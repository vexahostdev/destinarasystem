<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('destinara.superadmin.name', 'Super Admin');
        $email = config('destinara.superadmin.email');
        $password = config('destinara.superadmin.password');

        if (empty($email) || empty($password)) {
            throw new RuntimeException(
                'SUPERADMIN_EMAIL dan SUPERADMIN_PASSWORD wajib diatur pada berkas .env sebelum menjalankan seeder (SPEC §2.2, §19.3).'
            );
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'account_type' => AccountType::INTERNAL,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['super_admin']);
    }
}
