<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionType extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class);
    }
}
