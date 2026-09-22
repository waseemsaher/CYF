<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Students can view their own payments list.
     */
    public function viewOwn(User $user): bool
    {
        return true;
    }

    /**
     * Students can view a specific payment if it's theirs.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->getKey() === $payment->getAttribute('user_id')
            || $this->canReview($user);
    }

    /**
     * Students can submit payments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Students can cancel their own pending payments.
     */
    public function cancel(User $user, Payment $payment): bool
    {
        return $user->getKey() === $payment->getAttribute('user_id')
            && $payment->isPending();
    }

    /**
     * Admins with payments.review or superadmins can review.
     */
    public function review(User $user): bool
    {
        return $this->canReview($user);
    }

    private function canReview(User $user): bool
    {
        return $user->hasRole('superadmin') || $user->can('payments.review');
    }
}
