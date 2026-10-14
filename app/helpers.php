<?php

use App\Domain\Settings\SettingsRepository;

if (! function_exists('setting')) {
    /**
     * Get or set a system setting.
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $repository = app(SettingsRepository::class);

        if ($key === null) {
            return $repository;
        }

        return $repository->get($key, $default);
    }
}
