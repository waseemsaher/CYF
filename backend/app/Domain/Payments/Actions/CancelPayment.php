<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Payment;
use App\Models\User;

class CancelPayment
{
    public function handle(Payment $payment, User $user): Payment
    {
        $payment->update([
            'status' => 'cancelled',
        ]);

        activity('payment')
            ->performedOn($payment)
            ->causedBy($user)
            ->withProperties([
                'payment_id' => $payment->getKey(),
            ])
            ->log('payment_cancelled');

        return $payment;
    }
}
