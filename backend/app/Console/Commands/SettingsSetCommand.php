<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class SettingsSetCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'settings:set
                            {setting : The setting identifier in group.key format (e.g. payment_methods.instapay_handle)}
                            {value : The value to set (raw string, number, or valid JSON)}';

    /**
     * @var string
     */
    protected $description = 'Set a platform setting by group.key';

    public function handle(): int
    {
        $settingParam = (string) $this->argument('setting');
        $rawValue = (string) $this->argument('value');

        if (! str_contains($settingParam, '.')) {
            $this->error('The setting must be provided in group.key format (e.g. payment_methods.vodafone_cash).');

            return self::FAILURE;
        }

        [$group, $key] = explode('.', $settingParam, 2);

        if (trim($group) === '' || trim($key) === '') {
            $this->error('Both group and key must be non-empty.');

            return self::FAILURE;
        }

        // Try to decode JSON, boolean, or numeric values
        $parsedValue = match (strtolower($rawValue)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($rawValue) && ! str_starts_with($rawValue, '0')
                ? (str_contains($rawValue, '.') ? (float) $rawValue : (int) $rawValue)
                : (json_validate($rawValue) ? json_decode($rawValue, true) : $rawValue),
        };

        if ($key === 'default_teacher_share_percent' || $key === 'teacher_share_percent') {
            $validator = validator(
                ['value' => $parsedValue],
                ['value' => ['required', 'integer', 'between:0,100']]
            );
            if ($validator->fails()) {
                $this->error('The teacher_share_percent must be an integer between 0 and 100.');

                return self::FAILURE;
            }
        }

        Setting::setValue($group, $key, $parsedValue);

        activity('settings')
            ->withProperties([
                'group' => $group,
                'key' => $key,
            ])
            ->log('setting_updated');

        $this->info("Setting [{$group}.{$key}] updated successfully.");

        return self::SUCCESS;
    }
}
