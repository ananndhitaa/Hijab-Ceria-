<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN       = 'ADMIN';
    case GUDANG      = 'GUDANG';
    case PRODUKSI    = 'PRODUKSI';
    case OWNER       = 'OWNER';
    case CUSTOMER    = 'CUSTOMER';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN       => 'Admin',
            self::GUDANG      => 'Gudang',
            self::PRODUKSI    => 'Produksi',
            self::OWNER       => 'Owner',
            self::CUSTOMER    => 'Customer',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'indigo',
            self::ADMIN       => 'blue',
            self::GUDANG      => 'amber',
            self::PRODUKSI    => 'green',
            self::OWNER       => 'purple',
            self::CUSTOMER    => 'pink',
        };
    }

    public function isPlatformRole(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    public function isTenantRole(): bool
    {
        return $this !== self::SUPER_ADMIN;
    }
}
