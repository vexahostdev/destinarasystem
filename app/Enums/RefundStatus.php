<?php

namespace App\Enums;

enum RefundStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Diproses',
            self::PROCESSING => 'Sedang Diproses',
            self::COMPLETED => 'Selesai Ditransfer',
            self::REJECTED => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'emerald',
            self::REJECTED => 'rose',
        };
    }
}
