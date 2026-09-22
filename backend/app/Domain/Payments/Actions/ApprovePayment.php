<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovePayment
{
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

            // Get term for expiry calculation
            $term = $payment->term;
            $graceDays = (int) Setting::getValue('enrollment', 'grace_days', 0);

            // Create enrollment
            $enrollment = Enrollment::create([
                'user_id' => $payment->getAttribute('user_id'),
                'course_id' => $payment->getAttribute('course_id'),
                'term_id' => $payment->getAttribute('term_id'),
                'payment_id' => $payment->getKey(),
                'source' => 'payment',
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $term->getAttribute('ends_at')->addDays($graceDays),
            ]);

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

            // Notify via Telegram if linked
            $student = $payment->user;
            if ($student && $student->telegram_user_id) {
                $courseTitle = $payment->course->getTranslation('title', 'ar') ?: $payment->course->slug;

                $msg = "🎉 تم قبول عملية الدفع وتفعيل اشتراكك في مادة: <b>{$courseTitle}</b>!\nيمكنك الآن الانضمام إلى مجموعة التليجرام الخاصة بالمادة.\n\nYour payment has been approved and your course access is now active!";

                SendTelegramNotificationJob::dispatch((int) $student->telegram_user_id, $msg);
            }

            return $enrollment;
        });
    }

    private function resolveTeacherSharePercent(Payment $payment): int
    {
        // Priority: per-course override > global default
        $course = $payment->course;

        $courseOverride = $course->getAttribute('teacher_share_percent');
        if ($courseOverride !== null) {
            return (int) $courseOverride;
        }

        return (int) Setting::getValue('revenue', 'default_teacher_share_percent', 70);
    }
}
