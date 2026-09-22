<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

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

        return $payment;
    }
}
