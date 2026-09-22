<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('seeds the initial catalog data and is safe to run twice', function (): void {
    Artisan::call('db:seed', ['--class' => 'CatalogSeeder', '--no-interaction' => true]);
    Artisan::call('db:seed', ['--class' => 'CatalogSeeder', '--no-interaction' => true]);

    expect(AcademicYear::query()->count())->toBe(2)
        ->and(Department::query()->count())->toBe(4)
        ->and(Term::query()->count())->toBe(1)
        ->and(Course::query()->count())->toBe(5)
        ->and(Course::query()->where('status', 'published')->count())->toBe(5);
});
