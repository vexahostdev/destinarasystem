<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Bank;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\PartnerProfile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun dummy untuk setiap role selain super_admin. Hanya berjalan di environment lokal.
 *
 * Jalankan: php artisan db:seed --class=DummyAccountSeeder
 *
 * Kredensial (semua akun memakai password yang sama):
 *   admin_operasional : operasional@destinara.test
 *   mitra (partner)   : mitra@destinara.test
 *   institusi (buyer) : pembeli@destinara.test
 *   password          : DummyDestinara2026!
 */
class DummyAccountSeeder extends Seeder
{
    private const PASSWORD = 'DummyDestinara2026!';

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DummyAccountSeeder hanya dijalankan di environment local. Dilewati.');

            return;
        }

        // Pastikan role & permission sudah ada sebelum di-assign.
        $this->call(RolePermissionSeeder::class);

        $password = Hash::make(self::PASSWORD);

        // 1. Admin Operasional (staf internal)
        $operasional = User::updateOrCreate(
            ['email' => 'operasional@destinara.test'],
            [
                'name' => 'Admin Operasional Dummy',
                'phone' => '6281200000001',
                'password' => $password,
                'account_type' => AccountType::INTERNAL,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $operasional->syncRoles(['admin_operasional']);

        // 2. Mitra Pengelola Destinasi (partner) terverifikasi
        $mitra = User::updateOrCreate(
            ['email' => 'mitra@destinara.test'],
            [
                'name' => 'Mitra Dummy',
                'phone' => '6281200000002',
                'password' => $password,
                'account_type' => AccountType::PARTNER,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $bank = Bank::where('code', '014')->first();
        PartnerProfile::updateOrCreate(
            ['user_id' => $mitra->id],
            [
                'organization_name' => 'Pokdarwis Dummy Desa Wisata',
                'address' => 'Jl. Contoh No. 1',
                'province_id' => Province::where('code', 'YO')->value('id'),
                'city' => 'Sleman',
                'bank_id' => $bank?->id,
                'bank_account_number' => '1234567890',
                'bank_account_holder' => 'Mitra Dummy',
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]
        );

        // 3. Institusi / Pembeli (buyer) terverifikasi
        $pembeli = User::updateOrCreate(
            ['email' => 'pembeli@destinara.test'],
            [
                'name' => 'Pembeli Dummy',
                'phone' => '6281200000003',
                'password' => $password,
                'account_type' => AccountType::BUYER,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        Institution::updateOrCreate(
            ['user_id' => $pembeli->id],
            [
                'institution_name' => 'SMA Dummy Yogyakarta',
                'institution_type_id' => InstitutionType::where('slug', 'sekolah')->value('id'),
                'npsn' => '10000000',
                'address' => 'Jl. Contoh No. 2',
                'province_id' => Province::where('code', 'YO')->value('id'),
                'city' => 'Yogyakarta',
                'pic_name' => 'Pembeli Dummy',
                'pic_position' => 'Guru',
                'pic_phone' => '6281200000003',
                'pic_email' => 'pembeli@destinara.test',
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]
        );
    }
}
