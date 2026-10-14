<?php

namespace App\Domain\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsRepository
{
    protected const CACHE_KEY = 'destinara_system_settings';

    /**
     * Get setting value by key, with fallback to default from registry or parameter.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        // Check default in registry
        $registryItem = $this->findInRegistry($key);
        if ($registryItem !== null && array_key_exists('default', $registryItem)) {
            return $registryItem['default'];
        }

        return $default;
    }

    /**
     * Set setting value by key.
     */
    public function set(string $key, mixed $value): void
    {
        $registryItem = $this->findInRegistry($key);
        $type = $registryItem['type'] ?? 'string';
        $group = $registryItem['group'] ?? 'general';
        $isEncrypted = $type === 'secret';

        // If it's a secret and value is empty string, keep existing value
        if ($isEncrypted && ($value === null || $value === '')) {
            return;
        }

        $setting = Setting::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->group = $group;
        $setting->is_encrypted = $isEncrypted;
        $setting->setParsedValue($value);
        $setting->save();

        $this->clearCache();
    }

    /**
     * Get all cached settings parsed into key => value array.
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = [];
            $records = Setting::all();

            foreach ($records as $record) {
                $settings[$record->key] = $record->parsed_value;
            }

            return $settings;
        });
    }

    /**
     * Get settings by group.
     */
    public function group(string $group): array
    {
        $all = $this->all();
        $registry = config('destinara.settings', []);
        $groupSettings = [];

        foreach ($registry as $item) {
            if ($item['group'] === $group) {
                $groupSettings[$item['key']] = $all[$item['key']] ?? $item['default'] ?? null;
            }
        }

        return $groupSettings;
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Find item definition in registry.
     */
    public function findInRegistry(string $key): ?array
    {
        $registry = config('destinara.settings', []);
        foreach ($registry as $item) {
            if ($item['key'] === $key) {
                return $item;
            }
        }

        return null;
    }
}
