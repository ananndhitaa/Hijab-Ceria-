<?php

namespace App\Enums;

enum PasswordResetStatus: string
{
    case PENDING   = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case REJECTED  = 'REJECTED';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Menunggu',
            self::COMPLETED => 'Selesai',
            self::REJECTED  => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING   => 'amber',
            self::COMPLETED => 'green',
            self::REJECTED  => 'red',
        };
    }
}
