<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only published courses in the public catalog', function (): void {
    AcademicYear::create([
        'name' => ['ar' => 'السنة الأولى', 'en' => '1st Year'],
        'sort_order' => 1,
    ]);

    Department::create([
        'code' => 'CS',
        'name' => ['ar' => 'علوم الحاسب', 'en' => 'Computer Science'],
        'sort_order' => 1,
    ]);

    Course::create([
        'slug' => 'draft-course',
        'title' => ['ar' => 'مسار مسودة', 'en' => 'Draft Course'],
        'description' => ['ar' => 'غير منشور', 'en' => 'Not published'],
        'price_cents' => 25000,
        'status' => 'draft',
        'sort_order' => 1,
    ]);

    Course::create([
        'slug' => 'c-plus-plus',
        'title' => ['ar' => 'لغة C++', 'en' => 'C++'],
        'description' => ['ar' => 'دورة شاملة', 'en' => 'Complete course'],
        'price_cents' => 35000,
        'status' => 'published',
        'sort_order' => 2,
    ]);

    Course::create([
        'slug' => 'discrete-math',
        'title' => ['ar' => 'الرياضيات المتقطعة', 'en' => 'Discrete Mathematics'],
        'description' => ['ar' => 'أساسيات المنطق', 'en' => 'Logic basics'],
        'price_cents' => 40000,
        'status' => 'published',
        'sort_order' => 1,
    ]);

    $response = $this->getJson('/api/v1/courses');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'discrete-math')
        ->assertJsonPath('data.1.slug', 'c-plus-plus');
});
