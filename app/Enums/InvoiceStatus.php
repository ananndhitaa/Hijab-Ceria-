<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case UNPAID    = 'UNPAID';
    case PARTIAL   = 'PARTIAL';
    case PAID      = 'PAID';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match($this) {
            self::UNPAID    => 'Belum Lunas',
            self::PARTIAL   => 'Bayar Sebagian',
            self::PAID      => 'Lunas',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::UNPAID    => 'bg-amber-50 text-amber-700 border-amber-200',
            self::PARTIAL   => 'bg-amber-50 text-amber-700 border-amber-200',
            self::PAID      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::CANCELLED => 'bg-red-50 text-red-700 border-red-200',
        };
    }
}
