<?php

namespace App\Enums;

enum OrderStatus: string
{
    case KOMUNIKASI = 'KOMUNIKASI';
    case DEAL       = 'DEAL';
    case BAHAN      = 'BAHAN';
    case PRINTING   = 'PRINTING';
    case PRODUKSI   = 'PRODUKSI';
    case QC         = 'QC';
    case PACKING    = 'PACKING';
    case DIKIRIM    = 'DIKIRIM';
    case SELESAI    = 'SELESAI';

    public function label(): string
    {
        return match($this) {
            self::KOMUNIKASI => 'Komunikasi',
            self::DEAL       => 'Deal',
            self::BAHAN      => 'Bahan',
            self::PRINTING   => 'Printing',
            self::PRODUKSI   => 'Produksi',
            self::QC         => 'QC',
            self::PACKING    => 'Packing',
            self::DIKIRIM    => 'Dikirim',
            self::SELESAI    => 'Selesai',
        };
    }

    /**
     * Label yang ditampilkan ke customer (lebih friendly)
     */
    public function customerLabel(): string
    {
        return match($this) {
            self::KOMUNIKASI => 'Pesanan Diterima',
            self::DEAL       => 'Dikonfirmasi',
            self::BAHAN      => 'Bahan Disiapkan',
            self::PRINTING   => 'Printing',
            self::PRODUKSI   => 'Produksi',
            self::QC         => 'QC',
            self::PACKING    => 'Dikemas',
            self::DIKIRIM    => 'Dikirim',
            self::SELESAI    => 'Selesai',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::KOMUNIKASI => '#786B72',
            self::DEAL       => '#B52F74',
            self::BAHAN      => '#D97706',
            self::PRINTING   => '#7C3AED',
            self::PRODUKSI   => '#2563EB',
            self::QC         => '#0891B2',
            self::PACKING    => '#059669',
            self::DIKIRIM    => '#D96B9B',
            self::SELESAI    => '#16A34A',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::KOMUNIKASI => 'bg-amber-50 text-amber-700 border-amber-200',
            self::DEAL       => 'bg-purple-50 text-purple-700 border-purple-200',
            self::BAHAN      => 'bg-rose-50 text-rose-700 border-rose-200',
            self::PRINTING   => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
            self::PRODUKSI   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::QC         => 'bg-sky-50 text-sky-700 border-sky-200',
            self::PACKING    => 'bg-[#FDF2F7] text-[#8E1B5C] border-[#EBDDE4]',
            self::DIKIRIM    => 'bg-teal-50 text-teal-700 border-teal-200',
            self::SELESAI    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        };
    }

    public function next(): ?self
    {
        return match($this) {
            self::KOMUNIKASI => self::DEAL,
            self::DEAL       => self::BAHAN,
            self::BAHAN      => self::PRINTING,
            self::PRINTING   => self::PRODUKSI,
            self::PRODUKSI   => self::QC,
            self::QC         => self::PACKING,
            self::PACKING    => self::DIKIRIM,
            self::DIKIRIM    => self::SELESAI,
            self::SELESAI    => null,
        };
    }

    public static function kanbanColumns(): array
    {
        return self::cases();
    }
}
