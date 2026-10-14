<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'code',
        'name',
        'type', // fixed, percent
        'value',
        'max_discount',
        'min_transaction',
        'quota',
        'used_count',
        'per_user_limit',
        'starts_at',
        'ends_at',
        'destination_ids',
        'is_active',
    ];

    protected $casts = [
        'value' => 'integer',
        'max_discount' => 'integer',
        'min_transaction' => 'integer',
        'quota' => 'integer',
        'used_count' => 'integer',
        'per_user_limit' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'destination_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isValidFor(int $amount, ?int $destinationId = null, ?int $userId = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        if ($this->quota > 0 && $this->used_count >= $this->quota) {
            return false;
        }

        if ($amount < $this->min_transaction) {
            return false;
        }

        if (! empty($this->destination_ids) && $destinationId) {
            if (! in_array($destinationId, $this->destination_ids)) {
                return false;
            }
        }

        if ($userId && $this->per_user_limit > 0) {
            $userRedemptions = $this->redemptions()->where('user_id', $userId)->count();
            if ($userRedemptions >= $this->per_user_limit) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(int $amount): int
    {
        if ($this->type === 'percent') {
            $discount = (int) round($amount * ($this->value / 100));
        } else {
            $discount = (int) $this->value;
        }

        if ($this->max_discount !== null && $this->max_discount > 0) {
            $discount = min($discount, $this->max_discount);
        }

        return min($discount, $amount);
    }
}
