<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasRole('superadmin') || $user->can('coupons.manage') || $user->can('courses.manage');
    }

    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $this->manage($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $this->manage($user);
    }
}
