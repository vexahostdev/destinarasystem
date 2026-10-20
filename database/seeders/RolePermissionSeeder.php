<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = config('destinara.permissions', []);
        $excludedForOperasional = config('destinara.admin_operasional_excluded_permissions', []);

        // Create or update all permissions
        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        // Create Super Admin role (bypasses via Gate::before, but give all permissions as well)
        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        // Create Admin Operasional role
        $adminOperasional = Role::findOrCreate('admin_operasional', 'web');
        $operasionalPermissions = array_diff($permissions, $excludedForOperasional);
        $adminOperasional->syncPermissions($operasionalPermissions);
    }
}
