<?php

namespace Tests\Unit;

use App\Support\QuantityFormatter;
use PHPUnit\Framework\TestCase;

class QuantityFormatterTest extends TestCase
{
    public function test_formats_exact_integers(): void
    {
        $this->assertEquals('1', QuantityFormatter::format('1.0000'));
        $this->assertEquals('1', QuantityFormatter::format('1.00'));
        $this->assertEquals('1', QuantityFormatter::format('1'));
        $this->assertEquals('1', QuantityFormatter::format(1));
        $this->assertEquals('50', QuantityFormatter::format('50.0000'));
    }

    public function test_formats_zero_and_empty_values(): void
    {
        $this->assertEquals('0', QuantityFormatter::format('0'));
        $this->assertEquals('0', QuantityFormatter::format('0.0000'));
        $this->assertEquals('0', QuantityFormatter::format(0));
        $this->assertEquals('0', QuantityFormatter::format(null));
        $this->assertEquals('0', QuantityFormatter::format(''));
    }

    public function test_formats_user_specified_examples(): void
    {
        $this->assertEquals('1', QuantityFormatter::format('1.0000'));
        $this->assertEquals('1,29', QuantityFormatter::format('1.2900'));
        $this->assertEquals('0,02', QuantityFormatter::format('0.0200'));
        $this->assertEquals('0,5', QuantityFormatter::format('0.5000'));
        $this->assertEquals('12,75', QuantityFormatter::format('12.7500'));
    }

    public function test_formats_thousands_with_period_and_comma_decimal(): void
    {
        $this->assertEquals('1.000', QuantityFormatter::format('1000.0000'));
        $this->assertEquals('1.000,5', QuantityFormatter::format('1000.5000'));
        $this->assertEquals('1.234.567,89', QuantityFormatter::format('1234567.8900'));
    }

    public function test_preserves_high_precision_without_float_precision_loss(): void
    {
        $this->assertEquals('0,0005', QuantityFormatter::format('0.0005'));
        $this->assertEquals('0,123456', QuantityFormatter::format('0.123456'));
    }

    public function test_formats_negative_quantities(): void
    {
        $this->assertEquals('-1,29', QuantityFormatter::format('-1.2900'));
        $this->assertEquals('-5', QuantityFormatter::format('-5.0000'));
    }

    public function test_formats_with_units(): void
    {
        $this->assertEquals('18 pcs', QuantityFormatter::format('18.0000', 'pcs'));
        $this->assertEquals('256 pcs', QuantityFormatter::format('256.0000', 'pcs'));
        $this->assertEquals('20 m', QuantityFormatter::format('20.0000', 'm'));
        $this->assertEquals('23,4 m', QuantityFormatter::format('23.4000', 'm'));
        $this->assertEquals('0,396 roll', QuantityFormatter::format('0.3960', 'roll'));
        $this->assertEquals('0,4 roll', QuantityFormatter::format('0.4000', 'roll'));
        $this->assertEquals('17,68 m', QuantityFormatter::format('17.6800', 'm'));
    }

    public function test_formats_signed_movements(): void
    {
        $this->assertEquals('-18 pcs', QuantityFormatter::formatSigned('18.0000', '-', 'pcs'));
        $this->assertEquals('-18 pcs', QuantityFormatter::formatSigned('-18.0000', '-', 'pcs'));
        $this->assertEquals('+20 m', QuantityFormatter::formatSigned('20.0000', '+', 'm'));
        $this->assertEquals('+100 pcs', QuantityFormatter::formatSigned('100.0000', '+', 'pcs'));
        $this->assertEquals('-23,4 m', QuantityFormatter::formatSigned('23.4000', '-', 'm'));
        $this->assertEquals('-0,396 roll', QuantityFormatter::formatSigned('0.3960', '-', 'roll'));
        $this->assertEquals('-0,4 roll', QuantityFormatter::formatSigned('0.4000', '-', 'roll'));
        $this->assertEquals('-17,68 m', QuantityFormatter::formatSigned('17.6800', '-', 'm'));
    }

    public function test_formats_before_and_after_values(): void
    {
        $this->assertEquals('274', QuantityFormatter::format('274.0000'));
        $this->assertEquals('256', QuantityFormatter::format('256.0000'));
        $this->assertEquals('41,08', QuantityFormatter::format('41.0800'));
        $this->assertEquals('17,68', QuantityFormatter::format('17.6800'));
        $this->assertEquals('83,36', QuantityFormatter::format('83.3600'));
        $this->assertEquals('82,96', QuantityFormatter::format('82.9600'));
    }
}
