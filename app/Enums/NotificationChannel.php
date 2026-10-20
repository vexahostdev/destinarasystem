<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case MAIL = 'mail';
    case WHATSAPP = 'whatsapp';
    case DATABASE = 'database';

    public function label(): string
    {
        return match ($this) {
            self::MAIL => 'Email',
            self::WHATSAPP => 'WhatsApp',
            self::DATABASE => 'In-App',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MAIL => 'blue',
            self::WHATSAPP => 'emerald',
            self::DATABASE => 'indigo',
        };
    }
}
