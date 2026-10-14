<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CancellationPolicy extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function tiers(): HasMany
    {
        return $this->hasMany(CancellationPolicyTier::class)->orderBy('min_days_before', 'desc');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }
}
