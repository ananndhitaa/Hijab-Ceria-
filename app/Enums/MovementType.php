<?php

namespace App\Enums;

enum MovementType: string
{
    case IN         = 'IN';
    case OUT        = 'OUT';
    case ADJUSTMENT = 'ADJUSTMENT';

    public function label(): string
    {
        return match($this) {
            self::IN         => 'Stok Masuk',
            self::OUT        => 'Stok Keluar',
            self::ADJUSTMENT => 'Penyesuaian',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::IN         => 'badge-success',
            self::OUT        => 'badge-danger',
            self::ADJUSTMENT => 'badge-warning',
        };
    }
}
