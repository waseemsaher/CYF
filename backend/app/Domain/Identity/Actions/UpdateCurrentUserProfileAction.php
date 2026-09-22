<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Models\User;

class UpdateCurrentUserProfileAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        $fillable = [
            'name',
            'branch',
            'academic_year',
            'department',
            'telegram_username',
            'phone',
            'locale',
        ];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $attributes) && $attributes[$field] !== null) {
                $user->setAttribute($field, $attributes[$field]);
            }
        }

        $user->save();

        return $user;
    }
}
