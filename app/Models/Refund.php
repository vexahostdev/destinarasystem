<?php

namespace App\Models;

use App\Enums\RefundStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'booking_id',
        'payment_id',
        'amount',
        'percent',
        'reason',
        'status',
        'buyer_bank_id',
        'buyer_account_number',
        'buyer_account_holder',
        'proof_path',
        'processed_by',
        'processed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount' => 'integer',
            'percent' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function buyerBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'buyer_bank_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === RefundStatus::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === RefundStatus::COMPLETED;
    }
}
