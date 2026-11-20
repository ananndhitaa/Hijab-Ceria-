<?php

namespace App\Enums;

enum PurchaseApprovalStatus: string
{
    case NOT_SUBMITTED      = 'NOT_SUBMITTED';
    case PENDING_APPROVAL   = 'PENDING_APPROVAL';
    case APPROVED           = 'APPROVED';
    case REVISION_REQUIRED  = 'REVISION_REQUIRED';
    case REJECTED           = 'REJECTED';

    public function label(): string
    {
        return match($this) {
            self::NOT_SUBMITTED     => 'Belum Diajukan',
            self::PENDING_APPROVAL  => 'Menunggu Persetujuan',
            self::APPROVED          => 'Disetujui',
            self::REVISION_REQUIRED => 'Perlu Revisi',
            self::REJECTED          => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::NOT_SUBMITTED     => 'bg-slate-50 text-slate-700 border-slate-200',
            self::PENDING_APPROVAL  => 'bg-amber-50 text-amber-700 border-amber-200',
            self::APPROVED          => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::REVISION_REQUIRED => 'bg-orange-50 text-orange-700 border-orange-200',
            self::REJECTED          => 'bg-red-50 text-red-700 border-red-200',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }
}
