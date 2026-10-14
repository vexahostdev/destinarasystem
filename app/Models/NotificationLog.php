<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'event_key',
        'channel',
        'recipient',
        'subject',
        'body',
        'status',
        'provider_message_id',
        'error',
        'attempts',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRecipientNameAttribute(): ?string
    {
        return $this->notifiable?->name ?? $this->recipient;
    }

    public function getTargetAttribute(): ?string
    {
        return $this->recipient;
    }

    public function getTitleAttribute(): ?string
    {
        return $this->subject;
    }

    public function getPreviewTextAttribute(): ?string
    {
        return \Illuminate\Support\Str::limit(strip_tags((string) $this->body), 100);
    }

    public function getRetryCountAttribute(): int
    {
        return $this->attempts ?? 0;
    }
}
