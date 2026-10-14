<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReconciliation extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'period',
        'total_paid_in',
        'total_refunded',
        'total_platform_fee',
        'total_insurance',
        'total_commission',
        'total_discount',
        'total_payout_transferred',
        'total_payout_pending',
        'net_platform_revenue',
        'status',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'total_paid_in' => 'integer',
        'total_refunded' => 'integer',
        'total_platform_fee' => 'integer',
        'total_insurance' => 'integer',
        'total_commission' => 'integer',
        'total_discount' => 'integer',
        'total_payout_transferred' => 'integer',
        'total_payout_pending' => 'integer',
        'net_platform_revenue' => 'integer',
        'closed_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
