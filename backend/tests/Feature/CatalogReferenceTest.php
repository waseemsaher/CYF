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

it('returns the public terms for catalog contexts', function (): void {
    Term::create([
        'name' => ['ar' => 'الفصل الأول', 'en' => 'Term 1'],
        'starts_at' => '2026-09-01',
        'ends_at' => '2026-12-31',
        'is_current' => true,
        'sort_order' => 1,
    });

    Term::create([
        'name' => ['ar' => 'الفصل الثاني', 'en' => 'Term 2'],
        'starts_at' => '2027-01-01',
        'ends_at' => '2027-05-31',
        'is_current' => false,
        'sort_order' => 2,
    });

    $response = $this->getJson('/api/v1/reference/terms');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name.en', 'Term 1')
        ->assertJsonPath('data.0.is_current', true);
});
