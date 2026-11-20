<?php

namespace App\Enums;

enum CostType: string
{
    case MATERIAL      = 'MATERIAL';
    case PRINTING      = 'PRINTING';
    case DIRECT_LABOR  = 'DIRECT_LABOR';
    case PACKAGING     = 'PACKAGING';
    case OTHER_DIRECT  = 'OTHER_DIRECT';

    public function label(): string
    {
        return match($this) {
            self::MATERIAL     => 'Biaya Material',
            self::PRINTING     => 'Biaya Printing',
            self::DIRECT_LABOR => 'Biaya Tenaga Kerja Langsung',
            self::PACKAGING    => 'Biaya Pengemasan',
            self::OTHER_DIRECT => 'Biaya Produksi Lainnya',
        };
    }
}
