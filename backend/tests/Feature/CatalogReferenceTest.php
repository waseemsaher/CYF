<?php

declare(strict_types=1);

use App\Models\AcademicYear;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the public academic years for catalog filters', function (): void {
    AcademicYear::create([
        'name' => ['ar' => 'السنة الأولى', 'en' => '1st Year'],
        'sort_order' => 1,
    ]);

    AcademicYear::create([
        'name' => ['ar' => 'السنة الثانية', 'en' => '2nd Year'],
        'sort_order' => 2,
    ]);

    $response = $this->getJson('/api/v1/reference/academic-years');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name.en', '1st Year');
});

it('returns the public departments for catalog filters', function (): void {
    Department::create([
        'code' => 'CS',
        'name' => ['ar' => 'علوم الحاسب', 'en' => 'Computer Science'],
        'sort_order' => 1,
    ]);

    Department::create([
        'code' => 'CY',
        'name' => ['ar' => 'الحاسب الآلي', 'en' => 'Cybersecurity'],
        'sort_order' => 2,
    ]);

    $response = $this->getJson('/api/v1/reference/departments');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.code', 'CS');
});
