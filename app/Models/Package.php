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
}
