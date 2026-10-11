<?php

declare(strict_types=1);

namespace App\Domain\Settings\Services;

use App\Models\CtaBlock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class CtaBlockService
{
    private const CACHE_KEY_ALL = 'cta_blocks:all';

    private const CACHE_TTL_SECONDS = 60;

    /**
     * Required CTA slot keys that fall back to safe defaults when inactive/expired.
     * Optional slots ('home_hero_secondary', 'announcement_bar') render nothing when inactive/expired.
     *
     * @var list<string>
     */
    private const REQUIRED_SLOT_KEYS = [
        'home_hero_primary',
        'home_bottom',
        'course_page_default',
    ];

    public static function isRequiredSlot(string $key): bool
    {
        return in_array($key, self::REQUIRED_SLOT_KEYS, true);
    }

    /**
     * Default seeded CTA definitions. Used by CtaBlockSeeder and as safe fallback.
     *
     * @return array<string, array{
     *   key: string,
     *   required: bool,
     *   label_ar: string,
     *   headline_ar: string|null,
     *   subtext_ar: string|null,
     *   url: string,
     *   style: string,
     *   is_active: bool,
     *   starts_at: string|null,
     *   ends_at: string|null,
     *   open_in_new_tab: bool
     * }>
     */
    public static function defaultDefinitions(): array
    {
        return [
            'home_hero_primary' => [
                'key' => 'home_hero_primary',
                'required' => true,
                'label_ar' => 'ابدأ بمادة واحدة',
                'headline_ar' => null,
                'subtext_ar' => null,
                'url' => '/courses',
                'style' => 'primary',
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
                'open_in_new_tab' => false,
            ],
            'home_hero_secondary' => [
                'key' => 'home_hero_secondary',
                'required' => false,
                'label_ar' => 'شوف الدرس التجريبي',
                'headline_ar' => null,
                'subtext_ar' => null,
                'url' => '#sample-lesson',
                'style' => 'secondary',
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
                'open_in_new_tab' => false,
            ],
            'home_bottom' => [
                'key' => 'home_bottom',
                'required' => true,
                'label_ar' => 'شوف مواد فرقتك',
                'headline_ar' => 'جاهز تبدأ مذاكرة بنظام واضح؟',
                'subtext_ar' => 'اختار مادتك، ادفع بفودافون كاش أو إنستاباي على رقم رسمي، وهنراجع الإيصال ونفتحلك المحتوى.',
                'url' => '/courses',
                'style' => 'primary',
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
                'open_in_new_tab' => false,
            ],
            'course_page_default' => [
                'key' => 'course_page_default',
                'required' => true,
                'label_ar' => 'اشترك في المقرر الآن',
                'headline_ar' => null,
                'subtext_ar' => null,
                'url' => '/checkout',
                'style' => 'primary',
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
                'open_in_new_tab' => false,
            ],
            'announcement_bar' => [
                'key' => 'announcement_bar',
                'required' => false,
                'label_ar' => 'تصفح المواد',
                'headline_ar' => 'عرض بداية الترم متاح لفترة محدودة',
                'subtext_ar' => 'سعر خاص لطلاب حاسبات الأزهر قبل امتحانات الميدتيرم',
                'url' => '/courses',
                'style' => 'outline',
                'is_active' => false,
                'starts_at' => null,
                'ends_at' => null,
                'open_in_new_tab' => false,
            ],
        ];
    }

    /**
     * Sanitize plain text to prevent raw HTML injection.
     */
    public static function sanitizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim(strip_tags($value));

        return $cleaned === '' ? null : $cleaned;
    }

    /**
     * Validate that a URL is either a relative path (`/path` or `#anchor`) or an `https://` URL.
     */
    public static function isValidUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return false;
        }

        // Relative path starting with single `/` or `#`
        if (str_starts_with($trimmed, '/') && ! str_starts_with($trimmed, '//')) {
            return ! preg_match('/[\s<>"\']/', $trimmed);
        }

        if (str_starts_with($trimmed, '#') && strlen($trimmed) > 1) {
            return (bool) preg_match('/^#[a-zA-Z0-9_-]+$/', $trimmed);
        }

        if (str_starts_with($trimmed, 'https://')) {
            return filter_var($trimmed, FILTER_VALIDATE_URL) !== false
                && ! preg_match('/[\s<>"\']/', $trimmed);
        }

        return false;
    }

    /**
     * Return all CTA records for public consumption (cached with short TTL),
     * including visibility flag at current time.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getPublicMap(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = Cache::remember(self::CACHE_KEY_ALL, self::CACHE_TTL_SECONDS, function (): array {
            return CtaBlock::query()
                ->orderBy('id')
                ->get()
                ->map(fn (CtaBlock $block): array => $this->serialize($block))
                ->all();
        });

        $map = [];
        foreach ($rows as $row) {
            $status = $this->computeStatusFromSerialized($row, $now);
            $row['status'] = $status;
            $row['is_visible'] = $status === 'active';
            $map[(string) $row['key']] = $row;
        }

        return $map;
    }

    /**
     * Return all CTA blocks for admin management (ensuring defaults exist).
     *
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        $blocks = CtaBlock::query()->orderBy('id')->get();

        return $blocks->map(function (CtaBlock $block) use ($now): array {
            $data = $this->serialize($block);
            $status = $block->computedStatus($now);
            $data['status'] = $status;
            $data['is_visible'] = $status === 'active';

            return $data;
        })->all();
    }

    /**
     * Update or create a CTA block and flush cache.
     *
     * @param  array{
     *   label_ar: string,
     *   headline_ar?: string|null,
     *   subtext_ar?: string|null,
     *   url: string,
     *   style: string,
     *   is_active: bool,
     *   starts_at?: string|null,
     *   ends_at?: string|null,
     *   open_in_new_tab: bool
     * }  $attributes
     */
    public function upsert(string $key, array $attributes, ?int $updatedBy = null): CtaBlock
    {
        $labelAr = self::sanitizeText($attributes['label_ar']) ?? '';
        $headlineAr = self::sanitizeText($attributes['headline_ar'] ?? null);
        $subtextAr = self::sanitizeText($attributes['subtext_ar'] ?? null);

        /** @var CtaBlock $block */
        $block = CtaBlock::query()->updateOrCreate(
            ['key' => $key],
            [
                'label_ar' => $labelAr,
                'headline_ar' => $headlineAr,
                'subtext_ar' => $subtextAr,
                'url' => trim($attributes['url']),
                'style' => $attributes['style'],
                'is_active' => (bool) $attributes['is_active'],
                'starts_at' => $attributes['starts_at'] ?? null,
                'ends_at' => $attributes['ends_at'] ?? null,
                'open_in_new_tab' => (bool) $attributes['open_in_new_tab'],
                'updated_by' => $updatedBy,
            ]
        );

        $this->flushCache();

        return $block;
    }

    public function toggleActive(CtaBlock $block, ?int $updatedBy = null): CtaBlock
    {
        $block->update([
            'is_active' => ! $block->is_active,
            'updated_by' => $updatedBy,
        ]);

        $this->flushCache();

        return $block->fresh() ?? $block;
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY_ALL);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(CtaBlock $block): array
    {
        return [
            'id' => $block->getKey(),
            'key' => (string) $block->key,
            'label_ar' => self::sanitizeText((string) $block->label_ar) ?? '',
            'headline_ar' => self::sanitizeText($block->headline_ar),
            'subtext_ar' => self::sanitizeText($block->subtext_ar),
            'url' => (string) $block->url,
            'style' => (string) $block->style,
            'is_active' => (bool) $block->is_active,
            'required' => self::isRequiredSlot((string) $block->key),
            'starts_at' => $block->starts_at?->toISOString(),
            'ends_at' => $block->ends_at?->toISOString(),
            'open_in_new_tab' => (bool) $block->open_in_new_tab,
            'updated_by' => $block->updated_by,
            'updated_at' => $block->updated_at?->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function computeStatusFromSerialized(array $row, CarbonImmutable $now): string
    {
        if (empty($row['is_active'])) {
            return 'inactive';
        }

        if (! empty($row['ends_at'])) {
            $endsAt = CarbonImmutable::parse((string) $row['ends_at']);
            if ($now->greaterThan($endsAt)) {
                return 'expired';
            }
        }

        if (! empty($row['starts_at'])) {
            $startsAt = CarbonImmutable::parse((string) $row['starts_at']);
            if ($now->lessThan($startsAt)) {
                return 'scheduled';
            }
        }

        return 'active';
    }
}
