<?php

namespace App\Enums;

enum AccountType: string
{
    case BUYER = 'buyer';
    case PARTNER = 'partner';
    case INTERNAL = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::BUYER => 'Institusi / Pembeli',
            self::PARTNER => 'Mitra Pengelola Destinasi',
            self::INTERNAL => 'Tim Internal Destinara',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BUYER => 'blue',
            self::PARTNER => 'emerald',
            self::INTERNAL => 'purple',
        };
    }
}
