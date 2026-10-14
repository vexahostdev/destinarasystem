<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicDestinationResource extends JsonResource
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
            'cover_url' => $coverUrl,
            'price_from' => (int) ($priceFrom ?? 0),
            'rating_avg' => (float) ($this->rating_avg ?? 0),
            'rating_count' => (int) ($this->rating_count ?? 0),
            'suitable_for' => $this->suitable_for ?? [],
            'is_featured' => (bool) $this->is_featured,
            'detail_url' => url('/destinasi/'.$this->slug),
            'booking_url' => url('/buyer/booking/create?destinasi='.$this->slug),
        ];
    }
}
