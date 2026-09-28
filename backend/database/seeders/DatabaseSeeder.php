<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's reference data.
     * Demo accounts are strictly isolated and only seeded in local/testing environments.
     */
    public function run(): void
    {
        $this->seedRolesAndPermissions();
        $this->call(CatalogSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(ContentBlockSeeder::class);

        // Demo user accounts are strictly restricted to local and testing environments
        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoAccountsSeeder::class);
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
