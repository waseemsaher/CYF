<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\Payment;
use App\Models\User;

class RejectPayment
{
    public function handle(Payment $payment, User $reviewer, string $reason): Payment
    {
        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
        ]);

        activity('payment')
            ->performedOn($payment)
            ->causedBy($reviewer)
            ->withProperties([
                'payment_id' => $payment->getKey(),
                'reason' => $reason,
            ])
            ->log('payment_rejected');

        $student = $payment->user;
        if ($student && $student->telegram_user_id) {
            $courseTitle = $payment->course->getTranslation('title', 'ar') ?: $payment->course->slug;

            $msg = "نأسف، تم رفض إيصال الدفع لمادة: <b>{$courseTitle}</b>.\nالسبب: <i>{$reason}</i>\nيمكنك إعادة رفع إيصال صحيح من حسابك.\n\nYour payment proof was rejected. Reason: {$reason}";

            SendTelegramNotificationJob::dispatch((int) $student->telegram_user_id, $msg);
        }

        return $payment;
    }
}
