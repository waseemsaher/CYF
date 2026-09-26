<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateSuperadminCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'admin:create-superadmin
                            {email? : The superadmin email address}
                            {--name=Super Admin : The display name for the account}';

    /**
     * @var string
     */
    protected $description = 'Create a new superadmin account with a secure random password';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! $email) {
            $email = $this->ask('Enter superadmin email address');
        }

        $email = is_string($email) ? trim($email) : '';

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('The provided email address is invalid.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: 'Super Admin');
        $password = Str::password(20);

        Role::findOrCreate('superadmin', 'web');

        $user = new User;
        $user->fill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'must_change_password' => true,
            'is_active' => true,
        ]);
        $user->save();

        $user->assignRole('superadmin');

        $this->info('Superadmin account created successfully.');
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");
        $this->warn('Warning: Store this password securely. It will not be shown again.');

        return self::SUCCESS;
    }
}
