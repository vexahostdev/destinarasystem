<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Destination;

class PublicController extends Controller
{
    /**
     * 1. Halaman Beranda (Dialihkan ke Katalog Destinasi karena landing page beranda berpusat di Destinara Compro)
     */
    public function home()
    {
        return redirect()->route('destinasi.index');
    }

    /**
     * 2. Halaman Direktori Destinasi (Katalog & Filter Dinamis Basis Data)
     */
    public function destinasi(Request $request)
    {
        $query = Destination::where('status', 'published');

        // Search Query Filtering
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                  ->orWhere('province', 'like', "%{$q}%")
                  ->orWhere('city', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%")
                  ->orWhere('educational_highlights', 'like', "%{$q}%");
            });
        }

        // Category Filtering
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Province Filtering
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }

        // Sorting
        $sort = $request->get('sort', 'popular');
        if ($sort === 'lowest_price') {
            $query->orderBy('price_per_pax', 'asc');
        } elseif ($sort === 'highest_capacity') {
            $query->orderBy('capacity', 'desc');
        } else {
            $query->latest();
        }

        $destinations = $query->get();

        $provinces = Destination::where('status', 'published')
            ->pluck('province')
            ->unique()
            ->values()
            ->all();

        return view('destinasi.index', [
            'destinations' => $destinations,
            'total' => $destinations->count(),
            'categories' => [
                'desa_wisata' => 'Desa Wisata & Adat',
                'taman_nasional' => 'Taman Nasional & Hutan',
                'situs_budaya' => 'Situs Budaya & Kriya',
                'konservasi_alam' => 'Konservasi Alam',
                'kedaulatan_pangan' => 'Kedaulatan Pangan',
            ],
            'provinces' => $provinces,
        ]);
    }

    /**
     * 3. Halaman Detail Destinasi (Dinamis Basis Data)
     */
    public function showDestinasi($slug)
    {
        $destination = Destination::with(['partner.partnerProfile', 'contacts', 'slots'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

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
                'partner', 'mitra' => redirect()->route('mitra.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                'super_admin', 'superadmin' => redirect()->route('superadmin.dashboard'),
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
                'partner', 'mitra' => redirect()->route('mitra.dashboard'),
                'admin' => redirect()->route('admin.dashboard'),
                'super_admin', 'superadmin' => redirect()->route('superadmin.dashboard'),
                default => redirect()->route('buyer.dashboard'),
            };
        }

        return view('auth.login');
    }

    /**
     * 7. Halaman Publik Verifikasi Keabsahan Dokumen (SPEC §11, §13).
     */
    public function verifyDocument(string $hash)
    {
        $document = \App\Models\Document::with(['booking.destination', 'booking.institution', 'payout'])
            ->where('verification_hash', $hash)
            ->first();

        return view('public.verify-document', compact('document', 'hash'));
    }
}
