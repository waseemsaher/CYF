<?php

declare(strict_types=1);

use App\Models\ContentBlock;
use App\Models\CtaBlock;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\CtaBlockSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not overwrite existing ContentBlock or CtaBlock edits when re-seeding by default', function (): void {
    $this->seed(ContentBlockSeeder::class);
    $this->seed(CtaBlockSeeder::class);

    ContentBlock::query()->where('key', 'landing.hero')->firstOrFail()->update([
        'content' => [
            'ar' => 'عنوان مخصص من لوحة التحكم لا يجب استبداله',
            'en' => 'Custom admin headline that must not be overwritten',
        ],
    ]);

    CtaBlock::query()->where('key', 'home_hero_primary')->firstOrFail()->update([
        'label_ar' => 'زر مخصص من الأدمن',
        'url' => '/courses?year=1',
    ]);

    // Re-run both seeders without force flags
    $this->seed(ContentBlockSeeder::class);
    $this->seed(CtaBlockSeeder::class);

    $heroBlock = ContentBlock::query()->where('key', 'landing.hero')->firstOrFail();
    expect($heroBlock->getTranslation('content', 'ar'))->toBe('عنوان مخصص من لوحة التحكم لا يجب استبداله');

    $ctaBlock = CtaBlock::query()->where('key', 'home_hero_primary')->firstOrFail();
    expect($ctaBlock->label_ar)->toBe('زر مخصص من الأدمن')
        ->and($ctaBlock->url)->toBe('/courses?year=1');
});

it('allows explicit opt-in force-update per key on ContentBlockSeeder and CtaBlockSeeder', function (): void {
    $this->seed(ContentBlockSeeder::class);
    $this->seed(CtaBlockSeeder::class);

    ContentBlock::query()->where('key', 'landing.hero')->firstOrFail()->update([
        'content' => ['ar' => 'تعديل مؤقت 1', 'en' => 'Temp 1'],
    ]);
    ContentBlock::query()->where('key', 'legal.refund')->firstOrFail()->update([
        'content' => ['ar' => 'تعديل مؤقت 2', 'en' => 'Temp 2'],
    ]);

    CtaBlock::query()->where('key', 'home_hero_primary')->firstOrFail()->update([
        'label_ar' => 'تعديل زر 1',
    ]);
    CtaBlock::query()->where('key', 'home_hero_secondary')->firstOrFail()->update([
        'label_ar' => 'تعديل زر 2',
    ]);

    // Force-update only legal.refund and home_hero_secondary
    app(ContentBlockSeeder::class)->run(['legal.refund']);
    app(CtaBlockSeeder::class)->run(['home_hero_secondary']);

    expect(ContentBlock::query()->where('key', 'landing.hero')->firstOrFail()->getTranslation('content', 'ar'))
        ->toBe('تعديل مؤقت 1');
    expect(ContentBlock::query()->where('key', 'legal.refund')->firstOrFail()->getTranslation('content', 'ar'))
        ->toContain('سياسة الاسترداد في Codeera');

    expect(CtaBlock::query()->where('key', 'home_hero_primary')->firstOrFail()->label_ar)
        ->toBe('تعديل زر 1');
    expect(CtaBlock::query()->where('key', 'home_hero_secondary')->firstOrFail()->label_ar)
        ->toBe('شوف الدرس التجريبي');
});

it('contains no square-bracket placeholders in seeded ContentBlock or CtaBlock records', function (): void {
    $this->seed(ContentBlockSeeder::class);
    $this->seed(CtaBlockSeeder::class);

    foreach (ContentBlock::all() as $block) {
        $ar = (string) $block->getTranslation('content', 'ar');
        $en = (string) $block->getTranslation('content', 'en');
        expect($ar)->not->toMatch('/\[[^\]]+\]/')
            ->and($en)->not->toMatch('/\[[^\]]+\]/');
    }

    foreach (CtaBlock::all() as $cta) {
        expect((string) $cta->label_ar)->not->toMatch('/\[[^\]]+\]/')
            ->and((string) ($cta->headline_ar ?? ''))->not->toMatch('/\[[^\]]+\]/')
            ->and((string) ($cta->subtext_ar ?? ''))->not->toMatch('/\[[^\]]+\]/');
    }
});
