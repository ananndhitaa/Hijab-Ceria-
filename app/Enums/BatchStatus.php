<?php

namespace App\Enums;

enum BatchStatus: string
{
    case BAHAN      = 'BAHAN';
    case PRINTING   = 'PRINTING';
    case PRODUKSI   = 'PRODUKSI';
    case QC         = 'QC';
    case PACKING         = 'PACKING';
    case SELESAI         = 'SELESAI';
    case SELESAI_PARSIAL = 'SELESAI_PARSIAL';
    case CANCELLED       = 'CANCELLED';

    public function label(): string
    {
        return match($this) {
            self::BAHAN           => 'Bahan',
            self::PRINTING        => 'Printing',
            self::PRODUKSI        => 'Produksi',
            self::QC              => 'QC',
            self::PACKING         => 'Packing',
            self::SELESAI         => 'Batch Selesai',
            self::SELESAI_PARSIAL => 'Selesai Parsial',
            self::CANCELLED       => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::BAHAN           => 'bg-rose-50 text-rose-700 border-rose-200',
            self::PRINTING        => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
            self::PRODUKSI        => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::QC              => 'bg-sky-50 text-sky-700 border-sky-200',
            self::PACKING         => 'bg-[#FDF2F7] text-[#8E1B5C] border-[#EBDDE4]',
            self::SELESAI         => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::SELESAI_PARSIAL => 'bg-amber-50 text-amber-800 border-amber-200',
            self::CANCELLED       => 'bg-red-50 text-red-700 border-red-200',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::SELESAI, self::SELESAI_PARSIAL, self::CANCELLED], true);
    }
}
