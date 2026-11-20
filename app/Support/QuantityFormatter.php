<?php

namespace App\Support;

class QuantityFormatter
{
    /**
     * Format a decimal quantity string naturally for Indonesian user display without unnecessary trailing zeroes.
     * Operates strictly on string characters without float conversion to guarantee zero precision loss.
     *
     * Examples:
     *  - '1.0000'         => '1'
     *  - '1.2900'         => '1,29'
     *  - '0.0200'         => '0,02'
     *  - '0.5000'         => '0,5'
     *  - '12.7500'        => '12,75'
     *  - '1000.5'         => '1.000,5'
     *  - '18.0000', 'pcs' => '18 pcs'
     *  - '20.0000', 'm'   => '20 m'
     *  - '23.4000', 'm'   => '23,4 m'
     *  - '0.3960', 'roll' => '0,396 roll'
     *
     * @param string|int|float|null $qty
     * @param string|null $unit Optional unit to append cleanly
     * @return string
     */
    public static function format(string|int|float|null $qty, ?string $unit = null): string
    {
        if ($qty === null || $qty === '') {
            $res = '0';
            return $unit ? $res . ' ' . trim($unit) : $res;
        }

        $str = trim((string) $qty);
        $isNegative = str_starts_with($str, '-');
        if ($isNegative) {
            $str = ltrim($str, '-');
        }

        // Normalize decimal separator to dot if comma was passed
        $str = str_replace(',', '.', $str);

        $parts = explode('.', $str);
        $integerPart = preg_replace('/[^\d]/', '', $parts[0] ?? '0');
        $decimalPart = isset($parts[1]) ? preg_replace('/[^\d]/', '', $parts[1]) : '';

        // Normalize integer part (strip leading zeroes except if single '0')
        $integerPart = ltrim($integerPart, '0');
        if ($integerPart === '') {
            $integerPart = '0';
        }

        // Add Indonesian thousand separators (period) to integer part
        $formattedInteger = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integerPart);

        // Strip trailing zeroes from decimal part
        $decimalPart = rtrim($decimalPart, '0');

        if ($decimalPart === '') {
            $result = $formattedInteger;
        } else {
            $result = $formattedInteger . ',' . $decimalPart;
        }

        $formatted = $isNegative ? '-' . $result : $result;
        return $unit ? $formatted . ' ' . trim($unit) : $formatted;
    }

    /**
     * Format a quantity with an explicit sign ('+' or '-'), trimmed trailing zeros, Indonesian locale, and optional unit.
     *
     * Examples:
     *  - formatSigned('18.0000', '-', 'pcs')  => '-18 pcs'
     *  - formatSigned('20.0000', '+', 'm')    => '+20 m'
     *  - formatSigned('23.4000', '-', 'm')    => '-23,4 m'
     *  - formatSigned('0.3960', '-', 'roll')  => '-0,396 roll'
     *  - formatSigned('0.4000', '-', 'roll')  => '-0,4 roll'
     *  - formatSigned('17.6800', '-', 'm')    => '-17,68 m'
     *
     * @param string|int|float|null $qty
     * @param string $sign Explicit sign prefix ('+' or '-') or empty string
     * @param string|null $unit Optional unit to append cleanly
     * @return string
     */
    public static function formatSigned(string|int|float|null $qty, string $sign = '', ?string $unit = null): string
    {
        $formatted = self::format($qty);

        // If input already had negative sign, strip it first so $sign controls or stays negative
        if (str_starts_with($formatted, '-')) {
            $raw = substr($formatted, 1);
            $signPrefix = ($sign === '+') ? '+' : '-';
        } else {
            $raw = $formatted;
            $signPrefix = ($sign === '+' || $sign === '-') ? $sign : '';
        }

        $result = $signPrefix . $raw;
        return $unit ? $result . ' ' . trim($unit) : $result;
    }
}
