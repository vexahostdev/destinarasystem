<?php

namespace App\Enums;

enum AddonPricingType: string
{
    case PER_PAX = 'per_pax';
    case PER_GROUP = 'per_group';
    case PER_DAY = 'per_day';
    case PER_PAX_PER_DAY = 'per_pax_per_day';

    public function label(): string
    {
        return match ($this) {
            self::PER_PAX => 'Per Peserta (Pax)',
            self::PER_GROUP => 'Per Rombongan (Group)',
            self::PER_DAY => 'Per Hari',
            self::PER_PAX_PER_DAY => 'Per Peserta Per Hari',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PER_PAX => 'blue',
            self::PER_GROUP => 'emerald',
            self::PER_DAY => 'amber',
            self::PER_PAX_PER_DAY => 'indigo',
        };
    }
}
