<?php

namespace App\Enums;

enum PaymentType: string
{
    case DP         = 'DP';
    case PELUNASAN  = 'PELUNASAN';
    case LAINNYA    = 'LAINNYA';

    public function label(): string
    {
        return match($this) {
            self::DP        => 'Uang Muka (DP)',
            self::PELUNASAN => 'Pelunasan',
            self::LAINNYA   => 'Lainnya',
        };
    }
}
