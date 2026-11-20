<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING   = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Menunggu Konfirmasi',
            self::CONFIRMED => 'Dikonfirmasi',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING   => 'bg-amber-50 text-amber-700 border-amber-200',
            self::CONFIRMED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::CANCELLED => 'bg-red-50 text-red-700 border-red-200',
        };
    }
}
