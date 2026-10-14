<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'name',
        'slug',
        'category',
        'province',
        'city',
        'price_per_pax',
        'description',
        'educational_highlights',
        'facilities',
        'suitable_for',
        'has_permit_document',
        'cover_image',
        'gallery_images',
        'status',
    ];

    protected $casts = [
        'facilities' => 'array',
        'suitable_for' => 'array',
        'gallery_images' => 'array',
        'has_permit_document' => 'boolean',
        'price_per_pax' => 'integer',
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function contacts()
    {
        return $this->hasMany(DestinationContact::class);
    }

    public function slots()
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Accessor aliases for seamless public view compatibility
    public function getPriceAttribute()
    {
        return $this->price_per_pax ?? 45000;
    }

    public function getPriceFormattedAttribute()
    {
        return 'Rp ' . number_format($this->price_per_pax ?? 45000, 0, ',', '.');
    }

    public function getImageAttribute()
    {
        if (!empty($this->cover_image)) {
            return str_starts_with($this->cover_image, 'http') ? $this->cover_image : asset($this->cover_image);
        }
        return asset('assets/img/hd/hero-fieldwork.jpg');
    }

    public function getGalleryAttribute()
    {
        if (!empty($this->gallery_images) && is_array($this->gallery_images)) {
            return array_map(function($img) {
                return str_starts_with($img, 'http') ? $img : asset($img);
            }, $this->gallery_images);
        }
        return [$this->image];
    }

    public function getHighlightsAttribute()
    {
        return $this->educational_highlights ?? $this->description;
    }

    public function getRatingAttribute()
    {
        return '4.9';
    }

    public function getReviewsCountAttribute()
    {
        return 98;
    }

    public function getCategoryCodeAttribute()
    {
        return $this->category;
    }
}
