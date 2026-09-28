<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class SettingsShowCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'settings:show
                            {group? : Filter settings by group (e.g. payment_methods, uploads)}';

    /**
     * @var string
     */
    protected $description = 'Display platform settings in a tabular view';

    public function handle(): int
    {
        $group = $this->argument('group');

        $query = Setting::query()->orderBy('group')->orderBy('key');

        if ($group) {
            $query->where('group', (string) $group);
        }

        $settings = $query->get();

        if ($settings->isEmpty()) {
            $this->warn($group ? "No settings found for group [{$group}]." : 'No settings configured.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($settings as $setting) {
            $val = $setting->getAttribute('value');
            $displayVal = is_array($val) || is_object($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string) $val;

            $rows[] = [
                'Group' => (string) $setting->getAttribute('group'),
                'Key' => (string) $setting->getAttribute('key'),
                'Value' => $displayVal,
            ];
        }

        $this->table(['Group', 'Key', 'Value'], $rows);

        return self::SUCCESS;
    }
}
