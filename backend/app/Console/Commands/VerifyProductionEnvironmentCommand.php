<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;

class VerifyProductionEnvironmentCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'production:verify';

    /**
     * @var string
     */
    protected $description = 'Verify that production security and environment configurations are strictly set';

    public function handle(): int
    {
        $this->info('Validating production environment configuration...');
        $errors = [];

        // 1. Verify Telegram Webhook Secret is present
        $webhookSecret = (string) config('telegram.webhook_secret', '');
        if ($webhookSecret === '') {
            $errors[] = 'TELEGRAM_WEBHOOK_SECRET is missing or empty. The webhook will fail closed and reject all requests.';
        }

        // 2. Verify APP_DEBUG is false
        if (config('app.debug') === true) {
            $errors[] = 'APP_DEBUG is enabled. Must be set to false in production.';
        }

        // 3. Verify APP_KEY is generated
        if (empty(config('app.key'))) {
            $errors[] = 'APP_KEY is missing.';
        }

        // 4. Verify CORS wildcard pattern is not active
        $corsPatterns = (array) config('cors.allowed_origins_patterns', []);
        if (in_array('#^https?://.*#', $corsPatterns, true)) {
            $errors[] = 'CORS allowed_origins_patterns contains permissive wildcard pattern.';
        }

        if (! empty($errors)) {
            $this->error('Production verification failed with the following critical security errors:');
            foreach ($errors as $error) {
                $this->error("  ✖ {$error}");
            }

            if (! app()->environment(['local', 'testing'])) {
                throw new RuntimeException('Production verification failed: '.implode('; ', $errors));
            }

            return self::FAILURE;
        }

        $this->info('✅ Production environment verification passed.');

        return self::SUCCESS;
    }
}
