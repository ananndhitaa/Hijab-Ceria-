<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case PENDING   = 'PENDING';
    case PACKED    = 'PACKED';
    case SHIPPED   = 'SHIPPED';
    case DELIVERED = 'DELIVERED';
    case RETURNED  = 'RETURNED';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Menunggu',
            self::PACKED    => 'Dikemas',
            self::SHIPPED   => 'Dikirim',
            self::DELIVERED => 'Terkirim',
            self::RETURNED  => 'Dikembalikan',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING   => 'bg-amber-50 text-amber-700 border-amber-200',
            self::PACKED    => 'bg-[#FDF2F7] text-[#8E1B5C] border-[#EBDDE4]',
            self::SHIPPED   => 'bg-teal-50 text-teal-700 border-teal-200',
            self::DELIVERED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::RETURNED  => 'bg-red-50 text-red-700 border-red-200',
        };
    }
}
