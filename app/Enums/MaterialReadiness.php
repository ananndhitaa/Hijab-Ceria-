<?php

namespace App\Enums;

enum MaterialReadiness: string
{
    case PERLU_PEMBELIAN   = 'PERLU_PEMBELIAN';
    case SIAP_DIALOKASIKAN = 'SIAP_DIALOKASIKAN';
    case TERALOKASI        = 'TERALOKASI';
    case BELUM_TERSEDIA    = 'BELUM_TERSEDIA';

    public function label(): string
    {
        return match($this) {
            self::PERLU_PEMBELIAN   => 'Perlu Pembelian',
            self::SIAP_DIALOKASIKAN => 'Siap Dialokasikan',
            self::TERALOKASI        => 'Teralokasi',
            self::BELUM_TERSEDIA    => 'Perencanaan Belum Tersedia',
        };
    }

    public function badgeText(): string
    {
        return match($this) {
            self::PERLU_PEMBELIAN   => 'PERLU PEMBELIAN',
            self::SIAP_DIALOKASIKAN => 'SIAP DIALOKASIKAN',
            self::TERALOKASI        => 'TERALOKASI',
            self::BELUM_TERSEDIA    => 'PERENCANAAN BELUM TERSEDIA',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PERLU_PEMBELIAN   => 'bg-amber-50 text-amber-700 border-amber-200',
            self::SIAP_DIALOKASIKAN => 'bg-[#F7E6EE] text-[#8E1B5C] border border-[#EBDDE4]',
            self::TERALOKASI        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::BELUM_TERSEDIA    => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}
