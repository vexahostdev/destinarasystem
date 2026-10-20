<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case UNVERIFIED = 'unverified';
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::UNVERIFIED => 'Belum Terverifikasi',
            self::PENDING => 'Menunggu Verifikasi',
            self::VERIFIED => 'Terverifikasi',
            self::REJECTED => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UNVERIFIED => 'gray',
            self::PENDING => 'amber',
            self::VERIFIED => 'emerald',
            self::REJECTED => 'rose',
        };
    }
}
