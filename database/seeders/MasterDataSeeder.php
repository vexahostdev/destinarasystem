<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\CancellationPolicy;
use App\Models\CancellationPolicyTier;
use App\Models\DestinationCategory;
use App\Models\Facility;
use App\Models\InstitutionType;
use App\Models\OrganizationType;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Destination Categories
        $categories = [
            ['name' => 'Desa Wisata', 'slug' => 'desa-wisata', 'icon' => 'home', 'description' => 'Desa wisata berbasis komunitas dan kearifan lokal.', 'sort_order' => 1],
            ['name' => 'Taman Nasional', 'slug' => 'taman-nasional', 'icon' => 'tree', 'description' => 'Kawasan konservasi alam, flora, dan fauna hayati.', 'sort_order' => 2],
            ['name' => 'Situs Budaya', 'slug' => 'situs-budaya', 'icon' => 'landmark', 'description' => 'Cagar budaya, sejarah, candi, dan museum.', 'sort_order' => 3],
            ['name' => 'Lainnya', 'slug' => 'lainnya', 'icon' => 'compass', 'description' => 'Kategori tapak edukasi alternatif lainnya.', 'sort_order' => 4],
        ];
        foreach ($categories as $cat) {
            DestinationCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 2. Facilities
        $facilities = [
            ['name' => 'Toilet', 'icon' => 'toilet', 'sort_order' => 1],
            ['name' => 'Parkir Bus', 'icon' => 'bus', 'sort_order' => 2],
            ['name' => 'Aula', 'icon' => 'building', 'sort_order' => 3],
            ['name' => 'Penginapan', 'icon' => 'bed', 'sort_order' => 4],
            ['name' => 'Mushola', 'icon' => 'moon', 'sort_order' => 5],
            ['name' => 'Area Makan', 'icon' => 'utensils', 'sort_order' => 6],
            ['name' => 'Pemandu Lokal', 'icon' => 'user-check', 'sort_order' => 7],
            ['name' => 'P3K', 'icon' => 'cross', 'sort_order' => 8],
            ['name' => 'Listrik', 'icon' => 'zap', 'sort_order' => 9],
            ['name' => 'Sinyal Seluler', 'icon' => 'signal', 'sort_order' => 10],
        ];
        foreach ($facilities as $fac) {
            Facility::updateOrCreate(['name' => $fac['name']], $fac);
        }

        // 3. 38 Provinces
        $provinces = [
            ['name' => 'Aceh', 'code' => 'AC'],
            ['name' => 'Sumatera Utara', 'code' => 'SU'],
            ['name' => 'Sumatera Barat', 'code' => 'SB'],
            ['name' => 'Riau', 'code' => 'RI'],
            ['name' => 'Kepulauan Riau', 'code' => 'KR'],
            ['name' => 'Jambi', 'code' => 'JA'],
            ['name' => 'Sumatera Selatan', 'code' => 'SS'],
            ['name' => 'Kepulauan Bangka Belitung', 'code' => 'BB'],
            ['name' => 'Bengkulu', 'code' => 'BE'],
            ['name' => 'Lampung', 'code' => 'LA'],
            ['name' => 'DKI Jakarta', 'code' => 'JK'],
            ['name' => 'Jawa Barat', 'code' => 'JB'],
            ['name' => 'Banten', 'code' => 'BT'],
            ['name' => 'Jawa Tengah', 'code' => 'JT'],
            ['name' => 'DI Yogyakarta', 'code' => 'YO'],
            ['name' => 'Jawa Timur', 'code' => 'JI'],
            ['name' => 'Bali', 'code' => 'BA'],
            ['name' => 'Nusa Tenggara Barat', 'code' => 'NB'],
            ['name' => 'Nusa Tenggara Timur', 'code' => 'NT'],
            ['name' => 'Kalimantan Barat', 'code' => 'KB'],
            ['name' => 'Kalimantan Tengah', 'code' => 'KT'],
            ['name' => 'Kalimantan Selatan', 'code' => 'KS'],
            ['name' => 'Kalimantan Timur', 'code' => 'KI'],
            ['name' => 'Kalimantan Utara', 'code' => 'KU'],
            ['name' => 'Sulawesi Utara', 'code' => 'SA'],
            ['name' => 'Gorontalo', 'code' => 'GO'],
            ['name' => 'Sulawesi Tengah', 'code' => 'ST'],
            ['name' => 'Sulawesi Barat', 'code' => 'SR'],
            ['name' => 'Sulawesi Selatan', 'code' => 'SN'],
            ['name' => 'Sulawesi Tenggara', 'code' => 'SG'],
            ['name' => 'Maluku', 'code' => 'MA'],
            ['name' => 'Maluku Utara', 'code' => 'MU'],
            ['name' => 'Papua', 'code' => 'PA'],
            ['name' => 'Papua Barat', 'code' => 'PB'],
            ['name' => 'Papua Barat Daya', 'code' => 'PD'],
            ['name' => 'Papua Selatan', 'code' => 'PS'],
            ['name' => 'Papua Tengah', 'code' => 'PT'],
            ['name' => 'Papua Pegunungan', 'code' => 'PE'],
        ];
        foreach ($provinces as $prov) {
            Province::updateOrCreate(['name' => $prov['name']], $prov);
        }

        // 4. Institution Types
        $institutionTypes = [
            ['name' => 'Sekolah (SD/SMP/SMA/SMK)', 'slug' => 'sekolah'],
            ['name' => 'Kampus / Universitas', 'slug' => 'kampus'],
            ['name' => 'Lembaga Riset / Peneliti', 'slug' => 'lembaga-riset'],
            ['name' => 'Lainnya', 'slug' => 'lainnya'],
        ];
        foreach ($institutionTypes as $type) {
            InstitutionType::updateOrCreate(['slug' => $type['slug']], $type);
        }

        // 5. Organization Types
        $orgTypes = [
            ['name' => 'Pokdarwis', 'slug' => 'pokdarwis'],
            ['name' => 'BUMDes', 'slug' => 'bumdes'],
            ['name' => 'Lembaga Adat', 'slug' => 'lembaga-adat'],
            ['name' => 'Koperasi', 'slug' => 'koperasi'],
            ['name' => 'Balai Taman Nasional', 'slug' => 'balai-taman-nasional'],
            ['name' => 'Swasta', 'slug' => 'swasta'],
            ['name' => 'Lainnya', 'slug' => 'lainnya'],
        ];
        foreach ($orgTypes as $org) {
            OrganizationType::updateOrCreate(['slug' => $org['slug']], $org);
        }

        // 6. Banks
        $banks = [
            ['name' => 'BCA', 'code' => '014'],
            ['name' => 'BRI', 'code' => '002'],
            ['name' => 'BNI', 'code' => '009'],
            ['name' => 'Mandiri', 'code' => '008'],
            ['name' => 'BSI (Bank Syariah Indonesia)', 'code' => '451'],
            ['name' => 'BTN', 'code' => '200'],
            ['name' => 'CIMB Niaga', 'code' => '022'],
            ['name' => 'Permata Bank', 'code' => '013'],
            ['name' => 'Bank Danamon', 'code' => '011'],
            ['name' => 'BPD DIY', 'code' => '112'],
        ];
        foreach ($banks as $b) {
            Bank::updateOrCreate(['code' => $b['code']], $b);
        }

        // 7. Cancellation Policy & Tiers
        $policy = CancellationPolicy::updateOrCreate(
            ['name' => 'Kebijakan Standar Destinara'],
            ['is_default' => true]
        );

        $tiers = [
            ['cancellation_policy_id' => $policy->id, 'min_days_before' => 14, 'refund_percent' => 100],
            ['cancellation_policy_id' => $policy->id, 'min_days_before' => 7, 'refund_percent' => 50],
            ['cancellation_policy_id' => $policy->id, 'min_days_before' => 0, 'refund_percent' => 0],
        ];

        foreach ($tiers as $tier) {
            CancellationPolicyTier::updateOrCreate([
                'cancellation_policy_id' => $tier['cancellation_policy_id'],
                'min_days_before' => $tier['min_days_before'],
            ], $tier);
        }
    }
}
