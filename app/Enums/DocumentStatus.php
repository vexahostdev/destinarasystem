<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case FINAL = 'final';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::FINAL => 'Final / Sah',
            self::VOID => 'Batal / Void',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'amber',
            self::FINAL => 'emerald',
            self::VOID => 'rose',
        };
    }
}
