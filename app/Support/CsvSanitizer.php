<?php

namespace App\Support;

class CsvSanitizer
{
    /**
     * Escape spreadsheet formula injection characters (=, +, -, @, tab, cr) for textual values.
     * Numeric, numeric string, and safe values are preserved intact without modification.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function sanitize(mixed $value): mixed
    {
        if ($value === null) {
            return '';
        }

        if (!is_string($value) || is_numeric($value)) {
            return $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Sanitize an entire row array for CSV output.
     *
     * @param array $row
     * @return array
     */
    public static function sanitizeRow(array $row): array
    {
        return array_map([self::class, 'sanitize'], $row);
    }
}
