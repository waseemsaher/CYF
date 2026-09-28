<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Settings\Services\ContentBlockService;
use Illuminate\Console\Command;

class ContentBlockSetCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'content-block:set
                            {key : Content block key (e.g. terms_and_conditions, privacy_policy, refund_policy)}
                            {--ar= : Path to file containing Arabic content}
                            {--en= : Path to file containing English content (optional; falls back to Arabic if omitted)}';

    /**
     * @var string
     */
    protected $description = 'Load text from files into a localized content block';

    public function handle(ContentBlockService $contentBlockService): int
    {
        $key = trim((string) $this->argument('key'));
        $arFile = $this->option('ar');
        $enFile = $this->option('en');

        if (! $arFile) {
            $this->error('The --ar option is required and must specify a readable file.');

            return self::FAILURE;
        }

        $arPath = (string) $arFile;
        if (! file_exists($arPath) || ! is_readable($arPath)) {
            $this->error("Arabic content file not found or not readable: [{$arPath}]");

            return self::FAILURE;
        }

        $arContent = file_get_contents($arPath);
        if ($arContent === false) {
            $this->error("Failed to read Arabic file: [{$arPath}]");

            return self::FAILURE;
        }

        $enContent = $arContent;
        if ($enFile) {
            $enPath = (string) $enFile;
            if (! file_exists($enPath) || ! is_readable($enPath)) {
                $this->error("English content file not found or not readable: [{$enPath}]");

                return self::FAILURE;
            }

            $readEn = file_get_contents($enPath);
            if ($readEn === false) {
                $this->error("Failed to read English file: [{$enPath}]");

                return self::FAILURE;
            }
            $enContent = $readEn;
        }

        $block = $contentBlockService->set($key, [
            'ar' => trim($arContent),
            'en' => trim($enContent),
        ]);

        activity('content_block')
            ->performedOn($block)
            ->withProperties([
                'key' => $key,
                'has_en_file' => ! empty($enFile),
            ])
            ->log('content_block_updated_from_file');

        $this->info("Content block [{$key}] successfully updated from file(s).");

        return self::SUCCESS;
    }
}
