<?php

namespace App\Models;

use App\Enums\DestinationStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Destination extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'partner_id',
        'category_id',
        'name',
        'slug',
        'province_id',
        'city',
        'address',
        'latitude',
        'longitude',
        'short_description',
        'description',
        'educational_highlights',
        'suitable_for',
        'has_permit_document',
        'cancellation_policy_id',
        'default_daily_capacity',
        'cover_image',
        'status',
        'is_featured',
        'rating_avg',
        'rating_count',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'suitable_for' => 'array',
            'has_permit_document' => 'boolean',
            'is_featured' => 'boolean',
            'status' => DestinationStatus::class,
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DestinationCategory::class, 'category_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function cancellationPolicy(): BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'destination_facility');
    }

    public function media(): HasMany
    {
        return $this->hasMany(DestinationMedia::class)->orderBy('sort_order');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(DestinationContact::class);
    }

    public function moderations(): HasMany
    {
        return $this->hasMany(DestinationModeration::class)->latest();
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function allPackages(): HasMany
    {
        return $this->hasMany(Package::class)->orderBy('sort_order');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(Addon::class)->where('is_active', true);
    }

    public function allAddons(): HasMany
    {
        return $this->hasMany(Addon::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    public function slots(): HasMany
    {
        return $this->availabilitySlots();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'published')->latest();
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function scopePublished($query)
    {
        return $query->where('status', DestinationStatus::PUBLISHED);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getPriceFromAttribute(): ?int
    {
        return $this->packages()->min('price_per_pax');
    }

    public function isPublished(): bool
    {
        return $this->status === DestinationStatus::PUBLISHED;
    }
}
