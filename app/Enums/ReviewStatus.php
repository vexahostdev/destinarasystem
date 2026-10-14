<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case PENDING = 'pending';
    case PUBLISHED = 'published';
    case HIDDEN = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Moderasi',
            self::PUBLISHED => 'Ditampilkan',
            self::HIDDEN => 'Disembunyikan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::PUBLISHED => 'emerald',
            self::HIDDEN => 'gray',
        };
    }
}
