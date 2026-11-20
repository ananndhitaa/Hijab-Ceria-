<?php

namespace Tests\Unit;

use App\Support\DateTimeFormatter;
use Carbon\Carbon;
use Tests\TestCase;

class DateTimeFormatterTest extends TestCase
{
    public function test_utc_04_00_displays_as_11_00_wib(): void
    {
        $utcTimestamp = '2026-09-30 04:00:00';

        $this->assertEquals('30 September 2026, 11:00 WIB', DateTimeFormatter::formatLongDateTime($utcTimestamp));
        $this->assertEquals('30 Sep 2026, 11:00 WIB', DateTimeFormatter::formatDateTime($utcTimestamp));
        $this->assertEquals('30/09/2026 11:00 WIB', DateTimeFormatter::formatShortDateTime($utcTimestamp));
        $this->assertEquals('11:00 WIB', DateTimeFormatter::formatTime($utcTimestamp));
        $this->assertEquals('30/09/2026', DateTimeFormatter::formatDateWib($utcTimestamp));
    }

    public function test_carbon_utc_instance_converts_to_asia_jakarta_without_mutating_original(): void
    {
        $originalUtc = Carbon::create(2026, 9, 30, 4, 0, 0, 'UTC');

        $formatted = DateTimeFormatter::formatLongDateTime($originalUtc);

        $this->assertEquals('30 September 2026, 11:00 WIB', $formatted);
        $this->assertEquals('UTC', $originalUtc->timezoneName);
        $this->assertEquals(4, $originalUtc->hour);
    }

    public function test_already_jakarta_timestamp_does_not_shift_hours(): void
    {
        $jakartaTime = Carbon::create(2026, 9, 30, 11, 0, 0, 'Asia/Jakarta');

        $this->assertEquals('30 September 2026, 11:00 WIB', DateTimeFormatter::formatLongDateTime($jakartaTime));
        $this->assertEquals('11:00 WIB', DateTimeFormatter::formatTime($jakartaTime));
    }

    public function test_date_only_format_remains_exact_calendar_date(): void
    {
        $dateOnlyString = '2026-09-30';
        $this->assertEquals('30 Sep 2026', DateTimeFormatter::formatDate($dateOnlyString, 'd M Y'));
        $this->assertEquals('30/09/2026', DateTimeFormatter::formatDate($dateOnlyString, 'd/m/Y'));

        $carbonDateOnly = Carbon::parse('2026-09-30');
        $this->assertEquals('30 Sep 2026', DateTimeFormatter::formatDate($carbonDateOnly, 'd M Y'));

        $this->assertEquals('12 Okt 2026', DateTimeFormatter::formatDate('2026-10-12', 'd M Y'));
        $this->assertEquals('08 Okt 2026', DateTimeFormatter::formatDate('2026-10-08', 'd M Y'));
        $this->assertEquals('08 Agt 2026', DateTimeFormatter::formatDate('2026-08-08', 'd M Y'));
    }

    public function test_late_night_utc_timestamp_crosses_to_next_wib_calendar_day(): void
    {
        // 23:30 UTC on 29 Sep 2026 is 06:30 WIB on 30 Sep 2026
        $lateNightUtc = '2026-09-29 23:30:00';

        $this->assertEquals('30/09/2026', DateTimeFormatter::formatDateWib($lateNightUtc));
        $this->assertEquals('30 September 2026, 06:30 WIB', DateTimeFormatter::formatLongDateTime($lateNightUtc));
    }

    public function test_null_or_empty_returns_placeholder(): void
    {
        $this->assertEquals('-', DateTimeFormatter::formatDateTime(null));
        $this->assertEquals('-', DateTimeFormatter::formatDateTime(''));
        $this->assertEquals('—', DateTimeFormatter::formatDateTime(null, 'd M Y, H:i', true, '—'));
        $this->assertEquals('-', DateTimeFormatter::formatDate(null));
    }

    public function test_no_manual_plus_seven_arithmetic_in_formatter_code(): void
    {
        $code = file_get_contents(app_path('Support/DateTimeFormatter.php'));

        $this->assertStringNotContainsString('addHours(7)', $code);
        $this->assertStringNotContainsString('+ 7', $code);
        $this->assertStringNotContainsString('+7', $code);
        $this->assertStringContainsString("setTimezone", $code);
    }
}
