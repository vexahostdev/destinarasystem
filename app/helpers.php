<?php

use App\Domain\Settings\SettingsRepository;

if (! function_exists('setting')) {
    /**
     * Get or set a system setting.
     *
     * Usage:
     * - setting('key', 'default') -> get
     * - setting(['key' => 'val']) -> bulk set
     * - setting() -> SettingsRepository instance
     */
    function setting(string|array|null $key = null, mixed $default = null): mixed
    {
        $repository = app(SettingsRepository::class);

        if ($key === null) {
            return $repository;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $repository->set($k, $v);
            }
            return true;
        }

        return $repository->get($key, $default);
    }
}
