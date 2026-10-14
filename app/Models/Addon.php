<?php

namespace App\Models;

use App\Enums\AddonPricingType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Addon extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'destination_id',
        'name',
        'description',
        'pricing_type',
        'price',
        'is_active',
    ];

    protected $casts = [
        'pricing_type' => AddonPricingType::class,
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
