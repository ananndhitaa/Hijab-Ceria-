<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE   = 'ACTIVE';
    case INACTIVE = 'INACTIVE';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE   => 'Aktif',
            self::INACTIVE => 'Nonaktif',
        };
    }
}
