<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvailabilitySlot extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'destination_id',
        'date',
        'capacity',
        'booked_count',
        'held_count',
        'status',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'capacity' => 'integer',
        'booked_count' => 'integer',
        'held_count' => 'integer',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BookingSlotAllocation::class);
    }

    /**
     * Sisa kapasitas = capacity - booked_count - held_count.
     */
    public function getRemainingCapacityAttribute(): int
    {
        return max(0, $this->capacity - $this->booked_count - $this->held_count);
    }

    public function isAvailable(int $pax = 1): bool
    {
        return $this->status === 'open' && $this->remaining_capacity >= $pax;
    }

    public function isFull(): bool
    {
        return $this->remaining_capacity <= 0;
    }
}
