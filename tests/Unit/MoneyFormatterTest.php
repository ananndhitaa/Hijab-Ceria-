<?php

namespace Tests\Unit;

use App\Support\MoneyFormatter;
use PHPUnit\Framework\TestCase;

class MoneyFormatterTest extends TestCase
{
    public function test_formats_zero(): void
    {
        $this->assertEquals('Rp 0', MoneyFormatter::rupiah('0'));
        $this->assertEquals('Rp 0', MoneyFormatter::rupiah('0.00'));
        $this->assertEquals('Rp 0', MoneyFormatter::rupiah(0));
        $this->assertEquals('Rp 0', MoneyFormatter::rupiah(null));
        $this->assertEquals('Rp 0', MoneyFormatter::rupiah(''));
    }

    public function test_formats_thousands(): void
    {
        $this->assertEquals('Rp 12.500', MoneyFormatter::rupiah('12500'));
        $this->assertEquals('Rp 12.500', MoneyFormatter::rupiah('12500.50'));
    }

    public function test_formats_millions(): void
    {
        $this->assertEquals('Rp 15.000.000', MoneyFormatter::rupiah('15000000.00'));
    }

    public function test_formats_decimal_18_2_scale_large_value_without_precision_loss(): void
    {
        // 16 integer digits + 2 decimals: 9999999999999999.99
        // Float conversion would lose precision, but string formatting retains exact digits
        $largeValue = '9999999999999999.99';
        $this->assertEquals('Rp 9.999.999.999.999.999', MoneyFormatter::rupiah($largeValue));
    }

    public function test_formats_with_cents_if_requested(): void
    {
        $this->assertEquals('Rp 12.500,50', MoneyFormatter::rupiah('12500.50', true));
        $this->assertEquals('Rp 12.500,05', MoneyFormatter::rupiah('12500.05', true));
    }

    public function test_formats_negative_values(): void
    {
        $this->assertEquals('-Rp 50.000', MoneyFormatter::rupiah('-50000.00'));
    }

    public function test_format_without_prefix(): void
    {
        $this->assertEquals('15.000.000', MoneyFormatter::format('15000000.00'));
    }
}
