<?php

namespace App\Support;

class PhoneNormalizer
{
    /**
     * Normalize Indonesian phone/WhatsApp numbers to canonical '08xxxxxxxxxx' format.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Keep only digits
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        // 628xxxxxxxx -> 08xxxxxxxx
        if (str_starts_with($digits, '628')) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /**
     * Format a normalized or raw phone number into readable Indonesian telephone notation.
     */
    public static function format(?string $phone): string
    {
        if (empty($phone)) {
            return '—';
        }

        $norm = self::normalize($phone);
        if (! $norm) {
            return $phone;
        }

        // Format 081234567890 into 0812-3456-7890 (or 4-4-X)
        if (strlen($norm) >= 10 && strlen($norm) <= 13) {
            $part1 = substr($norm, 0, 4);
            $part2 = substr($norm, 4, 4);
            $part3 = substr($norm, 8);
            return "{$part1}-{$part2}-{$part3}";
        }

        return $norm;
    }
}
