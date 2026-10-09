<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeEnrollment
{
    public function handle(Enrollment $enrollment, User $admin, bool $refund = false): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $admin, $refund): Enrollment {
            /** @var Enrollment $lockedEnrollment */
            $lockedEnrollment = Enrollment::query()
                ->lockForUpdate()
                ->findOrFail($enrollment->getKey());

            if ($lockedEnrollment->getAttribute('status') === 'revoked') {
                return $lockedEnrollment;
            }

            $lockedEnrollment->update([
                'status' => 'revoked',
            ]);

            activity('enrollment')
                ->performedOn($lockedEnrollment)
                ->causedBy($admin)
                ->withProperties([
                    'enrollment_id' => $lockedEnrollment->getKey(),
                    'student_id' => $lockedEnrollment->getAttribute('user_id'),
                    'course_id' => $lockedEnrollment->getAttribute('course_id'),
                ])
                ->log('enrollment_revoked');

            $paymentId = $lockedEnrollment->getAttribute('payment_id');
            if ($refund && $paymentId) {
                /** @var Payment|null $payment */
                $payment = Payment::query()
                    ->lockForUpdate()
                    ->find($paymentId);

                if ($payment && $payment->isApproved()) {
                    $payment->update([
                        'status' => 'refunded',
                    ]);

                    activity('payment')
                        ->performedOn($payment)
                        ->causedBy($admin)
                        ->withProperties([
                            'payment_id' => $payment->getKey(),
                            'enrollment_id' => $lockedEnrollment->getKey(),
                        ])
                        ->log('payment_refunded');
                }
            }

            return $lockedEnrollment;
        });
    }
}
