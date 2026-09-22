<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Get a setting value by group and key, with optional default.
     */
    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $cacheKey = "settings.{$group}.{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($group, $key, $default): mixed {
            /** @var Setting|null $setting */
            $setting = static::query()
                ->where('group', $group)
                ->where('key', $key)
                ->first();

            return $setting?->getAttribute('value') ?? $default;
        });
    }

    /**
     * Set a setting value and invalidate cache.
     */
    public static function setValue(string $group, string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value],
        );

        Cache::forget("settings.{$group}.{$key}");
    }

    /**
     * Get all settings in a group.
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Setting> $settings */
        $settings = static::query()->where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[(string) $setting->getAttribute('key')] = $setting->getAttribute('value');
        }

        return $result;
    }
}
