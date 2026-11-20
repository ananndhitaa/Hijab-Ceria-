<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;

class DateTimeFormatter
{
    /**
     * Default display timezone (WIB / Asia/Jakarta).
     */
    public static function displayTimezone(): string
    {
        return config('app.display_timezone', 'Asia/Jakarta');
    }

    /**
     * Convert any DateTime/Carbon/string instance to the application display timezone (Asia/Jakarta).
     * Preserves the exact moment in time (instant) without manual hour arithmetic.
     *
     * @param DateTimeInterface|string|null $date
     * @return Carbon|null
     */
    public static function toDisplayTimezone(DateTimeInterface|string|null $date): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date->copy()->setTimezone(static::displayTimezone());
        }

        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date)->setTimezone(static::displayTimezone());
        }

        return Carbon::parse($date)->setTimezone(static::displayTimezone());
    }

    /**
     * Format a timestamp into medium date and time with timezone suffix (e.g., "30 Sep 2026, 11:00 WIB").
     */
    public static function formatDateTime(
        DateTimeInterface|string|null $date,
        string $format = 'd M Y, H:i',
        bool $withTzSuffix = true,
        string $empty = '-'
    ): string {
        $tzDate = static::toDisplayTimezone($date);
        if (! $tzDate) {
            return $empty;
        }

        $formatted = $tzDate->translatedFormat($format);

        return $withTzSuffix ? "{$formatted} WIB" : $formatted;
    }

    /**
     * Format a timestamp into long Indonesian date and time (e.g., "30 September 2026, 11:00 WIB").
     */
    public static function formatLongDateTime(
        DateTimeInterface|string|null $date,
        bool $withTzSuffix = true,
        string $empty = '-'
    ): string {
        return static::formatDateTime($date, 'd F Y, H:i', $withTzSuffix, $empty);
    }

    /**
     * Format a timestamp into compact numeric date and time (e.g., "30/09/2026 11:00 WIB").
     */
    public static function formatShortDateTime(
        DateTimeInterface|string|null $date,
        bool $withTzSuffix = true,
        string $empty = '-'
    ): string {
        $tzDate = static::toDisplayTimezone($date);
        if (! $tzDate) {
            return $empty;
        }

        $formatted = $tzDate->format('d/m/Y H:i');

        return $withTzSuffix ? "{$formatted} WIB" : $formatted;
    }

    /**
     * Format time only in display timezone (e.g., "11:00 WIB" or "11:00").
     */
    public static function formatTime(
        DateTimeInterface|string|null $date,
        bool $withTzSuffix = true,
        string $empty = '-'
    ): string {
        $tzDate = static::toDisplayTimezone($date);
        if (! $tzDate) {
            return $empty;
        }

        $formatted = $tzDate->format('H:i');

        return $withTzSuffix ? "{$formatted} WIB" : $formatted;
    }

    /**
     * Format date part of a timestamp converted to display timezone (e.g., "30/09/2026").
     * Useful for audit timestamps where we want the local calendar date of the event.
     */
    public static function formatDateWib(
        DateTimeInterface|string|null $date,
        string $format = 'd/m/Y',
        string $empty = '-'
    ): string {
        $tzDate = static::toDisplayTimezone($date);
        if (! $tzDate) {
            return $empty;
        }

        return $tzDate->format($format);
    }

    /**
     * Format a DATE-only business field (e.g. order_date, purchase_date, expense_date).
     * Strictly avoids timezone conversion so calendar dates never shift.
     */
    public static function formatDate(
        DateTimeInterface|string|null $date,
        string $format = 'd M Y',
        string $empty = '-'
    ): string {
        if ($date === null || $date === '') {
            return $empty;
        }

        if ($date instanceof CarbonInterface) {
            return $date->translatedFormat($format);
        }

        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date)->translatedFormat($format);
        }

        return Carbon::parse($date)->translatedFormat($format);
    }
}
