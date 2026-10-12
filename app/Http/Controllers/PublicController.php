<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Data master destinasi tapak untuk User No Login
     */
    /**
     * Data master destinasi tapak untuk User No Login
     * Menggunakan foto HD autentik dari comprodestinara
     */
    protected function getDestinations()
    {
        return [
            [
                'id' => 1,
                'name' => 'Kampung Kopi Kintamani & Subak Abian',
                'slug' => 'kampung-kopi-kintamani',
                'category' => 'Kedaulatan Pangan',
                'category_code' => 'kedaulatan_pangan',
                'province' => 'Bali',
                'city' => 'Kab. Bangli',
                'price' => 45000,
                'price_formatted' => 'Rp 45.000',
                'image' => asset('assets/img/hd/dest-kintamani.jpg'),
                'gallery' => [
                    asset('assets/img/hd/dest-kintamani.jpg'),
                    asset('assets/img/hd/desa-serambi.jpg'),
                    asset('assets/img/hd/sekolah-briefing.jpg'),
                    asset('assets/img/hd/desa-bukit.jpg'),
                    asset('assets/img/hd/sekolah-diskusi.jpg'),
                ],
                'suitable_for' => ['Study Tour Sekolah & Proyek P5', 'Agroekologi & Subak'],
                'highlights' => 'Eksplorasi filosofi Tri Hita Karana melalui sistem Subak Abian yang memadukan perkebunan kopi arabika organik dengan spiritualitas tanah vulkanik Batur.',
                'facilities' => ['Ruang Belajar Balai Desa', 'Pemandu Sesepuh Subak', 'Kebun Kopi Percontohan', 'Dapur Olahan Tradisional', 'Jalur Agrowisata'],
                'rating' => '4.9',
                'reviews_count' => 128,
                'capacity' => 150,
            ],
            [
                'id' => 2,
                'name' => 'Hutan Adat Wonosadi & Konservasi Karst',
                'slug' => 'hutan-adat-wonosadi',
                'category' => 'Taman Nasional & Hutan',
                'category_code' => 'taman_nasional',
                'province' => 'D.I. Yogyakarta',
                'city' => 'Kab. Gunungkidul',
                'price' => 40000,
                'price_formatted' => 'Rp 40.000',
                'image' => asset('assets/img/hd/dest-wonosadi.jpg'),
                'gallery' => [
                    asset('assets/img/hd/dest-wonosadi.jpg'),
                    asset('assets/img/hd/peneliti-bleberan.jpg'),
                    asset('assets/img/hd/hero-home.jpg'),
                    asset('assets/img/hd/peneliti-wawancara.jpg'),
                    asset('assets/img/hd/sekolah-lembar.jpg'),
                ],
                'suitable_for' => ['Penelitian Hayati & Ekologi', 'Ekskursi Sains Lapangan'],
                'highlights' => 'Laboratorium konservasi mata air karst berbasis hukum adat Sadumuk Bathuk Sanyari Bumi yang menjaga ketahanan air lereng perbukitan.',
                'facilities' => ['Pondok Peneliti Lapangan', 'Pemandu Ranger Adat', 'Jalur Ekspedisi Hutan', 'Peralatan Sample Karst'],
                'rating' => '4.8',
                'reviews_count' => 94,
                'capacity' => 80,
            ],
            [
                'id' => 3,
                'name' => 'Desa Sasak Sade & Budaya Tenun',
                'slug' => 'desa-sasak-sade',
                'category' => 'Desa Wisata & Adat',
                'category_code' => 'desa_wisata',
                'province' => 'Nusa Tenggara Barat',
                'city' => 'Lombok Tengah',
                'price' => 45000,
                'price_formatted' => 'Rp 45.000',
                'image' => asset('assets/img/hd/dest-sasak-sade.jpg'),
                'gallery' => [
                    asset('assets/img/hd/dest-sasak-sade.jpg'),
                    asset('assets/img/hd/peneliti-sade.jpg'),
                    asset('assets/img/hd/story-sasak.jpg'),
                    asset('assets/img/hd/about-musyawarah.jpg'),
                    asset('assets/img/hd/desa-lurah.jpg'),
                ],
                'suitable_for' => ['Study Tour Budaya', 'Etnomatematika Kriya'],
                'highlights' => 'Pusat studi arsitektur vernakular Bale Tani tahan gempa dan transmisi lisan pola geometris motif tenun ikat warisan leluhur Sasak.',
                'facilities' => ['Bale Sanggar Pertemuan', 'Pemandu Budaya Lokal', 'Area Lokakarya Tenun', 'Konsumsi Kuliner Tradisional'],
                'rating' => '4.9',
                'reviews_count' => 115,
                'capacity' => 120,
            ],
            [
                'id' => 4,
                'name' => 'Desa Adat Waerebo & Arsitektur Mbaru Niang',
                'slug' => 'desa-adat-waerebo',
                'category' => 'Desa Wisata & Adat',
                'category_code' => 'desa_wisata',
                'province' => 'Nusa Tenggara Timur',
                'city' => 'Manggarai, Flores',
                'price' => 65000,
                'price_formatted' => 'Rp 65.000',
                'image' => asset('assets/img/hd/dest-waerebo.jpg'),
                'gallery' => [
                    asset('assets/img/hd/dest-waerebo.jpg'),
                    asset('assets/img/hd/desa-bukit.jpg'),
                    asset('assets/img/hd/desa-serambi.jpg'),
                    asset('assets/img/hd/peneliti-lontar.jpg'),
                    asset('assets/img/hd/about-jurnal.jpg'),
                ],
                'suitable_for' => ['Riset Antropologi & Arsitektur', 'Ekskursi Budaya'],
                'highlights' => 'Tujuh rumah utama Mbaru Niang kerucut bertingkat lima yang merefleksikan kosmologi perlindungan alam, klan komunal, dan cadangan benih pangan jagung.',
                'facilities' => ['Homestay Mbaru Niang', 'Upacara Penerimaan Pa\'u', 'Pemandu Warga Adat', 'Dapur Komunal Lembah'],
                'rating' => '5.0',
                'reviews_count' => 88,
                'capacity' => 60,
            ],
            [
                'id' => 5,
                'name' => 'Konservasi Hutan Ulin Sebulu',
                'slug' => 'konservasi-hutan-ulin-sebulu',
                'category' => 'Taman Nasional & Hutan',
                'category_code' => 'taman_nasional',
                'province' => 'Kalimantan Timur',
                'city' => 'Kutai Kartanegara',
                'price' => 55000,
                'price_formatted' => 'Rp 55.000',
                'image' => asset('assets/img/hd/story-ulin.jpg'),
                'gallery' => [
                    asset('assets/img/hd/story-ulin.jpg'),
                    asset('assets/img/hd/hero-about.jpg'),
                    asset('assets/img/hd/sekolah-mou.jpg'),
                    asset('assets/img/hd/sekolah-diskusi.jpg'),
                    asset('assets/img/hd/peneliti-wawancara.jpg'),
                ],
                'suitable_for' => ['Penelitian Hayati & Ekologi', 'Studi Konstruksi Vernakular'],
                'highlights' => 'Laboratorium botani pohon ulin (kayu besi) endemik Kalimantan, uji arsitektur pasak geser tanpa paku, dan konservasi kanopi purba.',
                'facilities' => ['Pondok Stasiun Lapangan', 'Pemandu Ranger Hutan', 'Titik Pengamatan Satwa', 'Peralatan Sample Lapangan'],
                'rating' => '4.8',
                'reviews_count' => 92,
                'capacity' => 75,
            ],
            [
                'id' => 6,
                'name' => 'Sanggar Pewarna Alami Tenun Ikat Sikka',
                'slug' => 'sanggar-tenun-ikat-sikka',
                'category' => 'Situs Budaya & Kriya',
                'category_code' => 'situs_budaya',
                'province' => 'Nusa Tenggara Timur',
                'city' => 'Kab. Sikka, Flores',
                'price' => 50000,
                'price_formatted' => 'Rp 50.000',
                'image' => asset('assets/img/hd/story-sikka.jpg'),
                'gallery' => [
                    asset('assets/img/hd/story-sikka.jpg'),
                    asset('assets/img/hd/peneliti-ciptagelar.jpg'),
                    asset('assets/img/hd/peneliti-lontar.jpg'),
                    asset('assets/img/hd/desa-serambi.jpg'),
                    asset('assets/img/hd/sekolah-lembar.jpg'),
                ],
                'suitable_for' => ['Study Tour Seni & Budaya', 'Riset Botani Pewarna Alami'],
                'highlights' => 'Dokumentasi 28 tanaman pewarna botani purba (tarum, mengkudu, kunyit) dan geometri motif tenun sakral Maumere berwawasan lingkungan.',
                'facilities' => ['Sanggar Belajar Menenun', 'Dapur Ekstraksi Warna', 'Koleksi Kain Etnis', 'Akomodasi Desa'],
                'rating' => '5.0',
                'reviews_count' => 96,
                'capacity' => 60,
            ],
            [
                'id' => 7,
                'name' => 'Kampung Nelayan Bahari Wakatobi',
                'slug' => 'kampung-nelayan-wakatobi',
                'category' => 'Taman Nasional & Hutan',
                'category_code' => 'taman_nasional',
                'province' => 'Sulawesi Tenggara',
                'city' => 'Wakatobi',
                'price' => 60000,
                'price_formatted' => 'Rp 60.000',
                'image' => asset('assets/img/hd/dest-wakatobi.jpg'),
                'gallery' => [
                    asset('assets/img/hd/dest-wakatobi.jpg'),
                    asset('assets/img/hd/hero-home.jpg'),
                    asset('assets/img/hd/sekolah-briefing.jpg'),
                    asset('assets/img/hd/contact-map.jpg'),
                    asset('assets/img/hd/desa-bukit.jpg'),
                ],
                'suitable_for' => ['Oseanografi & Bahari', 'Ekskursi Terumbu Karang'],
                'highlights' => 'Kearifan sistem zonasi laut tradisional suku Bajo dan masyarakat pulau karang dalam merawat keanekaragaman segitiga terumbu karang dunia.',
                'facilities' => ['Pusat Observasi Terumbu', 'Perahu Edukasi Karang', 'Peralatan Snorkeling Riset', 'Homestay Terapung'],
                'rating' => '4.9',
                'reviews_count' => 82,
                'capacity' => 50,
            ],
            [
                'id' => 8,
                'name' => 'Kampung Adat Kasepuhan Ciptagelar',
                'slug' => 'kampung-adat-ciptagelar',
                'category' => 'Kedaulatan Pangan',
                'category_code' => 'kedaulatan_pangan',
                'province' => 'Jawa Barat',
                'city' => 'Sukabumi',
                'price' => 50000,
                'price_formatted' => 'Rp 50.000',
                'image' => asset('assets/img/hd/peneliti-ciptagelar.jpg'),
                'gallery' => [
                    asset('assets/img/hd/peneliti-ciptagelar.jpg'),
                    asset('assets/img/hd/about-musyawarah.jpg'),
                    asset('assets/img/hd/desa-lurah.jpg'),
                    asset('assets/img/hd/sekolah-diskusi.jpg'),
                    asset('assets/img/hd/about-jurnal.jpg'),
                ],
                'suitable_for' => ['Study Tour Ketahanan Pangan', 'Penelitian Agraria'],
                'highlights' => 'Sistem perladangan huma kuno dan lumbung padi leuit komunal yang mampu menyimpan ketahanan pangan ratusan tahun tanpa pestisida kimia.',
                'facilities' => ['Imah Gede Adat', 'Penginapan Rumah Warga', 'Bimbingan Kokolot Lembur', 'Dapur Komunal'],
                'rating' => '4.9',
                'reviews_count' => 104,
                'capacity' => 100,
            ],
        ];
    }

    /**
     * 1. Halaman Beranda (Dialihkan ke Katalog Destinasi karena landing page beranda berpusat di Destinara Compro)
     */
    public function home()
    {
        return redirect()->route('destinasi.index');
    }

    /**
     * 2. Halaman Direktori Destinasi (Katalog & Filter)
     */
    public function destinasi(Request $request)
    {
        $destinations = $this->getDestinations();

        // Search Query Filtering
        if ($request->filled('q')) {
            $query = strtolower($request->q);
            $destinations = array_filter($destinations, function($d) use ($query) {
                return str_contains(strtolower($d['name']), $query) || 
                       str_contains(strtolower($d['province']), $query) ||
                       str_contains(strtolower($d['city']), $query) ||
                       str_contains(strtolower($d['highlights']), $query);
            });
        }

        // Category Filtering
        if ($request->filled('category')) {
            $destinations = array_filter($destinations, function($d) use ($request) {
                return $d['category_code'] === $request->category;
            });
        }

        // Province Filtering
        if ($request->filled('province')) {
            $destinations = array_filter($destinations, function($d) use ($request) {
                return $d['province'] === $request->province;
            });
        }

        // Sorting
        $sort = $request->get('sort', 'popular');
        if ($sort === 'lowest_price') {
            usort($destinations, fn($a, $b) => $a['price'] <=> $b['price']);
        } elseif ($sort === 'highest_capacity') {
            usort($destinations, fn($a, $b) => $b['capacity'] <=> $a['capacity']);
        }

        return view('destinasi.index', [
            'destinations' => array_values($destinations),
            'total' => count($destinations),
            'categories' => [
                'desa_wisata' => 'Desa Wisata & Adat',
                'taman_nasional' => 'Taman Nasional & Hutan',
                'situs_budaya' => 'Situs Budaya & Kriya',
                'kedaulatan_pangan' => 'Kedaulatan Pangan',
            ],
            'provinces' => ['Bali', 'D.I. Yogyakarta', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Timur', 'Sulawesi Tenggara', 'Jawa Barat']
        ]);
    }

    /**
     * 3. Halaman Detail Destinasi
     */
    public function showDestinasi($slug)
    {
        $allDestinations = $this->getDestinations();
        $destination = collect($allDestinations)->firstWhere('slug', $slug) ?? $allDestinations[0];

        return view('destinasi.show', compact('destination'));
    }

    /**
     * 4. Halaman Alur & Cara Kerja Kemitraan
     */
    public function caraKerja()
    {
        return view('cara-kerja');
    }

    /**
     * 5. Halaman Pendaftaran Akun (Split-screen)
     */
    public function register()
    {
        if (auth()->check()) {
            $role = auth()->user()->role ?? 'buyer';
            return match($role) {
                'buyer' => redirect()->route('buyer.dashboard'),
                'mitra' => redirect()->route('mitra.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                'superadmin' => redirect()->route('superadmin.dashboard'),
                default => redirect()->route('buyer.dashboard'),
            };
        }

        return view('auth.register');
    }

    /**
     * 6. Halaman / Modal Masuk (Login)
     */
    public function login()
    {
        if (auth()->check()) {
            $role = auth()->user()->role ?? 'buyer';
            return match($role) {
                'buyer' => redirect()->route('buyer.dashboard'),
                'mitra' => redirect()->route('mitra.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                'superadmin' => redirect()->route('superadmin.dashboard'),
                default => redirect()->route('buyer.dashboard'),
            };
        }

        return view('auth.login');
    }
}
