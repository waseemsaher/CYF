<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Only admins/superadmins can grant enrollments.
     */
    public function grant(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Only admins/superadmins can revoke enrollments.
     */
    public function revoke(User $user, Enrollment $enrollment): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Only admins/superadmins can extend enrollments.
     */
    public function extend(User $user, Enrollment $enrollment): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole('superadmin')
            || $user->hasRole('admin');
    }
}
