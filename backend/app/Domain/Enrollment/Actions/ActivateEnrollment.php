<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;

class ActivateEnrollment
{
    /**
     * Activate an enrollment inside a caller's transaction.
     * Locks any existing enrollment for (user, course, term).
     *
     * - active AND not expired -> throws DomainException
     * - revoked or expired -> reactivates it
     * - none -> creates it
     */
    public function handle(
        User $user,
        Course $course,
        Term $term,
        string $source,
        ?int $paymentId = null,
        ?int $grantedBy = null,
    ): Enrollment {
        $graceDays = (int) Setting::getValue('enrollment', 'grace_days', 0);
        $expiresAt = $term->getAttribute('ends_at')->addDays($graceDays);

        /** @var Enrollment|null $existing */
        $existing = Enrollment::query()
            ->where('user_id', $user->getKey())
            ->where('course_id', $course->getKey())
            ->where('term_id', $term->getKey())
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            $isNotExpired = $existing->getAttribute('expires_at') !== null
                && $existing->getAttribute('expires_at')->isFuture();

            if ($existing->getAttribute('status') === 'active' && $isNotExpired) {
                throw new \DomainException('Student already has an active enrollment for this course in this term.');
            }

            $existing->update([
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'payment_id' => $paymentId,
                'source' => $source,
                'granted_by' => $grantedBy,
            ]);

            return $existing;
        }

        return Enrollment::create([
            'user_id' => $user->getKey(),
            'course_id' => $course->getKey(),
            'term_id' => $term->getKey(),
            'payment_id' => $paymentId,
            'source' => $source,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'granted_by' => $grantedBy,
        ]);
    }
}
