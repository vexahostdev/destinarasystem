<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'key',
        'title',
        'body',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
