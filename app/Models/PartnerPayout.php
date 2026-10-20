<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PartnerPayout extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'payout_number',
        'partner_id',
        'booking_id',
        'gross_amount',
        'commission_amount',
        'net_amount',
        'status',
        'eligible_at',
        'transferred_at',
        'transfer_proof_path',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'processed_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'gross_amount' => 'integer',
            'commission_amount' => 'integer',
            'net_amount' => 'integer',
            'eligible_at' => 'datetime',
            'transferred_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function document(): HasOne
    {
        return $this->hasOne(Document::class, 'payout_id');
    }

    public function isPending(): bool
    {
        return $this->status === PayoutStatus::PENDING;
    }

    public function isTransferred(): bool
    {
        return $this->status === PayoutStatus::TRANSFERRED;
    }
}
