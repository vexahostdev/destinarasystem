<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Dalam Antrean',
            self::SENT => 'Terkirim',
            self::DELIVERED => 'Diterima',
            self::READ => 'Dibaca',
            self::FAILED => 'Gagal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::QUEUED => 'amber',
            self::SENT => 'blue',
            self::DELIVERED => 'teal',
            self::READ => 'emerald',
            self::FAILED => 'rose',
        };
    }
}
