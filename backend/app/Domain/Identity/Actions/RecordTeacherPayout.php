<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Models\TeacherPayout;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RecordTeacherPayout
{
    public function handle(User $teacher, int $amountCents, User $admin, ?string $note = null): TeacherPayout
    {
        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount_cents' => ['مبلغ التحويل يجب أن يكون أكبر من صفر.'],
            ]);
        }

        $payout = TeacherPayout::create([
            'teacher_id' => $teacher->id,
            'amount_cents' => $amountCents,
            'paid_at' => now(),
            'note' => $note,
            'created_by' => $admin->id,
        ]);

        activity('payout')
            ->performedOn($payout)
            ->causedBy($admin)
            ->withProperties([
                'teacher_id' => $teacher->id,
                'amount_cents' => $amountCents,
                'note' => $note,
            ])
            ->log('teacher_payout_recorded');

        return $payout;
    }
}
