<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Payment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelPayment
{
    public function handle(Payment $payment, User $user): Payment
    {
        return DB::transaction(function () use ($payment, $user): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            if (! $locked->isPending()) {
                throw new DomainException("Only pending payments can be cancelled. Current status: {$locked->getAttribute('status')}");
            }

            $locked->update([
                'status' => 'cancelled',
            ]);

            activity('payment')
                ->performedOn($locked)
                ->causedBy($user)
                ->withProperties([
                    'payment_id' => $locked->getKey(),
                ])
                ->log('payment_cancelled');

            return $locked;
        });
    }
}
