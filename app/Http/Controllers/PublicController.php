<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\DestinationStatus;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    /**
     * 1. Halaman Beranda (Dialihkan ke Katalog Destinasi).
     */
    public function home()
    {
        return redirect()->route('destinasi.index');
    }

    /**
     * 2. Halaman Direktori Destinasi (Katalog Publik).
     */
    public function destinasi(Request $request)
    {
        return view('destinasi.index');
    }

    /**
     * 3. Halaman Detail Destinasi.
     */
    public function showDestinasi(string $slug)
    {
        $destination = Destination::with([
            'partner.partnerProfile',
            'category',
            'province',
            'cancellationPolicy.tiers',
            'packages',
            'addons',
            'media',
            'facilities',
            'reviews.user',
            'contacts',
            'slots' => fn ($q) => $q->where('date', '>=', now()->toDateString())->orderBy('date')->limit(30),
        ])
            ->where('slug', $slug)
            ->where('status', DestinationStatus::PUBLISHED)
            ->firstOrFail();

        // Reveal rule SPEC §8.5
        $canRevealContacts = false;
        if (auth()->check()) {
            $user = auth()->user();
            $canRevealContacts = Booking::where('user_id', $user->id)
                ->where('destination_id', $destination->id)
                ->whereIn('status', [BookingStatus::DIKONFIRMASI, BookingStatus::BERLANGSUNG])
                ->exists() || $user->hasRole('admin') || $user->hasRole('super_admin');
        }

        $minPrice = $destination->packages->min('price_per_pax') ?? 0;
        $gallery = $destination->media->isNotEmpty()
            ? $destination->media->map(fn ($m) => asset('storage/'.$m->file_path))->all()
            : ['https://images.unsplash.com/photo-1596402184320-417e7178b2cd?auto=format&fit=crop&w=1200&q=80'];

        $destinationData = [
            'id' => $destination->id,
            'name' => $destination->name,
            'slug' => $destination->slug,
            'city' => $destination->city,
            'address' => $destination->address,
            'province' => $destination->province?->name ?? $destination->city,
            'category' => $destination->category?->name ?? 'Wisata Edukasi',
            'highlights' => Str::limit($destination->short_description ?? $destination->description ?? 'Destinasi Edukasi Lapangan Terverifikasi', 200) ?: 'Destinasi Edukasi Lapangan Terverifikasi',
            'description' => $destination->description,
            'price' => $minPrice,
            'price_formatted' => 'Rp '.number_format($minPrice, 0, ',', '.'),
            'capacity' => $destination->default_daily_capacity ?? 100,
            'rating' => $destination->reviews->count() > 0 ? number_format($destination->rating, 1) : null,
            'reviews_count' => $destination->reviews->count(),
            'gallery' => $gallery,
            'image' => $gallery[0],
            'suitable_for' => is_array($destination->educational_focus)
                ? $destination->educational_focus
                : (json_decode($destination->educational_focus ?? '[]', true) ?: ['Studi Lapang', 'P5 Kurikulum Merdeka']),
            'facilities' => $destination->facilities->pluck('name')->all() ?: ['Toilet Bersih', 'Area Parkir Bus', 'Pendopo/Ruang Diskusi'],
            'packages' => $destination->packages,
            'cancellationPolicy' => $destination->cancellationPolicy,
            'slots' => $destination->slots,
            'reviews' => $destination->reviews,
            'contacts' => $destination->contacts->map(function ($c) use ($canRevealContacts) {
                return [
                    'name' => $c->name,
                    'role' => $c->role,
                    'phone' => $canRevealContacts
                        ? $c->phone
                        : (substr($c->phone, 0, 4).'-****-'.substr($c->phone, -4)),
                    'is_revealed' => $canRevealContacts,
                ];
            }),
        ];

        return view('destinasi.show', [
            'destination' => $destinationData,
            'model' => $destination,
        ]);
    }

    /**
     * 4. Halaman Alur & Cara Kerja Kemitraan.
     */
    public function caraKerja()
    {
        return view('cara-kerja');
    }

    /**
     * 5. Halaman Tanya Jawab / FAQ.
     */
    public function faq()
    {
        return view('public.faq');
    }

    /**
     * 6. Halaman Syarat & Ketentuan.
     */
    public function terms()
    {
        return view('public.terms');
    }

    /**
     * 7. Halaman Publik Verifikasi Keabsahan Dokumen (SPEC §11, §13).
     */
    public function verifyDocument(string $hash)
    {
        $document = Document::with(['booking.destination', 'booking.institution', 'payout'])
            ->where('verification_hash', $hash)
            ->first();

        return view('public.verify-document', compact('document', 'hash'));
    }
}
