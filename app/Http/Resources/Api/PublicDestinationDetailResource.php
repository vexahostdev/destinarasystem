<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicDestinationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $coverUrl = null;
        if ($this->cover_image) {
            $coverUrl = str_starts_with($this->cover_image, 'http')
                ? $this->cover_image
                : url($this->cover_image);
        }

        $priceFrom = $this->price_from;
        if ($priceFrom === null && $this->relationLoaded('packages')) {
            $priceFrom = $this->packages->min('price_per_pax');
        }

        // Facilities (list of names)
        $facilities = $this->relationLoaded('facilities')
            ? $this->facilities->pluck('name')->values()->all()
            : $this->facilities()->pluck('name')->values()->all();

        // Gallery URLs
        $gallery = $this->relationLoaded('media') ? $this->media : $this->media()->get();
        $galleryUrls = $gallery->map(function ($item) {
            return str_starts_with($item->path, 'http') ? $item->path : url($item->path);
        })->values()->all();

        // Packages
        $packagesList = $this->relationLoaded('packages') ? $this->packages : $this->packages()->get();
        $packagesData = $packagesList->map(function ($pkg) {
            return [
                'id' => $pkg->id,
                'name' => $pkg->name,
                'duration' => $pkg->duration_days ? "{$pkg->duration_days} Hari" : '-',
                'duration_days' => $pkg->duration_days,
                'price_per_pax' => (int) $pkg->price_per_pax,
                'min_pax' => (int) $pkg->min_pax,
                'max_pax' => (int) $pkg->max_pax,
                'includes' => $pkg->includes ?? [],
            ];
        })->values()->all();

        // Latest 5 published reviews
        $reviewsList = $this->relationLoaded('reviews')
            ? $this->reviews->take(5)
            : $this->reviews()->take(5)->get();

        $reviewsData = $reviewsList->map(function ($rev) {
            return [
                'rating' => (int) $rev->rating,
                'comment' => $rev->comment,
                'user_name' => $rev->institution?->institution_name ?? 'Institusi Pendidikan',
                'created_at' => $rev->created_at?->toIso8601String(),
                'reply' => $rev->partner_reply ? [
                    'comment' => $rev->partner_reply,
                    'replied_at' => $rev->replied_at?->toIso8601String(),
                ] : null,
            ];
        })->values()->all();

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => [
                'slug' => $this->category?->slug,
                'name' => $this->category?->name,
            ],
            'province' => $this->province?->name ?? (string) $this->province_id,
            'city' => $this->city,
            'short_description' => $this->short_description ?? '',
            'description' => $this->description ?? '',
            'educational_highlights' => $this->educational_highlights ?? [],
            'facilities' => $facilities,
            'cover_url' => $coverUrl,
            'gallery_urls' => $galleryUrls,
            'price_from' => (int) ($priceFrom ?? 0),
            'rating_avg' => (float) ($this->rating_avg ?? 0),
            'rating_count' => (int) ($this->rating_count ?? 0),
            'suitable_for' => $this->suitable_for ?? [],
            'is_featured' => (bool) $this->is_featured,
            'detail_url' => url('/destinasi/' . $this->slug),
            'booking_url' => url('/buyer/booking/create?destinasi=' . $this->slug),
            'packages' => $packagesData,
            'reviews' => $reviewsData,
        ];
    }
}
