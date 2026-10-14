<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use Auditable, HasFactory, SoftDeletes;

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

    public function validateFor(?int $amount = null, ?int $destinationId = null, ?int $userId = null): void
    {
        if (! $this->is_active) {
            throw new \InvalidArgumentException('Voucher tidak aktif.');
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            throw new \InvalidArgumentException('Voucher belum dapat digunakan.');
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            throw new \InvalidArgumentException('Masa berlaku voucher telah habis.');
        }

        if ($this->quota !== null && $this->quota > 0 && $this->used_count >= $this->quota) {
            throw new \InvalidArgumentException('Kuota pemakaian voucher telah habis.');
        }

        if ($amount !== null && $this->min_transaction > 0 && $amount < $this->min_transaction) {
            $minFormatted = number_format($this->min_transaction, 0, ',', '.');
            throw new \InvalidArgumentException("Total transaksi belum mencapai batas minimal voucher (Rp {$minFormatted}).");
        }

        if (! empty($this->destination_ids) && $destinationId !== null) {
            $destIds = is_array($this->destination_ids) ? $this->destination_ids : (json_decode($this->destination_ids, true) ?? []);
            if (! in_array($destinationId, $destIds)) {
                throw new \InvalidArgumentException('Voucher tidak berlaku untuk destinasi ini.');
            }
        }

        if ($userId && $this->per_user_limit !== null && $this->per_user_limit > 0) {
            $userUsage = $this->redemptions()->where('user_id', $userId)->count();
            if ($userUsage >= $this->per_user_limit) {
                throw new \InvalidArgumentException('Anda telah mencapai batas penggunaan voucher ini.');
            }
        }
    }

    public function isValid(?int $amount = null, ?int $destinationId = null, ?int $userId = null, ?string &$reason = null): bool
    {
        try {
            $this->validateFor($amount, $destinationId, $userId);

            return true;
        } catch (\InvalidArgumentException $e) {
            $reason = $e->getMessage();

            return false;
        }
    }

    public function isValidFor(int $amount, ?int $destinationId = null, ?int $userId = null, ?string &$reason = null): bool
    {
        return $this->isValid($amount, $destinationId, $userId, $reason);
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
