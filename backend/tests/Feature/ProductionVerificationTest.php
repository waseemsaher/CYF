<?php

declare(strict_types=1);

test('production:verify fails if telegram webhook secret is empty', function (): void {
    config()->set('telegram.webhook_secret', '');
    config()->set('app.debug', false);
    config()->set('app.key', 'base64:somevalidkey12345678901234567890123456=');

    $this->artisan('production:verify')
        ->assertFailed()
        ->expectsOutputToContain('TELEGRAM_WEBHOOK_SECRET is missing or empty');
});

test('production:verify passes when required security configurations are present', function (): void {
    config()->set('telegram.webhook_secret', 'strong_random_secret_token');
    config()->set('app.debug', false);
    config()->set('app.key', 'base64:somevalidkey12345678901234567890123456=');
    config()->set('cors.allowed_origins_patterns', []);

    $this->artisan('production:verify')
        ->assertSuccessful()
        ->expectsOutputToContain('Production environment verification passed');
});
