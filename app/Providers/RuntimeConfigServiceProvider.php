<?php

namespace App\Providers;

use App\Domain\Settings\SettingsRepository;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class RuntimeConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class, function () {
            return new SettingsRepository();
        });
    }

    public function boot(SettingsRepository $settings): void
    {
        // Apply runtime configuration on boot
        $this->applyRuntimeConfig($settings);

        // Long-running queue workers must reapply settings before each job
        Queue::before(function () use ($settings) {
            $this->applyRuntimeConfig($settings);
        });
    }

    /**
     * Apply runtime settings to Laravel configuration.
     */
    protected function applyRuntimeConfig(SettingsRepository $settings): void
    {
        try {
            // Check if settings table exists to avoid errors during initial migrations
            if (! Schema::hasTable('settings')) {
                return;
            }

            // Apply Email / SMTP settings
            $email = $settings->group('email');

            if (! empty($email['smtp_host'])) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $email['smtp_host'],
                    'mail.mailers.smtp.port' => (int) ($email['smtp_port'] ?? 587),
                    'mail.mailers.smtp.encryption' => $email['smtp_encryption'] === 'null' ? null : $email['smtp_encryption'],
                    'mail.mailers.smtp.username' => $email['smtp_username'] ?? null,
                    'mail.mailers.smtp.password' => $email['smtp_password'] ?? null,
                    'mail.from.address' => $email['mail_from_address'] ?? 'notifikasi@destinara.my.id',
                    'mail.from.name' => $email['mail_from_name'] ?? 'Destinara',
                ]);
            }

            // Apply General Company settings
            $general = $settings->group('general');
            if (! empty($general['company_name'])) {
                config(['app.name' => $general['company_name']]);
            }
        } catch (\Throwable $e) {
            // Silently ignore during bootstrap if DB is unavailable
        }
    }
}
