<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case DRAFT     = 'DRAFT';
    case ORDERED   = 'ORDERED';
    case RECEIVED  = 'RECEIVED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match($this) {
            self::DRAFT     => 'Draft',
            self::ORDERED   => 'Dipesan',
            self::RECEIVED  => 'Diterima',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::DRAFT     => 'bg-slate-50 text-slate-700 border-slate-200',
            self::ORDERED   => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            self::RECEIVED  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::CANCELLED => 'bg-red-50 text-red-700 border-red-200',
        };
    }
}
