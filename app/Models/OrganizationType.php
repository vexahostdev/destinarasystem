<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationType extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function partnerProfiles(): HasMany
    {
        return $this->hasMany(PartnerProfile::class);
    }
}
