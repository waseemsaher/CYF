<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TeacherCreateCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'teacher:create
                            {email : The teacher email address}
                            {name : The teacher full display name}';

    /**
     * @var string
     */
    protected $description = 'Create a new teacher account with a secure temporary password';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $name = trim((string) $this->argument('name'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: [{$email}]");

            return self::FAILURE;
        }

        Role::findOrCreate('teacher', 'web');

        $existingUser = User::where('email', $email)->first();

        if ($existingUser !== null) {
            if ($existingUser->hasRole('teacher')) {
                $this->warn("Teacher account [{$email}] already exists.");

                return self::SUCCESS;
            }

            $existingUser->assignRole('teacher');
            $this->info("User [{$email}] was already registered and has been assigned the teacher role.");

            return self::SUCCESS;
        }

        $temporaryPassword = Str::password(16);

        $teacher = new User;
        $teacher->fill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $teacher->save();

        $teacher->assignRole('teacher');

        activity('teacher')
            ->performedOn($teacher)
            ->withProperties([
                'teacher_id' => $teacher->id,
                'email' => $teacher->email,
            ])
            ->log('teacher_created');

        $this->info("Teacher account created successfully: {$email}");
        $this->newLine();
        $this->warn("Temporary Password: {$temporaryPassword}");
        $this->line('Notice: This password will not be shown again. The teacher must change it on first login.');

        return self::SUCCESS;
    }
}
