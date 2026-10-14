<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationPolicyTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancellation_policy_id',
        'min_days_before',
        'refund_percent',
    ];

    protected $casts = [
        'min_days_before' => 'integer',
        'refund_percent' => 'integer',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class, 'cancellation_policy_id');
    }
}
