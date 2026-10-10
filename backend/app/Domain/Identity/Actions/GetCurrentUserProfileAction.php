<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Models\Enrollment;
use App\Models\User;

class GetCurrentUserProfileAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        return [
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->getAttribute('name'),
                'email' => $user->getAttribute('email'),
                'role' => $user->getRoleNames()->first(),
                'branch' => $user->getAttribute('branch'),
                'academic_year' => $user->getAttribute('academic_year'),
                'academic_year_id' => $user->getAttribute('academic_year_id') !== null ? (int) $user->getAttribute('academic_year_id') : null,
                'department' => $user->getAttribute('department'),
                'telegram_username' => $user->getAttribute('telegram_username'),
                'telegram_is_linked' => $user->getAttribute('telegram_user_id') !== null,
                'phone' => $user->getAttribute('phone'),
                'locale' => $user->getAttribute('locale'),
                'email_verified_at' => $user->getAttribute('email_verified_at')?->toIso8601String(),
                'must_change_password' => (bool) $user->getAttribute('must_change_password'),
                'is_active' => (bool) $user->getAttribute('is_active'),
                'enrolled_courses' => $user->enrollments()
                    ->where('status', 'active')
                    ->where('expires_at', '>', now())
                    ->with('course')
                    ->get()
                    ->map(fn (Enrollment $enrollment): ?array => $enrollment->course ? [
                        'id' => $enrollment->course->getKey(),
                        'slug' => $enrollment->course->getAttribute('slug'),
                        'title' => $enrollment->course->getTranslations('title'),
                    ] : null)
                    ->filter()
                    ->values()
                    ->all(),
            ],
        ];
    }
}
