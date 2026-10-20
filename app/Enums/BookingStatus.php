<?php

namespace App\Enums;

enum BookingStatus: string
{
    case MENUNGGU_PEMBAYARAN = 'menunggu_pembayaran';
    case DIBAYAR = 'dibayar';
    case DIKONFIRMASI = 'dikonfirmasi';
    case BERLANGSUNG = 'berlangsung';
    case SELESAI = 'selesai';
    case BATAL = 'batal';
    case KEDALUWARSA = 'kedaluwarsa';

    public function label(): string
    {
        return match ($this) {
            self::MENUNGGU_PEMBAYARAN => 'Menunggu Pembayaran',
            self::DIBAYAR => 'Dibayar (Menunggu Konfirmasi)',
            self::DIKONFIRMASI => 'Dikonfirmasi',
            self::BERLANGSUNG => 'Berlangsung',
            self::SELESAI => 'Selesai',
            self::BATAL => 'Dibatalkan',
            self::KEDALUWARSA => 'Kedaluwarsa',
        };
    }

    /**
     * Status booking yang membuka kontak destinasi bagi buyer, sesuai setting contact_reveal_status (SPEC §8.5).
     *
     * @return array<int, self>
     */
    public static function contactRevealStatuses(): array
    {
        return match (setting('contact_reveal_status', 'dikonfirmasi')) {
            'dibayar' => [self::DIBAYAR, self::DIKONFIRMASI, self::BERLANGSUNG, self::SELESAI],
            'berlangsung' => [self::BERLANGSUNG, self::SELESAI],
            default => [self::DIKONFIRMASI, self::BERLANGSUNG, self::SELESAI],
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MENUNGGU_PEMBAYARAN => 'amber',
            self::DIBAYAR => 'blue',
            self::DIKONFIRMASI => 'indigo',
            self::BERLANGSUNG => 'purple',
            self::SELESAI => 'emerald',
            self::BATAL => 'rose',
            self::KEDALUWARSA => 'gray',
        };
    }
}
