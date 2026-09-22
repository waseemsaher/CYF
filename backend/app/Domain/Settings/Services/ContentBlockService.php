<?php

declare(strict_types=1);

namespace App\Domain\Settings\Services;

use App\Models\ContentBlock;
use Illuminate\Support\Facades\Cache;

class ContentBlockService
{
    private const CACHE_PREFIX = 'content_block:';

    /**
     * @param  array{ar: string, en: string}|null  $default
     * @return array{ar: string, en: string}|null
     */
    public function get(string $key, ?array $default = null): ?array
    {
        return Cache::remember(self::CACHE_PREFIX.$key, 3600, function () use ($key, $default): ?array {
            /** @var ContentBlock|null $block */
            $block = ContentBlock::query()->where('key', $key)->first();

            return $block ? $block->getTranslations('content') : $default;
        });
    }

    /**
     * @param  array{ar: string, en: string}  $content
     */
    public function set(string $key, array $content, ?string $group = null): ContentBlock
    {
        /** @var ContentBlock $block */
        $block = ContentBlock::query()->updateOrCreate(
            ['key' => $key],
            [
                'content' => $content,
                'group' => $group,
            ]
        );

        Cache::forget(self::CACHE_PREFIX.$key);

        return $block;
    }
}
