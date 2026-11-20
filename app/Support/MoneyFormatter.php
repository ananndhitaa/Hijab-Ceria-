<?php

namespace App\Support;

class MoneyFormatter
{
    /**
     * Format a decimal string as Indonesian Rupiah without converting to float.
     * Operates strictly on string characters to preserve 100% precision for DECIMAL(18,2).
     *
     * @param string|int|float|null $amount
     * @param bool $withCents
     * @param string $prefix
     * @return string
     */
    public static function rupiah(string|int|float|null $amount, bool $withCents = false, string $prefix = 'Rp '): string
    {
        if ($amount === null || $amount === '') {
            return $prefix . '0';
        }

        $str = trim((string) $amount);
        $isNegative = str_starts_with($str, '-');
        if ($isNegative) {
            $str = ltrim($str, '-');
        }

        // Split integer and decimal parts
        $parts = explode('.', $str);
        $integerPart = preg_replace('/[^\d]/', '', $parts[0] ?? '0');
        $decimalPart = isset($parts[1]) ? preg_replace('/[^\d]/', '', $parts[1]) : '';

        // Normalize integer part (strip leading zeroes except if single '0')
        $integerPart = ltrim($integerPart, '0');
        if ($integerPart === '') {
            $integerPart = '0';
        }

        // Add thousands separator (period) to integer part using regex
        $formattedInteger = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integerPart);

        $result = $prefix . $formattedInteger;

        if ($withCents && $decimalPart !== '') {
            $decimalPart = str_pad(substr($decimalPart, 0, 2), 2, '0');
            $result .= ',' . $decimalPart;
        }

        return $isNegative ? '-' . $result : $result;
    }

    /**
     * Format a decimal string with thousand separators without 'Rp' prefix and without float conversion.
     *
     * @param string|int|float|null $amount
     * @param bool $withCents
     * @return string
     */
    public static function format(string|int|float|null $amount, bool $withCents = false): string
    {
        return self::rupiah($amount, $withCents, '');
    }
}
