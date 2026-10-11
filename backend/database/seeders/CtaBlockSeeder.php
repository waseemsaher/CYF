<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Settings\Services\CtaBlockService;
use App\Models\CtaBlock;
use Illuminate\Database\Seeder;

class CtaBlockSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CtaBlockService::defaultDefinitions() as $key => $def) {
            CtaBlock::query()->firstOrCreate(
                ['key' => $key],
                [
                    'label_ar' => $def['label_ar'],
                    'headline_ar' => $def['headline_ar'],
                    'subtext_ar' => $def['subtext_ar'],
                    'url' => $def['url'],
                    'style' => $def['style'],
                    'is_active' => $def['is_active'],
                    'starts_at' => $def['starts_at'],
                    'ends_at' => $def['ends_at'],
                    'open_in_new_tab' => $def['open_in_new_tab'],
                ]
            );
        }

        app(CtaBlockService::class)->flushCache();
    }
}
