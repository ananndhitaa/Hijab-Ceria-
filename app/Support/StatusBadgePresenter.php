<?php

namespace App\Support;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MaterialReadiness;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\ShipmentStatus;
use BackedEnum;

class StatusBadgePresenter
{
    /**
     * Size presets for status badges.
     */
    public const SIZES = [
        'table' => 'text-[11px] h-6 min-h-[24px] px-2.5',
        'card'  => 'text-xs h-7 min-h-[28px] px-3',
        'xs'    => 'text-[10px] h-5 min-h-[20px] px-2',
        'sm'    => 'text-[11px] h-6 min-h-[24px] px-2.5',
        'md'    => 'text-xs h-7 min-h-[28px] px-3',
        'lg'    => 'text-sm h-8 min-h-[32px] px-3.5',
    ];

    /**
     * Base container layout.
     */
    public const BASE_CLASSES = 'inline-flex items-center justify-center font-semibold whitespace-nowrap border rounded-lg transition-colors';

    /**
     * Fallback classes for unknown/neutral status.
     */
    public const FALLBACK_COLOR = 'bg-slate-50 text-slate-700 border-slate-200';

    /**
     * Retrieve complete badge metadata.
     *
     * @param mixed $status
     * @param string|null $context
     * @param string|null $customLabel
     * @param bool $isCustomer
     * @param string $size
     * @param string $extraClass
     * @return array{label: string, color_class: string, size_class: string, full_class: string, raw_status: string}
     */
    public static function get(
        mixed $status,
        ?string $context = null,
        ?string $customLabel = null,
        bool $isCustomer = false,
        string $size = 'table',
        string $extraClass = ''
    ): array {
        $rawStatus = self::normalizeStatusString($status);
        $colorClass = self::classes($status, $context);
        $displayLabel = $customLabel ?: self::label($status, $context, $isCustomer);
        $sizeClass = self::SIZES[$size] ?? self::SIZES['table'];

        $fullClass = trim(implode(' ', array_filter([
            self::BASE_CLASSES,
            $sizeClass,
            $colorClass,
            $extraClass,
        ])));

        return [
            'label'        => $displayLabel,
            'color_class'  => $colorClass,
            'classes'      => $colorClass,
            'size_class'   => $sizeClass,
            'full_class'   => $fullClass,
            'full_classes' => $fullClass,
            'raw_status'   => $rawStatus,
        ];
    }

    /**
     * Get semantic color classes for a given status & context.
     *
     * @param mixed $status
     * @param string|null $context
     * @param bool $customer
     * @return string
     */
    public static function classes(mixed $status, ?string $context = null, bool $customer = false): string
    {
        // 1. Direct BackedEnum resolution
        if ($status instanceof OrderStatus) {
            return self::orderClasses($status->value);
        }
        if ($status instanceof BatchStatus) {
            return self::batchClasses($status->value);
        }
        if ($status instanceof InvoiceStatus) {
            return self::invoiceClasses($status->value);
        }
        if ($status instanceof PurchaseApprovalStatus) {
            return self::approvalClasses($status->value);
        }
        if ($status instanceof PurchaseStatus) {
            return self::purchaseClasses($status->value);
        }
        if ($status instanceof ShipmentStatus) {
            return self::shipmentClasses($status->value);
        }
        if ($status instanceof PaymentStatus) {
            return self::paymentClasses($status->value);
        }
        if ($status instanceof MaterialReadiness) {
            return self::readinessClasses($status->value);
        }

        // 2. String status normalized
        $str = self::normalizeStatusString($status);
        $ctx = strtolower($context ?: '');

        // Match by context if explicitly provided
        if ($ctx === 'order') {
            return self::orderClasses($str);
        }
        if ($ctx === 'batch' || $ctx === 'production_batch') {
            return self::batchClasses($str);
        }
        if ($ctx === 'invoice') {
            return self::invoiceClasses($str);
        }
        if ($ctx === 'approval' || $ctx === 'purchase_approval') {
            return self::approvalClasses($str);
        }
        if ($ctx === 'purchase') {
            return self::purchaseClasses($str);
        }
        if ($ctx === 'shipment') {
            return self::shipmentClasses($str);
        }
        if ($ctx === 'payment') {
            return self::paymentClasses($str);
        }
        if ($ctx === 'readiness' || $ctx === 'material_readiness') {
            return self::readinessClasses($str);
        }
        if ($ctx === 'stock') {
            return self::stockClasses($str);
        }
        if ($ctx === 'flow' || $ctx === 'finance_flow') {
            return self::flowClasses($str);
        }

        // 3. Global semantic keyword mapping
        return self::resolveGlobalString($str);
    }

    /**
     * Get human readable label for status.
     *
     * @param mixed $status
     * @param string|null $context
     * @param bool $isCustomer
     * @return string
     */
    public static function label(mixed $status, ?string $context = null, bool $isCustomer = false): string
    {
        if ($status instanceof OrderStatus) {
            return $isCustomer ? $status->customerLabel() : $status->label();
        }
        if ($status instanceof BackedEnum && method_exists($status, 'label')) {
            return $status->label();
        }

        $str = self::normalizeStatusString($status);

        // Friendly customer labels
        if ($isCustomer) {
            $customerMap = [
                'KOMUNIKASI' => 'Pesanan Diterima',
                'DEAL'       => 'Dikonfirmasi',
                'BAHAN'      => 'Bahan Disiapkan',
                'PRINTING'   => 'Printing',
                'PRODUKSI'   => 'Produksi',
                'QC'         => 'QC',
                'PACKING'    => 'Dikemas',
                'DIKIRIM'    => 'Dikirim',
                'SELESAI'    => 'Selesai',
                'DELIVERED'  => 'Diterima',
                'UNPAID'     => 'Belum Lunas',
                'PARTIAL'    => 'Bayar Sebagian',
                'PAID'       => 'Lunas',
                'CANCELLED'  => 'Dibatalkan',
            ];
            if (isset($customerMap[$str])) {
                return $customerMap[$str];
            }
        }

        // Generic operational labels
        $map = [
            'KOMUNIKASI'            => 'Komunikasi',
            'DEAL'                  => 'Deal',
            'BAHAN'                 => 'Bahan',
            'PRINTING'              => 'Printing',
            'PRODUKSI'              => 'Produksi',
            'QC'                    => 'QC',
            'PACKING'               => 'Packing',
            'DIKIRIM'               => 'Dikirim',
            'SELESAI'               => 'Selesai',
            'CANCELLED'             => 'Dibatalkan',
            'UNPAID'                => 'Belum Lunas',
            'PARTIAL'               => 'Bayar Sebagian',
            'PAID'                  => 'Lunas',
            'PENDING'               => 'Menunggu',
            'CONFIRMED'             => 'Dikonfirmasi',
            'NOT_SUBMITTED'         => 'Belum Diajukan',
            'PENDING_APPROVAL'      => 'Menunggu Persetujuan',
            'APPROVED'              => 'Disetujui',
            'REVISION_REQUIRED'     => 'Perlu Revisi',
            'REJECTED'              => 'Ditolak',
            'DRAFT'                 => 'Draft',
            'ORDERED'               => 'Dipesan',
            'RECEIVED'              => 'Diterima',
            'PACKED'                => 'Dikemas',
            'SHIPPED'               => 'Dikirim',
            'DELIVERED'             => 'Terkirim',
            'RETURNED'              => 'Dikembalikan',
            'PERLU_PEMBELIAN'       => 'Perlu Pembelian',
            'SIAP_DIALOKASIKAN'     => 'Siap Dialokasikan',
            'TERALOKASI'            => 'Teralokasi',
            'BELUM_TERSEDIA'        => 'Belum Tersedia',
            'AMAN'                  => 'Aman',
            'RENDAH'                => 'Stok Rendah',
            'HABIS'                 => 'Stok Habis',
            'MASUK'                 => 'MASUK',
            'KELUAR'                => 'KELUAR',
            'READY_TO_SHIP'         => 'Siap Dikirim',
            'WAITING_PAYMENT'       => 'Menunggu Pelunasan',
            'WAITING_CONFIRMATION'  => 'Siap Dikonfirmasi',
        ];

        return $map[$str] ?? (string) $status;
    }

    /**
     * Format full CSS classes string.
     */
    public static function fullClasses(mixed $status, ?string $context = null, string $size = 'table', string $extraClass = ''): string
    {
        $meta = self::get($status, $context, null, false, $size, $extraClass);
        return $meta['full_class'];
    }

    /* ── Domain Specific Color Resolvers ────────────────────────────── */

    private static function orderClasses(string $status): string
    {
        return match ($status) {
            'KOMUNIKASI' => 'bg-amber-50 text-amber-700 border-amber-200',
            'DEAL'       => 'bg-purple-50 text-purple-700 border-purple-200',
            'BAHAN'      => 'bg-rose-50 text-rose-700 border-rose-200',
            'PRINTING'   => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
            'PRODUKSI'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'QC'         => 'bg-sky-50 text-sky-700 border-sky-200',
            'PACKING'    => 'bg-[#FDF2F7] text-[#8E1B5C] border-[#EBDDE4]',
            'DIKIRIM'    => 'bg-teal-50 text-teal-700 border-teal-200',
            'SELESAI'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CANCELLED'  => 'bg-red-50 text-red-700 border-red-200',
            default      => self::resolveGlobalString($status),
        };
    }

    private static function batchClasses(string $status): string
    {
        return match ($status) {
            'BAHAN'      => 'bg-rose-50 text-rose-700 border-rose-200',
            'PRINTING'   => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
            'PRODUKSI'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'QC'         => 'bg-sky-50 text-sky-700 border-sky-200',
            'PACKING'    => 'bg-[#FDF2F7] text-[#8E1B5C] border-[#EBDDE4]',
            'SELESAI'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CANCELLED'  => 'bg-red-50 text-red-700 border-red-200',
            default      => self::resolveGlobalString($status),
        };
    }

    private static function invoiceClasses(string $status): string
    {
        return match ($status) {
            'UNPAID'    => 'bg-amber-50 text-amber-700 border-amber-200',
            'PARTIAL'   => 'bg-amber-50 text-amber-700 border-amber-200',
            'PAID'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CANCELLED' => 'bg-red-50 text-red-700 border-red-200',
            default     => self::resolveGlobalString($status),
        };
    }

    private static function approvalClasses(string $status): string
    {
        return match ($status) {
            'NOT_SUBMITTED'     => 'bg-slate-50 text-slate-700 border-slate-200',
            'PENDING_APPROVAL'  => 'bg-amber-50 text-amber-700 border-amber-200',
            'APPROVED'          => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'REVISION_REQUIRED' => 'bg-orange-50 text-orange-700 border-orange-200',
            'REJECTED'          => 'bg-red-50 text-red-700 border-red-200',
            default             => self::resolveGlobalString($status),
        };
    }

    private static function purchaseClasses(string $status): string
    {
        return match ($status) {
            'DRAFT'                   => 'bg-slate-50 text-slate-700 border-slate-200',
            'ORDERED', 'DIPESAN'      => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            'RECEIVED', 'DITERIMA'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CANCELLED', 'DIBATALKAN' => 'bg-red-50 text-red-700 border-red-200',
            default                   => self::resolveGlobalString($status),
        };
    }

    private static function shipmentClasses(string $status): string
    {
        return match ($status) {
            'PENDING'   => 'bg-amber-50 text-amber-700 border-amber-200',
            'PACKED'    => 'bg-[#FDF2F7] text-[#8E1B5C] border-[#EBDDE4]',
            'SHIPPED'   => 'bg-teal-50 text-teal-700 border-teal-200',
            'DELIVERED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'RETURNED'  => 'bg-red-50 text-red-700 border-red-200',
            default     => self::resolveGlobalString($status),
        };
    }

    private static function paymentClasses(string $status): string
    {
        return match ($status) {
            'PENDING'   => 'bg-amber-50 text-amber-700 border-amber-200',
            'CONFIRMED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'CANCELLED' => 'bg-red-50 text-red-700 border-red-200',
            default     => self::resolveGlobalString($status),
        };
    }

    private static function readinessClasses(string $status): string
    {
        return match ($status) {
            'PERLU_PEMBELIAN'   => 'bg-amber-50 text-amber-700 border-amber-200',
            'SIAP_DIALOKASIKAN' => 'bg-[#F7E6EE] text-[#8E1B5C] border-[#EBDDE4]',
            'TERALOKASI'        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'BELUM_TERSEDIA'    => 'bg-slate-50 text-slate-700 border-slate-200',
            default             => self::resolveGlobalString($status),
        };
    }

    private static function stockClasses(string $status): string
    {
        return match ($status) {
            'AMAN'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'RENDAH' => 'bg-amber-50 text-amber-700 border-amber-200',
            'HABIS'  => 'bg-red-50 text-red-700 border-red-200',
            default  => self::resolveGlobalString($status),
        };
    }

    private static function flowClasses(string $status): string
    {
        return match ($status) {
            'MASUK', 'IN'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'KELUAR', 'OUT' => 'bg-rose-50 text-rose-700 border-rose-200',
            default         => self::resolveGlobalString($status),
        };
    }

    /**
     * Keyword and normalized string mapping.
     */
    private static function resolveGlobalString(string $str): string
    {
        // Success keywords (Emerald / Green)
        if (in_array($str, [
            'SELESAI', 'DELIVERED', 'PAID', 'LUNAS', 'CONFIRMED', 'APPROVED',
            'TERALOKASI', 'SIAP', 'SESUAI', 'AMAN', 'TERKIRIM', 'AKTIF', 'BERHASIL',
            'READY_TO_SHIP', 'SIAP DIKIRIM', 'SIAP DIKONFIRMASI', 'SIAP DIKERJAKAN',
            'MASUK', 'RECEIVED', 'DITERIMA',
        ], true)) {
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        }

        // Active / In Progress stages
        if ($str === 'KOMUNIKASI' || $str === 'PESANAN DITERIMA') {
            return 'bg-amber-50 text-amber-700 border-amber-200';
        }
        if ($str === 'DEAL' || $str === 'DIKONFIRMASI') {
            return 'bg-purple-50 text-purple-700 border-purple-200';
        }
        if ($str === 'BAHAN' || $str === 'BAHAN DISIAPKAN') {
            return 'bg-rose-50 text-rose-700 border-rose-200';
        }
        if ($str === 'PRINTING' || $str === 'PROSES PRINTING') {
            return 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200';
        }
        if ($str === 'PRODUKSI' || $str === 'DALAM PRODUKSI') {
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        }
        if ($str === 'QC' || $str === 'MENUNGGU QC') {
            return 'bg-sky-50 text-sky-700 border-sky-200';
        }
        if ($str === 'PACKING' || $str === 'DIKEMAS' || $str === 'PACKED') {
            return 'bg-[#FDF2F7] text-[#8E1B5C] border-[#EBDDE4]';
        }
        if ($str === 'DIKIRIM' || $str === 'SHIPPED') {
            return 'bg-teal-50 text-teal-700 border-teal-200';
        }
        if ($str === 'ORDERED' || $str === 'DIPESAN' || $str === 'MENUNGGU BARANG') {
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        }
        if ($str === 'SIAP_DIALOKASIKAN' || $str === 'SIAP DIALOKASIKAN') {
            return 'bg-[#F7E6EE] text-[#8E1B5C] border-[#EBDDE4]';
        }
        if ($str === 'PENDING_APPROVAL' || $str === 'MENUNGGU PERSETUJUAN' || $str === 'MENUNGGU OWNER') {
            return 'bg-amber-50 text-amber-700 border-amber-200';
        }
        if ($str === 'MENUNGGU PEMBAYARAN' || $str === 'WAITING_PAYMENT' || $str === 'MENUNGGU PELUNASAN') {
            return 'bg-amber-50 text-amber-800 border-amber-200';
        }

        // Warning / Attention keywords (Amber / Orange)
        if (in_array($str, ['REVISION_REQUIRED', 'PERLU REVISI', 'REVISI'], true)) {
            return 'bg-orange-50 text-orange-700 border-orange-200';
        }
        if (in_array($str, [
            'PARTIAL', 'BAYAR SEBAGIAN', 'PEMBAYARAN SEBAGIAN', 'UNPAID', 'BELUM LUNAS', 'KURANG',
            'PERLU_PEMBELIAN', 'PERLU PEMBELIAN', 'PERLU ORDER', 'KEKURANGAN BAHAN',
            'RENDAH', 'STOK RENDAH', 'PENDING', 'MENUNGGU', 'BELUM SIAP',
        ], true)) {
            return 'bg-amber-50 text-amber-700 border-amber-200';
        }
        if (in_array($str, ['OVERDUE', 'MELEWATI TARGET', 'TERLAMBAT'], true)) {
            return 'bg-orange-50 text-orange-800 border-orange-300 font-bold';
        }

        // Negative / Rejected / Cancelled (Red soft)
        if (in_array($str, [
            'CANCELLED', 'DIBATALKAN', 'BATAL', 'REJECTED', 'DITOLAK', 'REJECT',
            'GAGAL', 'FAILED', 'HABIS', 'STOK HABIS', 'RETURNED', 'DIKEMBALIKAN',
            'KELUAR',
        ], true)) {
            return 'bg-red-50 text-red-700 border-red-200';
        }

        // Neutral / Informational keywords (Slate soft)
        if (in_array($str, [
            'DRAFT', 'NOT_SUBMITTED', 'BELUM DIAJUKAN', 'BELUM TERSEDIA', 'BELUM_TERSEDIA',
            'TIDAK ADA', 'N/A', 'PENDING DATA', 'HISTORICAL', 'NONAKTIF',
        ], true)) {
            return 'bg-slate-50 text-slate-700 border-slate-200';
        }

        return self::FALLBACK_COLOR;
    }

    private static function normalizeStatusString(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return strtoupper((string) $status->value);
        }
        if (is_string($status)) {
            return strtoupper(trim($status));
        }
        return '';
    }
}
