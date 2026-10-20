<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Audit v11 K-12: cookie sesi otomatis secure di production/staging bila SESSION_SECURE_COOKIE tidak diisi.
 */
class SessionCookieSecurityTest extends TestCase
{
    private function sessionConfigWith(array $env): array
    {
        $backup = [];
        foreach ($env as $key => $value) {
            $backup[$key] = [$_SERVER[$key] ?? null, $_ENV[$key] ?? null, getenv($key)];
            if ($value === null) {
                unset($_SERVER[$key], $_ENV[$key]);
                putenv($key);
            } else {
                $_SERVER[$key] = $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        try {
            return require config_path('session.php');
        } finally {
            foreach ($backup as $key => [$server, $envValue, $putenv]) {
                $server === null ? $_SERVER[$key] = null : $_SERVER[$key] = $server;
                if ($server === null) {
                    unset($_SERVER[$key]);
                }
                if ($envValue === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $envValue;
                }
                $putenv === false ? putenv($key) : putenv("{$key}={$putenv}");
            }
        }
    }

    public function test_session_cookie_is_secure_by_default_in_production_and_staging(): void
    {
        foreach (['production', 'staging'] as $environment) {
            $config = $this->sessionConfigWith(['APP_ENV' => $environment, 'SESSION_SECURE_COOKIE' => null]);
            $this->assertTrue($config['secure'], "Cookie harus secure di {$environment}");
        }
    }

    public function test_session_cookie_is_not_forced_secure_locally_and_env_override_wins(): void
    {
        $this->assertFalse($this->sessionConfigWith(['APP_ENV' => 'local', 'SESSION_SECURE_COOKIE' => null])['secure']);
        $this->assertFalse($this->sessionConfigWith(['APP_ENV' => 'production', 'SESSION_SECURE_COOKIE' => 'false'])['secure']);
        $this->assertTrue($this->sessionConfigWith(['APP_ENV' => 'local', 'SESSION_SECURE_COOKIE' => 'true'])['secure']);
    }
}
