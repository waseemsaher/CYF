<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use Carbon\CarbonImmutable;
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

it('returns the public detail for a published course', function (): void {
    Course::create([
        'slug' => 'c-plus-plus',
        'title' => ['ar' => 'لغة C++', 'en' => 'C++'],
        'description' => ['ar' => 'دورة شاملة', 'en' => 'Complete course'],
        'price_cents' => 35000,
        'status' => 'published',
        'sort_order' => 2,
    ]);

    Course::create([
        'slug' => 'hidden-course',
        'title' => ['ar' => 'دورة مخفية', 'en' => 'Hidden Course'],
        'description' => ['ar' => 'غير منشور', 'en' => 'Not published'],
        'price_cents' => 25000,
        'status' => 'draft',
        'sort_order' => 1,
    ]);

    $response = $this->getJson('/api/v1/courses/c-plus-plus');

    $response->assertOk()
        ->assertJsonPath('data.slug', 'c-plus-plus')
        ->assertJsonPath('data.title.en', 'C++');
});

it('filters the public catalog by academic year and department audience', function (): void {
    $firstYear = AcademicYear::create([
        'name' => ['ar' => 'السنة الأولى', 'en' => '1st Year'],
        'sort_order' => 1,
    ]);

    $secondYear = AcademicYear::create([
        'name' => ['ar' => 'السنة الثانية', 'en' => '2nd Year'],
        'sort_order' => 2,
    ]);

    $cs = Department::create([
        'code' => 'CS',
        'name' => ['ar' => 'علوم الحاسب', 'en' => 'Computer Science'],
        'sort_order' => 1,
    ]);

    $ai = Department::create([
        'code' => 'AI',
        'name' => ['ar' => 'الذكاء الاصطناعي', 'en' => 'AI'],
        'sort_order' => 2,
    ]);

    $targeted = Course::create([
        'slug' => 'cs-101',
        'title' => ['ar' => 'أساسيات الحاسب', 'en' => 'CS 101'],
        'description' => ['ar' => 'للسنة الأولى', 'en' => 'For 1st year'],
        'price_cents' => 15000,
        'status' => 'published',
        'sort_order' => 1,
    ]);

    $targeted->audiences()->create([
        'academic_year_id' => $firstYear->getKey(),
        'department_id' => $cs->getKey(),
    ]);

    Course::create([
        'slug' => 'ai-200',
        'title' => ['ar' => 'ذكاء اصطناعي', 'en' => 'AI 200'],
        'description' => ['ar' => 'للسنة الثانية', 'en' => 'For 2nd year'],
        'price_cents' => 18000,
        'status' => 'published',
        'sort_order' => 2,
    ]);

    $response = $this->getJson('/api/v1/courses?academic_year_id='.$firstYear->getKey().'&department_id='.$cs->getKey());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'cs-101');

    $otherResponse = $this->getJson('/api/v1/courses?academic_year_id='.$secondYear->getKey().'&department_id='.$ai->getKey());

    $otherResponse->assertOk()->assertJsonCount(0, 'data');
});

it('returns calculated pricing for public course responses', function (): void {
    CarbonImmutable::setTestNow('2026-09-22 12:00:00');

    try {
        $course = Course::create([
            'slug' => 'discounted-course',
            'title' => ['ar' => 'دورة مخفضة', 'en' => 'Discounted Course'],
            'description' => ['ar' => 'دورة بسعر مخفض', 'en' => 'Discounted course'],
            'price_cents' => 10000,
            'status' => 'published',
        ]);

        $course->discounts()->create([
            'name' => ['ar' => 'خصم عام', 'en' => 'Global discount'],
            'type' => 'fixed',
            'value' => 2500,
            'scope' => 'all',
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-09-30',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/courses/discounted-course');

        $response->assertOk()
            ->assertJsonPath('data.price_cents', 10000)
            ->assertJsonPath('data.list_price_cents', 10000)
            ->assertJsonPath('data.discount_cents', 2500)
            ->assertJsonPath('data.amount_due_cents', 7500);
    } finally {
        CarbonImmutable::setTestNow();
    }
});
