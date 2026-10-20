<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'period',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];
}
