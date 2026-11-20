<?php

namespace App\Models\Inventory;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Models\Master\Supplier;
use App\Models\Production\ProductionBatch;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'outlet_id',
        'supplier_id',
        'purchase_no',
        'purchase_date',
        'status',
        'approval_status',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'review_note',
        'batch_id',
        'notes',
    ];

    protected $casts = [
        'purchase_date'   => 'date',
        'status'          => PurchaseStatus::class,
        'approval_status' => PurchaseApprovalStatus::class,
        'submitted_at'    => 'datetime',
        'approved_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Purchase $purchase) {
            if (empty($purchase->approval_status)) {
                $purchase->approval_status = in_array($purchase->status, [PurchaseStatus::ORDERED, PurchaseStatus::RECEIVED], true)
                    ? PurchaseApprovalStatus::APPROVED
                    : PurchaseApprovalStatus::NOT_SUBMITTED;
            }
        });
    }

    /**
     * Scope purchases to a specific legitimate outlet.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->where('purchases.outlet_id', $outletId);
    }

    /**
     * Verify that this Purchase and its related models (supplier, items.material, batch)
     * strictly belong to the specified outlet. Fails closed on any foreign or missing relation.
     */
    public function hasConsistentTenantRelations(int $outletId): bool
    {
        if ($this->outlet_id === null || (int) $this->outlet_id !== $outletId) {
            return false;
        }

        if ($this->supplier_id !== null) {
            $supplier = $this->relationLoaded('supplier')
                ? $this->supplier
                : $this->supplier()->first();

            if (! $supplier || $supplier->outlet_id === null || (int) $supplier->outlet_id !== $outletId) {
                return false;
            }
        }

        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('material')->get();

        foreach ($items as $item) {
            if (! $item->material || $item->material->outlet_id === null || (int) $item->material->outlet_id !== $outletId) {
                return false;
            }
        }

        if ($this->batch_id !== null) {
            $batch = $this->relationLoaded('batch')
                ? $this->batch
                : $this->batch()->first();

            if ($batch && ! $batch->hasConsistentTenantRelations($outletId)) {
                return false;
            }
        }

        return true;
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'batch_id');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(PurchaseApprovalHistory::class)->orderBy('created_at');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === PurchaseApprovalStatus::APPROVED;
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === PurchaseApprovalStatus::PENDING_APPROVAL;
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => (float) $item->qty * (float) $item->unit_price);
    }

    public function getTotalAmountStringAttribute(): string
    {
        $total = '0.00';
        foreach ($this->items as $item) {
            $subtotal = bcmul((string) $item->qty, (string) $item->unit_price, 2);
            $total = bcadd($total, $subtotal, 2);
        }
        return $total;
    }

    public function getPaidAmountAttribute(): string
    {
        $paid = '0.00';
        foreach ($this->payments as $payment) {
            $paid = bcadd($paid, (string) $payment->amount, 2);
        }
        return $paid;
    }

    public function getOutstandingAmountAttribute(): string
    {
        $total = $this->total_amount_string;
        $paid = $this->paid_amount;
        $diff = bcsub($total, $paid, 2);
        return bccomp($diff, '0.00', 2) > 0 ? $diff : '0.00';
    }

    public function isFullyPaid(): bool
    {
        return bccomp($this->outstanding_amount, '0.00', 2) === 0 && bccomp($this->total_amount_string, '0.00', 2) > 0;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        if ($this->isFullyPaid()) {
            return 'LUNAS';
        }
        if (bccomp($this->paid_amount, '0.00', 2) > 0) {
            return 'BAYAR SEBAGIAN';
        }
        return 'BELUM DIBAYAR';
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match($this->payment_status_label) {
            'LUNAS'          => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'BAYAR SEBAGIAN' => 'bg-amber-50 text-amber-700 border-amber-200',
            default          => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }

    /**
     * Compute single effective/derived display status for Purchase Order presentation.
     *
     * Priority:
     * 1. CANCELLED -> Dibatalkan
     * 2. RECEIVED -> Diterima
     * 3. ORDERED -> Dipesan (including fully paid auto-ordered)
     * 4. REJECTED -> Ditolak
     * 5. REVISION_REQUIRED -> Perlu Revisi
     * 6. PENDING_APPROVAL -> Menunggu Persetujuan
     * 7. APPROVED + partial -> Pembayaran Sebagian
     * 8. APPROVED + unpaid -> Menunggu Pembayaran
     * 9. NOT_SUBMITTED -> Belum Diajukan
     * 10. Fallback neutral -> Draft
     *
     * @return array{key: string, label: string, color_class: string}
     */
    public function effectiveStatus(): array
    {
        if ($this->status === PurchaseStatus::CANCELLED) {
            return [
                'key'         => 'CANCELLED',
                'label'       => 'Dibatalkan',
                'color_class' => 'bg-red-50 text-red-700 border-red-200',
            ];
        }

        if ($this->status === PurchaseStatus::RECEIVED) {
            return [
                'key'         => 'RECEIVED',
                'label'       => 'Diterima',
                'color_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ];
        }

        if ($this->status === PurchaseStatus::ORDERED) {
            return [
                'key'         => 'ORDERED',
                'label'       => 'Dipesan',
                'color_class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            ];
        }

        if ($this->approval_status === PurchaseApprovalStatus::REJECTED) {
            return [
                'key'         => 'REJECTED',
                'label'       => 'Ditolak',
                'color_class' => 'bg-red-50 text-red-700 border-red-200',
            ];
        }

        if ($this->approval_status === PurchaseApprovalStatus::REVISION_REQUIRED) {
            return [
                'key'         => 'REVISION_REQUIRED',
                'label'       => 'Perlu Revisi',
                'color_class' => 'bg-orange-50 text-orange-700 border-orange-200',
            ];
        }

        if ($this->approval_status === PurchaseApprovalStatus::PENDING_APPROVAL) {
            return [
                'key'         => 'PENDING_APPROVAL',
                'label'       => 'Menunggu Persetujuan',
                'color_class' => 'bg-amber-50 text-amber-700 border-amber-200',
            ];
        }

        if ($this->approval_status === PurchaseApprovalStatus::APPROVED) {
            if ($this->isFullyPaid()) {
                return [
                    'key'         => 'ORDERED',
                    'label'       => 'Dipesan',
                    'color_class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                ];
            }

            if (bccomp($this->paid_amount, '0.00', 2) > 0 && bccomp($this->outstanding_amount, '0.00', 2) > 0) {
                return [
                    'key'         => 'PARTIAL',
                    'label'       => 'Pembayaran Sebagian',
                    'color_class' => 'bg-amber-50 text-amber-700 border-amber-200',
                ];
            }

            return [
                'key'         => 'WAITING_PAYMENT',
                'label'       => 'Menunggu Pembayaran',
                'color_class' => 'bg-amber-50 text-amber-800 border-amber-200',
            ];
        }

        if ($this->approval_status === PurchaseApprovalStatus::NOT_SUBMITTED) {
            return [
                'key'         => 'NOT_SUBMITTED',
                'label'       => 'Belum Diajukan',
                'color_class' => 'bg-slate-50 text-slate-700 border-slate-200',
            ];
        }

        return [
            'key'         => 'DRAFT',
            'label'       => 'Draft',
            'color_class' => 'bg-slate-50 text-slate-700 border-slate-200',
        ];
    }

    public function getEffectiveStatusAttribute(): array
    {
        return $this->effectiveStatus();
    }

    public function getEffectiveStatusLabelAttribute(): string
    {
        return $this->effectiveStatus()['label'];
    }
}
