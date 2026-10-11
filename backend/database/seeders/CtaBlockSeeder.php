<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Settings\Services\CtaBlockService;
use App\Models\CtaBlock;
use Illuminate\Database\Seeder;

class CtaBlockSeeder extends Seeder
{
    /**
     * Seed default CTA slots safely without overwriting existing admin edits.
     *
     * @param  array<int, string>  $forceUpdateKeys
     */
    public function run(array $forceUpdateKeys = []): void
    {
        $resolvedForceKeys = self::resolveForceUpdateKeys($forceUpdateKeys);

        foreach (CtaBlockService::defaultDefinitions() as $key => $def) {
            $attributes = [
                'label_ar' => $def['label_ar'],
                'headline_ar' => $def['headline_ar'],
                'subtext_ar' => $def['subtext_ar'],
                'url' => $def['url'],
                'style' => $def['style'],
                'is_active' => $def['is_active'],
                'starts_at' => $def['starts_at'],
                'ends_at' => $def['ends_at'],
                'open_in_new_tab' => $def['open_in_new_tab'],
            ];

            if (in_array('*', $resolvedForceKeys, true) || in_array($key, $resolvedForceKeys, true)) {
                CtaBlock::query()->updateOrCreate(['key' => $key], $attributes);
            } else {
                CtaBlock::query()->firstOrCreate(['key' => $key], $attributes);
            }
        }

        app(CtaBlockService::class)->flushCache();
    }

    /**
     * @param  array<int, string>  $explicitKeys
     * @return array<int, string>
     */
    private static function resolveForceUpdateKeys(array $explicitKeys): array
    {
        $envRaw = (string) env('SEED_FORCE_UPDATE_KEYS', '');
        $envKeys = $envRaw !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $envRaw))))
            : [];

        return array_values(array_unique(array_merge($explicitKeys, $envKeys)));
    }
}
