<?php

declare(strict_types=1);

use App\Models\ContentBlock;
use App\Models\Course;
use App\Models\CourseItem;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('superadmin', 'web');
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('teacher', 'web');
    Role::findOrCreate('student', 'web');
});

/* -------------------------------------------------------------------------- */
/* 1. content:import */
/* -------------------------------------------------------------------------- */
test('content:import validates schema and imports course, sections, and items idempotently', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'content_test_').'.json';
    $jsonPayload = [
        'courses' => [
            [
                'slug' => 'pilot-course-101',
                'title' => [
                    'ar' => 'مقرر تجريبي',
                    'en' => 'Pilot Course 101',
                ],
                'description' => [
                    'ar' => 'وصف المقرر التجريبي',
                    'en' => 'Pilot course description',
                ],
                'price_cents' => 12000,
                'status' => 'published',
                'telegram_chat_id' => -100987654321,
                'telegram_invite_link' => 'https://t.me/+validInviteLink123',
                'sections' => [
                    [
                        'title' => [
                            'ar' => 'القسم الأول',
                            'en' => 'Section 1',
                        ],
                        'position' => 1,
                        'items' => [
                            [
                                'type' => 'link',
                                'title' => [
                                    'ar' => 'رابط المحاضرة',
                                    'en' => 'Lecture Link',
                                ],
                                'url' => 'https://t.me/c/12345/1',
                                'position' => 1,
                                'published' => true,
                            ],
                            [
                                'type' => 'text',
                                'title' => [
                                    'ar' => 'ملاحظات',
                                    'en' => 'Notes',
                                ],
                                'description' => [
                                    'ar' => 'ملاحظات مهمة',
                                    'en' => 'Important notes',
                                ],
                                'position' => 2,
                                'published' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
    file_put_contents($tempFile, json_encode($jsonPayload));

    // Test Dry-Run
    $this->artisan('content:import', [
        'file' => $tempFile,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Executing in DRY-RUN mode')
        ->expectsOutputToContain('Import summary:')
        ->assertSuccessful();

    expect(Course::where('slug', 'pilot-course-101')->exists())->toBeFalse();

    // Test Real Import
    $this->artisan('content:import', [
        'file' => $tempFile,
    ])
        ->expectsOutputToContain('Import summary:')
        ->expectsOutputToContain('1 created, 0 updated')
        ->assertSuccessful();

    $course = Course::where('slug', 'pilot-course-101')->first();
    expect($course)->not->toBeNull()
        ->and($course->getTranslation('title', 'ar'))->toBe('مقرر تجريبي')
        ->and($course->price_cents)->toBe(12000)
        ->and($course->telegram_invite_link)->toBe('https://t.me/+validInviteLink123')
        ->and(CourseSection::where('course_id', $course->id)->count())->toBe(1)
        ->and(CourseItem::where('course_id', $course->id)->count())->toBe(2);

    // Test Idempotence: Second run updates without duplicating
    $this->artisan('content:import', [
        'file' => $tempFile,
    ])
        ->expectsOutputToContain('0 created, 1 updated')
        ->assertSuccessful();

    expect(Course::where('slug', 'pilot-course-101')->count())->toBe(1)
        ->and(CourseSection::where('course_id', $course->id)->count())->toBe(1)
        ->and(CourseItem::where('course_id', $course->id)->count())->toBe(2);

    // Verify activity logged
    expect(Activity::where('log_name', 'content')->where('description', 'content_imported')->exists())->toBeTrue();

    unlink($tempFile);
});

test('content:import fails on invalid schema or non-existent file', function (): void {
    $this->artisan('content:import', ['file' => '/path/does/not/exist.json'])
        ->expectsOutputToContain('File does not exist')
        ->assertFailed();

    $invalidFile = tempnam(sys_get_temp_dir(), 'invalid_json_').'.json';
    file_put_contents($invalidFile, json_encode(['courses' => [['invalid' => 'data']]]));

    $this->artisan('content:import', ['file' => $invalidFile])
        ->expectsOutputToContain('Validation errors found')
        ->assertFailed();

    unlink($invalidFile);
});

/* -------------------------------------------------------------------------- */
/* 2. settings:show and settings:set */
/* -------------------------------------------------------------------------- */
test('settings:set updates platform settings and settings:show displays them', function (): void {
    $this->artisan('settings:set', [
        'setting' => 'payment_methods.vodafone_cash',
        'value' => '01012345678',
    ])
        ->expectsOutputToContain('Setting [payment_methods.vodafone_cash] updated successfully.')
        ->assertSuccessful();

    expect(Setting::getValue('payment_methods', 'vodafone_cash'))->toBe('01012345678');

    // Integer / JSON parsing check
    $this->artisan('settings:set', [
        'setting' => 'uploads.max_file_size_kb',
        'value' => '51200',
    ])
        ->expectsOutputToContain('Setting [uploads.max_file_size_kb] updated successfully.')
        ->assertSuccessful();

    expect(Setting::getValue('uploads', 'max_file_size_kb'))->toBe(51200);

    // settings:show output verification
    $this->artisan('settings:show', ['group' => 'payment_methods'])
        ->expectsTable(['Group', 'Key', 'Value'], [
            ['payment_methods', 'vodafone_cash', '01012345678'],
        ])
        ->assertSuccessful();

    // Verify activity log
    expect(Activity::where('log_name', 'settings')->where('description', 'setting_updated')->exists())->toBeTrue();
});

test('settings:set validates group.key format', function (): void {
    $this->artisan('settings:set', [
        'setting' => 'invalid_format_without_dot',
        'value' => 'value',
    ])
        ->expectsOutputToContain('group.key format')
        ->assertFailed();
});

test('settings:set validates revenue.default_teacher_share_percent range 0 to 100', function (): void {
    $this->artisan('settings:set', [
        'setting' => 'revenue.default_teacher_share_percent',
        'value' => '150',
    ])
        ->expectsOutputToContain('between 0 and 100')
        ->assertFailed();
});

/* -------------------------------------------------------------------------- */
/* 3. teacher:create and teacher:assign */
/* -------------------------------------------------------------------------- */
test('teacher:create creates teacher with temporary password and forces password change', function (): void {
    $this->artisan('teacher:create', [
        'email' => 'teacher.test@codeera.tech',
        'name' => 'Dr. Ahmed Taha',
    ])
        ->expectsOutputToContain('Teacher account created successfully: teacher.test@codeera.tech')
        ->expectsOutputToContain('Temporary Password:')
        ->expectsOutputToContain('The teacher must change it on first login')
        ->assertSuccessful();

    $teacher = User::where('email', 'teacher.test@codeera.tech')->first();
    expect($teacher)->not->toBeNull()
        ->and($teacher->name)->toBe('Dr. Ahmed Taha')
        ->and($teacher->must_change_password)->toBeTrue()
        ->and($teacher->hasRole('teacher'))->toBeTrue()
        ->and($teacher->email_verified_at)->not->toBeNull();

    // Re-running is idempotent and reports already existing
    $this->artisan('teacher:create', [
        'email' => 'teacher.test@codeera.tech',
        'name' => 'Dr. Ahmed Taha',
    ])
        ->expectsOutputToContain('already exists')
        ->assertSuccessful();
});

test('teacher:assign associates teacher with course and sets share percent', function (): void {
    $teacher = User::factory()->create(['email' => 'prof@example.com']);
    $teacher->assignRole('teacher');

    $course = Course::create([
        'slug' => 'test-assign-course',
        'title' => ['ar' => 'كورس', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
    ]);

    $this->artisan('teacher:assign', [
        'teacher' => 'prof@example.com',
        'course' => 'test-assign-course',
        '--share' => '75',
    ])
        ->expectsOutputToContain('with 75% revenue share.')
        ->assertSuccessful();

    $attached = $course->teachers()->where('teacher_id', $teacher->id)->first();
    expect($attached)->not->toBeNull()
        ->and((int) $attached->pivot->teacher_share_percent)->toBe(75);
});

/* -------------------------------------------------------------------------- */
/* 4. enrollment:grant and enrollment:revoke */
/* -------------------------------------------------------------------------- */
test('enrollment:grant manually creates active enrollment and enrollment:revoke revokes it', function (): void {
    $student = User::factory()->create(['email' => 'student.cli@example.com']);
    $student->assignRole('student');

    $term = Term::create([
        'name' => ['ar' => 'الفصل الأول', 'en' => 'Term 1'],
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDays(90),
        'is_current' => true,
    ]);

    $course = Course::create([
        'slug' => 'pilot-grant-course',
        'title' => ['ar' => 'كورس', 'en' => 'Course'],
        'description' => ['ar' => 'وصف', 'en' => 'Desc'],
        'price_cents' => 10000,
    ]);

    // Grant
    $this->artisan('enrollment:grant', [
        'email' => 'student.cli@example.com',
        'course' => 'pilot-grant-course',
    ])
        ->expectsOutputToContain('Enrollment granted successfully')
        ->assertSuccessful();

    $enrollment = Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->first();
    expect($enrollment)->not->toBeNull()
        ->and($enrollment->status)->toBe('active')
        ->and($enrollment->source)->toBe('admin_grant');

    // Grant idempotency
    $this->artisan('enrollment:grant', [
        'email' => 'student.cli@example.com',
        'course' => 'pilot-grant-course',
    ])
        ->expectsOutputToContain('already has an active enrollment')
        ->assertSuccessful();

    // Revoke
    $this->artisan('enrollment:revoke', [
        'email' => 'student.cli@example.com',
        'course' => 'pilot-grant-course',
    ])
        ->expectsOutputToContain('Enrollment revoked successfully')
        ->assertSuccessful();

    $enrollment->refresh();
    expect($enrollment->status)->toBe('revoked');
});

/* -------------------------------------------------------------------------- */
/* 5. user:reset-password */
/* -------------------------------------------------------------------------- */
test('user:reset-password generates a temporary password and requires change', function (): void {
    $user = User::factory()->create([
        'email' => 'locked.out@example.com',
        'password' => Hash::make('old-password-123'),
        'must_change_password' => false,
    ]);

    $this->artisan('user:reset-password', [
        'email' => 'locked.out@example.com',
    ])
        ->expectsOutputToContain('Password reset successfully for user: locked.out@example.com')
        ->expectsOutputToContain('Temporary Password:')
        ->expectsOutputToContain('The user must change it upon login')
        ->assertSuccessful();

    $user->refresh();
    expect($user->must_change_password)->toBeTrue()
        ->and(Hash::check('old-password-123', $user->password))->toBeFalse();

    expect(Activity::where('log_name', 'user')->where('description', 'password_reset_by_admin')->exists())->toBeTrue();
});

/* -------------------------------------------------------------------------- */
/* 6. content-block:set */
/* -------------------------------------------------------------------------- */
test('content-block:set loads text from files into translatable content block', function (): void {
    $arFile = tempnam(sys_get_temp_dir(), 'ar_terms_').'.txt';
    $enFile = tempnam(sys_get_temp_dir(), 'en_terms_').'.txt';

    file_put_contents($arFile, "شروط الاستخدام التجريبية الخاصة بالمنصة.\nسيتم التحديث قريباً.");
    file_put_contents($enFile, "Pilot terms and conditions for the platform.\nWill be updated soon.");

    $this->artisan('content-block:set', [
        'key' => 'terms_and_conditions',
        '--ar' => $arFile,
        '--en' => $enFile,
    ])
        ->expectsOutputToContain('Content block [terms_and_conditions] successfully updated from file(s).')
        ->assertSuccessful();

    $block = ContentBlock::where('key', 'terms_and_conditions')->first();
    expect($block)->not->toBeNull()
        ->and($block->getTranslation('content', 'ar'))->toContain('شروط الاستخدام التجريبية')
        ->and($block->getTranslation('content', 'en'))->toContain('Pilot terms and conditions');

    unlink($arFile);
    unlink($enFile);
});
