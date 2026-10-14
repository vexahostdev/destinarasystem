<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'event_key',
        'channel',
        'audience',
        'subject',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_active' => 'boolean',
        ];
    }
}
