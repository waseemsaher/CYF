<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;

class DiscountPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasRole('superadmin') || $user->can('courses.manage');
    }

    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Discount $discount): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Discount $discount): bool
    {
        return $this->manage($user);
    }
}
