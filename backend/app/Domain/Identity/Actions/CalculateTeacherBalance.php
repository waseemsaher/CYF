<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Models\Payment;
use App\Models\TeacherPayout;
use App\Models\User;

class CalculateTeacherBalance
{
    /**
     * @return array{earned_cents: int, paid_out_cents: int, balance_cents: int}
     */
    public function handle(User $teacher): array
    {
        $courseIds = $teacher->taughtCourses()->pluck('courses.id');

        $totalEarnedCents = 0;
        if ($courseIds->isNotEmpty()) {
            $totalEarnedCents = (int) Payment::query()
                ->whereIn('course_id', $courseIds)
                ->where('status', 'approved')
                ->sum('teacher_share_cents');
        }

        $totalPaidOutCents = (int) TeacherPayout::query()
            ->where('teacher_id', $teacher->id)
            ->sum('amount_cents');

        $balanceCents = $totalEarnedCents - $totalPaidOutCents;

        return [
            'earned_cents' => $totalEarnedCents,
            'paid_out_cents' => $totalPaidOutCents,
            'balance_cents' => max(0, $balanceCents),
        ];
    }
}
