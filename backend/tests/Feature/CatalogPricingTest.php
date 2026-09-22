<?php

declare(strict_types=1);

use App\Domain\Catalog\Actions\CalculateCoursePrice;
use App\Models\Course;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies the best valid non-stacking discount to a course price', function (): void {
    $course = Course::create([
        'slug' => 'pricing-course',
        'title' => ['ar' => 'دورة التسعير', 'en' => 'Pricing Course'],
        'description' => ['ar' => 'اختبار التسعير', 'en' => 'Pricing test'],
        'price_cents' => 10000,
        'status' => 'published',
    ]);

    $course->discounts()->create([
        'name' => ['ar' => 'خصم ثابت', 'en' => 'Fixed discount'],
        'type' => 'fixed',
        'value' => 2500,
        'scope' => 'courses',
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-30',
        'is_active' => true,
    ]);

    $course->discounts()->create([
        'name' => ['ar' => 'خصم نسبي', 'en' => 'Percent discount'],
        'type' => 'percent',
        'value' => 30,
        'scope' => 'courses',
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-30',
        'is_active' => true,
    ]);

    $result = app(CalculateCoursePrice::class)->handle(
        $course,
        CarbonImmutable::parse('2026-09-22'),
    );

    expect($result)->toBe([
        'list_price_cents' => 10000,
        'discount_cents' => 3000,
        'amount_due_cents' => 7000,
        'discount_id' => $course->discounts()->where('type', 'percent')->value('id'),
    ]);
});

it('ignores invalid discounts and never returns a negative price', function (): void {
    $course = Course::create([
        'slug' => 'free-pricing-course',
        'title' => ['ar' => 'دورة مجانية', 'en' => 'Free Pricing Course'],
        'description' => ['ar' => 'اختبار السعر المجاني', 'en' => 'Free price test'],
        'price_cents' => 1000,
        'status' => 'published',
    ]);

    $course->discounts()->create([
        'name' => ['ar' => 'خصم منته', 'en' => 'Expired discount'],
        'type' => 'fixed',
        'value' => 5000,
        'scope' => 'courses',
        'starts_at' => '2026-08-01',
        'ends_at' => '2026-08-31',
        'is_active' => true,
    ]);

    $course->discounts()->create([
        'name' => ['ar' => 'خصم كبير', 'en' => 'Large discount'],
        'type' => 'fixed',
        'value' => 5000,
        'scope' => 'courses',
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-09-30',
        'is_active' => true,
    ]);

    $result = app(CalculateCoursePrice::class)->handle(
        $course,
        CarbonImmutable::parse('2026-09-22'),
    );

    expect($result['list_price_cents'])->toBe(1000)
        ->and($result['discount_cents'])->toBe(1000)
        ->and($result['amount_due_cents'])->toBe(0);
});
