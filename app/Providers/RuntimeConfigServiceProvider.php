<?php

namespace App\Providers;

use App\Domain\Settings\SettingsRepository;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class RuntimeConfigServiceProvider extends ServiceProvider
{
    protected ?string $initialMailer = null;

    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class, function () {
            return new SettingsRepository;
        });

        $this->initialMailer = config('mail.default', 'log');
    }

    public function boot(SettingsRepository $settings): void
    {
        // Apply runtime configuration on boot
        $this->applyRuntimeConfig($settings);

        // Long-running queue workers must reapply settings and purge stale smtp before each job
        Queue::before(function () use ($settings) {
            $this->applyRuntimeConfig($settings);
            if (! app()->environment('testing')) {
                Mail::purge('smtp');
            }
        });
    }

    /**
     * Apply runtime settings to Laravel configuration.
     */
    public function applyRuntimeConfig(SettingsRepository $settings): void
    {
        try {
            // Check if settings table exists to avoid errors during initial migrations
            if (! Schema::hasTable('settings')) {
                return;
            }

            // Apply Email / SMTP settings
            $email = $settings->group('email');

            $hasCompleteSmtp = ! empty($email['smtp_host'])
                && ! empty($email['smtp_username'])
                && ! empty($email['smtp_password']);

            if ($hasCompleteSmtp) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $email['smtp_host'],
                    'mail.mailers.smtp.port' => (int) ($email['smtp_port'] ?? 587),
                    'mail.mailers.smtp.encryption' => ($email['smtp_encryption'] ?? 'tls') === 'null' ? null : ($email['smtp_encryption'] ?? 'tls'),
                    'mail.mailers.smtp.username' => $email['smtp_username'],
                    'mail.mailers.smtp.password' => $email['smtp_password'],
                    'mail.from.address' => ! empty($email['mail_from_address']) ? $email['mail_from_address'] : config('mail.from.address', 'notifikasi@destinara.my.id'),
                    'mail.from.name' => ! empty($email['mail_from_name']) ? $email['mail_from_name'] : config('mail.from.name', 'Destinara'),
                ]);
            } else {
                config([
                    'mail.default' => $this->initialMailer ?? config('mail.default', 'log'),
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
