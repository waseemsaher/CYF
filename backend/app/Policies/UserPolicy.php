<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $authenticatedUser, User $targetUser): bool
    {
        // Cannot delete self
        if ($authenticatedUser->id === $targetUser->id) {
            return false;
        }

        // Must be superadmin or admin with students.manage permission
        if (! $authenticatedUser->hasRole(['superadmin', 'admin']) && ! $authenticatedUser->can('students.manage')) {
            return false;
        }

        // Superadmin target check: only superadmin can delete another superadmin, and at least 1 must remain
        if ($targetUser->hasRole('superadmin')) {
            if (! $authenticatedUser->hasRole('superadmin')) {
                return false;
            }

            if (User::role('superadmin')->count() <= 1) {
                return false;
            }
        }

        return true;
    }
}
