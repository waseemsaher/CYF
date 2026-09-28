<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Run the demo user accounts seeder.
     * STRICTLY GUARDED: Never runnable in production environments.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('SECURITY ERROR: Demo accounts seeder cannot be run in non-local environments.');
        }

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

        $teacher = User::firstOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Dr. Ahmed Mahmoud',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $teacher->hasRole('teacher')) {
            $teacher->assignRole('teacher');
        }

        $firstCourse = Course::first();
        if ($firstCourse && ! $teacher->taughtCourses()->where('courses.id', $firstCourse->id)->exists()) {
            $teacher->taughtCourses()->attach($firstCourse->id, [
                'teacher_share_percent' => 70,
            ]);
        }
    }
}
