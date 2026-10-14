<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case TRANSFERRED = 'transferred';
    case ON_HOLD = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Jadwal',
            self::SCHEDULED => 'Terjadwal',
            self::TRANSFERRED => 'Ditransfer',
            self::ON_HOLD => 'Ditahan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::SCHEDULED => 'blue',
            self::TRANSFERRED => 'emerald',
            self::ON_HOLD => 'rose',
        };
    }
}
