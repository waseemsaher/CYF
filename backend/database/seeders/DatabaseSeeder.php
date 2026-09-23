<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedRolesAndPermissions();
        $this->call(CatalogSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(ContentBlockSeeder::class);

        $superadmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $superadmin->hasRole('superadmin')) {
            $superadmin->assignRole('superadmin');
        }

        $student = User::firstOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Test Student',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $student->hasRole('student')) {
            $student->assignRole('student');
        }
    }

    private function seedRolesAndPermissions(): void
    {
        $permissions = [
            'payments.review',
            'students.manage',
            'courses.manage',
            'content.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Reset cached permissions so givePermissionTo can find newly created ones
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::findOrCreate('superadmin', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(['payments.review', 'students.manage']);

        Role::findOrCreate('teacher', 'web');
        Role::findOrCreate('student', 'web');
    }
}
