<?php

namespace App\Enums;

enum ExpenseType: string
{
    case MARKETING     = 'MARKETING';
    case OPERATING     = 'OPERATING';
    case INTEREST      = 'INTEREST';
    case TAX           = 'TAX';
    case DEPRECIATION  = 'DEPRECIATION';
    case AMORTIZATION  = 'AMORTIZATION';

    public function label(): string
    {
        return match($this) {
            self::MARKETING    => 'Marketing',
            self::OPERATING    => 'Operasional',
            self::INTEREST     => 'Bunga',
            self::TAX          => 'Pajak',
            self::DEPRECIATION => 'Depresiasi',
            self::AMORTIZATION => 'Amortisasi',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::MARKETING    => 'badge-purple',
            self::OPERATING    => 'badge-primary',
            self::INTEREST     => 'badge-warning',
            self::TAX          => 'badge-cyan',
            self::DEPRECIATION => 'badge-secondary',
            self::AMORTIZATION => 'badge-secondary',
        };
    }

    public function isEbitdaExcluded(): bool
    {
        // Biaya ini TIDAK termasuk dalam EBITDA (di-add back)
        return in_array($this, [
            self::INTEREST,
            self::TAX,
            self::DEPRECIATION,
            self::AMORTIZATION,
        ]);
    }
}
