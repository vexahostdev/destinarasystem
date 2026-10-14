<?php

namespace Database\Seeders;

use App\Domain\Settings\SettingsRepository;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $registry = config('destinara.settings', []);

        foreach ($registry as $item) {
            $setting = Setting::firstOrNew(['key' => $item['key']]);

            // If setting already exists, preserve its current value
            if ($setting->exists) {
                $setting->type = $item['type'];
                $setting->group = $item['group'];
                $setting->is_encrypted = $item['type'] === 'secret';
                $setting->save();
                continue;
            }

            $setting->type = $item['type'];
            $setting->group = $item['group'];
            $setting->is_encrypted = $item['type'] === 'secret';

            // For secrets, do not seed any mock credentials
            if ($item['type'] === 'secret') {
                $setting->setParsedValue(null);
            } else {
                $setting->setParsedValue($item['default'] ?? null);
            }

            $setting->save();
        }

        // Clear settings cache
        app(SettingsRepository::class)->clearCache();
    }
}
