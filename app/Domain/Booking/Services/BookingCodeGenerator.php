<?php

namespace App\Domain\Booking\Services;

use App\Models\Booking;

class BookingCodeGenerator
{
    /**
     * Crockford base32 alphabet (no I, L, O, U to avoid visual confusion).
     */
    protected const CROCKFORD_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Generate unique booking code in format: DST-{YYMMDD}-{5 Crockford chars} (SPEC §8.3).
     */
    public static function generate(): string
    {
        $prefix = 'DST-' . now()->format('ymd') . '-';
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $randomPart = '';
            for ($j = 0; $j < 5; $j++) {
                $randomPart .= self::CROCKFORD_ALPHABET[random_int(0, strlen(self::CROCKFORD_ALPHABET) - 1)];
            }

            $candidate = $prefix . $randomPart;

            if (! Booking::where('booking_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback with microsecond entropy if needed
        return $prefix . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}
