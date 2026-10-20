<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'destination_id',
        'name',
        'slug',
        'inquiry_types',
        'duration_days',
        'price_per_pax',
        'min_pax',
        'max_pax',
        'min_participants',
        'max_participants',
        'description',
        'includes',
        'excludes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'inquiry_types' => 'array',
        'includes' => 'array',
        'excludes' => 'array',
        'duration_days' => 'integer',
        'price_per_pax' => 'integer',
        'min_pax' => 'integer',
        'max_pax' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getMinParticipantsAttribute(): int
    {
        return $this->min_pax ?? 1;
    }

    public function setMinParticipantsAttribute($value): void
    {
        $this->attributes['min_pax'] = (int) $value;
    }

    public function getMaxParticipantsAttribute(): int
    {
        return $this->max_pax ?? 500;
    }

    public function setMaxParticipantsAttribute($value): void
    {
        $this->attributes['max_pax'] = (int) $value;
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    protected static function booted(): void
    {
        static::saved(function (Package $package) {
            $package->syncDestinationSuitableFor();
        });

        static::deleted(function (Package $package) {
            $package->syncDestinationSuitableFor();
        });
    }

    public function syncDestinationSuitableFor(): void
    {
        if (! $this->destination_id) {
            return;
        }

        $destination = $this->destination ?? Destination::find($this->destination_id);
        if (! $destination) {
            return;
        }

        $activePackages = $destination->packages()->where('is_active', true)->get();
        $allInquiryTypes = [];
        foreach ($activePackages as $pkg) {
            $types = is_array($pkg->inquiry_types)
                ? $pkg->inquiry_types
                : (json_decode($pkg->inquiry_types ?? '[]', true) ?: []);
            foreach ($types as $t) {
                if (! empty($t)) {
                    $allInquiryTypes[] = $t;
                }
            }
        }

        $allInquiryTypes = array_values(array_unique($allInquiryTypes));
        if (empty($allInquiryTypes)) {
            $allInquiryTypes = ['study_tour'];
        }

        $destination->suitable_for = $allInquiryTypes;
        $destination->saveQuietly();
    }
}
