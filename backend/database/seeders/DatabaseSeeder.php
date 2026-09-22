<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        $superadmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
        ]);
        $superadmin->assignRole('superadmin');

        $student = User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');
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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::findOrCreate('superadmin', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(['payments.review', 'students.manage']);

        Role::findOrCreate('teacher', 'web');
        Role::findOrCreate('student', 'web');
    }
}
