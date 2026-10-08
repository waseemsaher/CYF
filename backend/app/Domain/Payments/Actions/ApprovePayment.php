<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Enrollment\Actions\ActivateEnrollment;
use App\Jobs\SendCourseInviteLinkJob;
use App\Jobs\SendTelegramNotificationJob;
use App\Mail\PaymentApprovedMail;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ApprovePayment
{
    public function __construct(
        private readonly ActivateEnrollment $activateEnrollment,
    ) {}

    /**
     * Approve a payment, create enrollment, and freeze revenue shares.
     * Runs inside a DB transaction with row locking. Idempotent.
     */
    public function handle(Payment $payment, User $reviewer): Enrollment
    {
        return DB::transaction(function () use ($payment, $reviewer): Enrollment {
            // Lock the payment row for update
            /** @var Payment $payment */
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            // Idempotent: if already approved, return existing enrollment
            if ($payment->isApproved()) {
                /** @var Enrollment $existing */
                $existing = Enrollment::query()
                    ->where('payment_id', $payment->getKey())
                    ->firstOrFail();

                return $existing;
            }

            if (! $payment->isPending()) {
                throw new \DomainException("Only pending payments can be approved. Current status: {$payment->getAttribute('status')}");
            }

            // Calculate revenue shares
            $amountDueCents = (int) $payment->getAttribute('amount_due_cents');
            $teacherSharePercent = $this->resolveTeacherSharePercent($payment);
            $teacherShareCents = intdiv($amountDueCents * $teacherSharePercent, 100);
            $platformShareCents = $amountDueCents - $teacherShareCents;

            // Update payment status and freeze shares
            $payment->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->getKey(),
                'reviewed_at' => now(),
                'teacher_share_percent' => $teacherSharePercent,
                'teacher_share_cents' => $teacherShareCents,
                'platform_share_cents' => $platformShareCents,
            ]);

            // Activate enrollment
            $enrollment = $this->activateEnrollment->handle(
                user: $payment->user,
                course: $payment->course,
                term: $payment->term,
                source: 'payment',
                paymentId: $payment->getKey(),
                grantedBy: null,
            );

            // Log activity
            activity('payment')
                ->performedOn($payment)
                ->causedBy($reviewer)
                ->withProperties([
                    'payment_id' => $payment->getKey(),
                    'enrollment_id' => $enrollment->getKey(),
                    'amount_due_cents' => $amountDueCents,
                    'teacher_share_cents' => $teacherShareCents,
                    'platform_share_cents' => $platformShareCents,
                ])
                ->log('payment_approved');

            // Notify via Telegram if linked (queued, non-blocking)
            $student = $payment->user;
            if ($student && $student->telegram_user_id) {
                $course = $payment->course;
                $courseTitle = $course->getTranslation('title', 'ar') ?: $course->slug;

                if ($course->getAttribute('telegram_group_id')) {
                    // Primary path: push a single-use invite link directly to the student
                    SendCourseInviteLinkJob::dispatch($student, $course)->afterCommit();
                } else {
                    // Fallback: plain approval message with optional static invite link
                    $msg = "تم قبول عملية الدفع وتفعيل اشتراكك في مادة: <b>{$courseTitle}</b>!\nيمكنك الآن الانضمام إلى مجموعة التليجرام الخاصة بالمادة.\n\nYour payment has been approved and your course access is now active!";

                    $inviteLink = $course->telegram_invite_link;
                    if ($inviteLink) {
                        $msg .= "\n\n<a href=\"{$inviteLink}\">انضم إلى القناة / Join Channel</a>";
                    }

                    SendTelegramNotificationJob::dispatch((int) $student->telegram_user_id, $msg)->afterCommit();
                }
            } elseif ($student && $student->email) {
                Mail::to($student->email)->queue((new PaymentApprovedMail($payment))->afterCommit());
            }

            return $enrollment;
        });
    }

    private function resolveTeacherSharePercent(Payment $payment): int
    {
        // Priority: per-course override > global default
        $course = $payment->course;

        $courseOverride = $course->getAttribute('teacher_share_percent');
        $sharePercent = $courseOverride !== null
            ? (int) $courseOverride
            : (int) Setting::getValue('revenue', 'default_teacher_share_percent', 70);

        if ($sharePercent < 0 || $sharePercent > 100) {
            throw new \DomainException("Teacher share percent must be between 0 and 100. Got: {$sharePercent}");
        }

        return $sharePercent;
    }
}
