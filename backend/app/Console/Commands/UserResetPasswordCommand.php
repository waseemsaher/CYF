<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserResetPasswordCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'user:reset-password
                            {email : The email address of the user}';

    /**
     * @var string
     */
    protected $description = 'Set a temporary password for a user and require a password change on next login';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User not found with email: [{$email}]");

            return self::FAILURE;
        }

        $temporaryPassword = Str::password(16);

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ])->save();

        activity('user')
            ->performedOn($user)
            ->withProperties([
                'user_id' => $user->id,
                'email' => $user->email,
            ])
            ->log('password_reset_by_admin');

        $this->info("Password reset successfully for user: {$email}");
        $this->newLine();
        $this->warn("Temporary Password: {$temporaryPassword}");
        $this->line('Notice: This password will not be shown again. The user must change it upon login.');

        return self::SUCCESS;
    }
}
