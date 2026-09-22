<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function manage(User $user): bool
    {
        return $user->hasRole('superadmin') || $user->can('courses.manage');
    }

    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->manage($user);
    }
}
