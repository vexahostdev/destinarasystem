<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database in strict production order (SPEC §19.3).
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            MasterDataSeeder::class,
            NotificationTemplateSeeder::class,
            DocumentTemplateSeeder::class,
            SiteContentSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
