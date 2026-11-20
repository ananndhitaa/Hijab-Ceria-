<?php

namespace App\Models\Production;

use App\Enums\BatchStatus;
use App\Enums\MaterialReadiness;
use App\Models\Finance\BatchCost;
use App\Models\Sales\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class ProductionBatch extends Model
{
    protected $fillable = [
        'order_item_id',
        'batch_no',
        'target_qty',
        'status',
        'requires_printing',
        'printing_cost_per_unit_snapshot',
        'direct_labor_cost_per_unit_snapshot',
        'packaging_process_cost_per_good_unit_snapshot',
        'other_direct_cost_per_unit_snapshot',
        'material_usage_recorded_at',
        'material_usage_recorded_by',
        'planned_start_date',
        'planned_end_date',
        'notes',
        'production_employee_id',
        'close_reason',
        'closed_by_user_id',
        'closed_at',
        'shortfall_qty',
    ];

    protected $casts = [
        'target_qty'                                    => 'decimal:4',
        'status'                                        => BatchStatus::class,
        'requires_printing'                             => 'boolean',
        'printing_cost_per_unit_snapshot'               => 'decimal:2',
        'direct_labor_cost_per_unit_snapshot'           => 'decimal:2',
        'packaging_process_cost_per_good_unit_snapshot' => 'decimal:2',
        'other_direct_cost_per_unit_snapshot'           => 'decimal:2',
        'material_usage_recorded_at'                    => 'datetime',
        'planned_start_date'                            => 'date',
        'planned_end_date'                              => 'date',
        'closed_at'                                     => 'datetime',
        'shortfall_qty'                                 => 'decimal:4',
    ];

    public function getEffectiveRequiresPrintingAttribute(): ?bool
    {
        if ($this->requires_printing !== null) {
            return $this->requires_printing;
        }

        return $this->orderItem?->variant?->requires_printing;
    }

    /**
     * Presentation-only indicator: the batch target date has passed and the batch is not yet finished.
     * Not a business status.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->planned_end_date) {
            return false;
        }

        if (in_array($this->status, [BatchStatus::SELESAI, BatchStatus::SELESAI_PARSIAL, BatchStatus::CANCELLED], true)) {
            return false;
        }

        $today = \App\Support\DateTimeFormatter::toDisplayTimezone(now())->toDateString();

        return $this->planned_end_date->toDateString() < $today;
    }

    /**
     * Authoritative actual production date (business date string Y-m-d).
     *
     * Semantics:
     * - planned_start_date = Admin planned start date
     * - planned_end_date = Admin planned target completion date
     * - productionResult.result_date = Actual production date explicitly chosen by Produksi
     * - qc_date = Follows authoritative actual production date
     * - qc_at = System timestamp when QC is saved
     *
     * Order of precedence:
     * 1. result_date on productionResult (authoritative actual date selected by Staff Produksi)
     * 2. planned_start_date on batch (legacy fallback only if actual production result is unavailable)
     * 3. created_at date of batch in Asia/Jakarta (ultimate fallback)
     */
    public function getActualProductionDate(): string
    {
        $prodResult = $this->relationLoaded('productionResult')
            ? $this->productionResult
            : $this->productionResult()->first();

        if ($prodResult?->result_date) {
            return $prodResult->result_date->toDateString();
        }

        if ($this->planned_start_date) {
            return $this->planned_start_date->toDateString();
        }

        $date = $this->created_at ?? now();

        return \App\Support\DateTimeFormatter::toDisplayTimezone($date)->toDateString();
    }

    /**
     * Backward-compatible alias for getActualProductionDate.
     */
    public function getProductionStartDate(): string
    {
        return $this->getActualProductionDate();
    }

    /**
     * Check if QC can be performed on the current business date.
     * Rule: today in Asia/Jakarta >= getActualProductionDate().
     */
    public function getIsQcDateEligibleAttribute(): bool
    {
        $today = \App\Support\DateTimeFormatter::toDisplayTimezone(now())->toDateString();

        return $today >= $this->getActualProductionDate();
    }

    public function getRouteLabelAttribute(): string
    {
        $effective = $this->effective_requires_printing;

        if ($effective === null) {
            return 'Rute Belum Ditetapkan';
        }

        return $effective ? 'Dengan Printing' : 'Tanpa Printing';
    }

    public function getRouteBadgeClassAttribute(): string
    {
        $effective = $this->effective_requires_printing;

        if ($effective === null) {
            return 'bg-amber-50 text-amber-800 border border-amber-200';
        }

        return $effective
            ? 'bg-[#F7E6EE] text-[#8E1B5C] border border-[#D96B9B]'
            : 'bg-white text-[#786B72] border border-[#EBDDE4]';
    }

    public function scopeWhereMaterialReadiness(Builder $query, string|MaterialReadiness $status): Builder
    {
        $statusValue = $status instanceof MaterialReadiness ? $status->value : $status;

        return match ($statusValue) {
            'TERALOKASI' => $query->whereHas('batchMaterials')
                ->whereDoesntHave('batchMaterials', function ($q) {
                    $q->whereColumn('allocated_qty', '<', 'required_qty')
                      ->orWhere('required_qty', '<=', 0);
                }),

            'PERLU_PEMBELIAN' => $query->whereHas('batchMaterials', function ($q) {
                    $q->whereColumn('allocated_qty', '<', 'required_qty');
                })
                ->whereHas('batchMaterials', function ($bmQuery) {
                    $bmQuery->whereColumn('allocated_qty', '<', 'required_qty')
                        ->where(function ($q) {
                            $q->whereDoesntHave('material.stock')
                              ->orWhereHas('material.stock', function ($stockQuery) {
                                  $stockQuery->whereColumn('material_stocks.qty_on_hand', '<', 'batch_materials.required_qty');
                              });
                        });
                }),

            'SIAP_DIALOKASIKAN' => $query->whereHas('batchMaterials', function ($q) {
                    $q->whereColumn('allocated_qty', '<', 'required_qty');
                })
                ->whereDoesntHave('batchMaterials', function ($bmQuery) {
                    $bmQuery->whereColumn('allocated_qty', '<', 'required_qty')
                        ->where(function ($q) {
                            $q->whereDoesntHave('material.stock')
                              ->orWhereHas('material.stock', function ($stockQuery) {
                                  $stockQuery->whereColumn('material_stocks.qty_on_hand', '<', 'batch_materials.required_qty');
                              });
                        });
                }),

            default => $query,
        };
    }

    public function getMaterialReadinessAttribute(): MaterialReadiness
    {
        $materials = $this->relationLoaded('batchMaterials')
            ? $this->batchMaterials
            : $this->batchMaterials()->with('material.stock')->get();

        if ($materials->isEmpty()) {
            return MaterialReadiness::BELUM_TERSEDIA;
        }

        $allAllocated = true;
        foreach ($materials as $bm) {
            $req = (string) $bm->required_qty;
            $alloc = (string) ($bm->allocated_qty ?? '0.0000');
            if (bccomp($req, '0.0000', 4) <= 0 || bccomp($alloc, $req, 4) < 0) {
                $allAllocated = false;
                break;
            }
        }

        if ($allAllocated) {
            return MaterialReadiness::TERALOKASI;
        }

        foreach ($materials as $bm) {
            $req = (string) $bm->required_qty;
            $alloc = (string) ($bm->allocated_qty ?? '0.0000');
            if (bccomp($alloc, $req, 4) >= 0) {
                continue;
            }

            $stockOnHand = (string) ($bm->material?->stock?->qty_on_hand ?? '0.0000');
            if (bccomp($req, $stockOnHand, 4) > 0) {
                return MaterialReadiness::PERLU_PEMBELIAN;
            }
        }

        return MaterialReadiness::SIAP_DIALOKASIKAN;
    }

    public function isMaterialReady(): bool
    {
        $materials = $this->relationLoaded('batchMaterials')
            ? $this->batchMaterials
            : $this->batchMaterials()->get();

        if ($materials->isEmpty()) {
            return false;
        }

        foreach ($materials as $bm) {
            if (bccomp((string) $bm->required_qty, '0.0000', 4) <= 0) {
                return false;
            }
            if (bccomp((string) $bm->allocated_qty, (string) $bm->required_qty, 4) < 0) {
                return false;
            }
        }

        return true;
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Parent-derived outlet ownership (follows parent OrderItem -> Order).
     */
    public function getOutletAttribute(): ?\App\Models\System\Outlet
    {
        return $this->orderItem?->order?->outlet;
    }

    /**
     * Scope query to only include batches belonging to the given outlet
     * through orderItem -> order -> outlet_id.
     */
    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->whereHas('orderItem.order', function (Builder $orderQuery) use ($outletId) {
            $orderQuery->where('orders.outlet_id', $outletId);
        });
    }

    /**
     * Determine if the production batch belongs to the given outlet.
     */
    public function belongsToOutlet(int $outletId): bool
    {
        $batchOutletId = $this->orderItem?->order?->outlet_id;

        if ($batchOutletId === null && $this->order_item_id) {
            $batchOutletId = $this->orderItem()
                ->with('order')
                ->first()
                ?->order
                ?->outlet_id;
        }

        return $batchOutletId !== null && (int) $batchOutletId === $outletId;
    }

    /**
     * Validate that all related models (OrderItem -> Order, Customer, Variant, Product)
     * are strictly and consistently bound to the given outlet.
     */
    public function hasConsistentTenantRelations(int $outletId): bool
    {
        if (! $this->belongsToOutlet($outletId)) {
            return false;
        }

        $item = $this->orderItem ?? $this->orderItem()->with(['variant.product', 'order.customer'])->first();
        if (! $item) {
            return false;
        }

        $order = $item->order;
        if (! $order || $order->outlet_id === null || (int) $order->outlet_id !== $outletId) {
            return false;
        }

        if ($order->customer && $order->customer->outlet_id !== null && (int) $order->customer->outlet_id !== $outletId) {
            return false;
        }

        $variant = $item->variant;
        if ($variant && $variant->outlet_id !== null && (int) $variant->outlet_id !== $outletId) {
            return false;
        }

        $product = $variant?->product;
        if ($product && $product->outlet_id !== null && (int) $product->outlet_id !== $outletId) {
            return false;
        }

        if ($this->production_employee_id !== null) {
            $pic = $this->relationLoaded('productionEmployee')
                ? $this->productionEmployee
                : $this->productionEmployee()->first();
            if ($pic && $pic->outlet_id !== null && (int) $pic->outlet_id !== $outletId) {
                return false;
            }
        }

        $materials = $this->relationLoaded('batchMaterials')
            ? $this->batchMaterials
            : $this->batchMaterials()->with('material')->get();

        foreach ($materials as $bm) {
            if (! $bm->material || $bm->material->outlet_id === null || (int) $bm->material->outlet_id !== $outletId) {
                return false;
            }
        }

        return true;
    }

    public function batchMaterials(): HasMany
    {
        return $this->hasMany(BatchMaterial::class, 'batch_id');
    }

    public function productionResults(): HasMany
    {
        return $this->hasMany(ProductionResult::class, 'batch_id');
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(ProductionResult::class, 'batch_id')->latestOfMany();
    }

    public function productionResult(): HasOne
    {
        return $this->hasOne(ProductionResult::class, 'batch_id')->latestOfMany();
    }

    public function qcResult(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            QcResult::class,
            ProductionResult::class,
            'batch_id',
            'production_result_id',
            'id',
            'id'
        );
    }

    public function qcResults(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            QcResult::class,
            ProductionResult::class,
            'batch_id',
            'production_result_id',
            'id',
            'id'
        );
    }

    public function costs(): HasMany
    {
        return $this->hasMany(BatchCost::class, 'batch_id');
    }

    public function batchCosts(): HasMany
    {
        return $this->hasMany(BatchCost::class, 'batch_id');
    }

    public function materialUsageRecordedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\System\User::class, 'material_usage_recorded_by');
    }

    public function productionEmployee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\System\User::class, 'production_employee_id');
    }

    public function picHistories(): HasMany
    {
        return $this->hasMany(\App\Models\Production\BatchPicHistory::class, 'batch_id')->orderBy('assigned_at', 'desc');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\System\User::class, 'closed_by_user_id');
    }

    /**
     * Total actual production quantity across all recorded production results.
     */
    public function getTotalActualQtyAttribute(): float
    {
        if ($this->relationLoaded('productionResults')) {
            return (float) $this->productionResults->sum('actual_qty');
        }

        return (float) $this->productionResults()->sum('actual_qty');
    }

    /**
     * Total confirmed good units across all QC results of this batch.
     */
    public function getTotalGoodQtyAttribute(): float
    {
        if ($this->relationLoaded('productionResults')) {
            return (float) $this->productionResults->sum(fn ($pr) => (float) ($pr->qcResult?->good_qty ?? 0));
        }

        return (float) $this->productionResults()
            ->join('qc_results', 'production_results.id', '=', 'qc_results.production_result_id')
            ->sum('qc_results.good_qty');
    }

    /**
     * Total reject units across all QC results of this batch.
     */
    public function getTotalRejectQtyAttribute(): float
    {
        if ($this->relationLoaded('productionResults')) {
            return (float) $this->productionResults->sum(fn ($pr) => (float) ($pr->qcResult?->reject_qty ?? 0));
        }

        return (float) $this->productionResults()
            ->join('qc_results', 'production_results.id', '=', 'qc_results.production_result_id')
            ->sum('qc_results.reject_qty');
    }

    /**
     * Total inspected units across all QC results (Good + Reject).
     */
    public function getTotalInspectedQtyAttribute(): float
    {
        return $this->total_good_qty + $this->total_reject_qty;
    }

    /**
     * Actual produced units that have not yet been inspected by QC.
     */
    public function getUninspectedActualQtyAttribute(): float
    {
        return max(0.0, $this->total_actual_qty - $this->total_inspected_qty);
    }

    /**
     * Sisa target produksi (shortfall) = Target Produksi - Total Aktual Produksi.
     * Tidak boleh menganggap kekurangan produksi sebagai barang reject.
     */
    public function getShortfallQtyAttribute(): float
    {
        if (isset($this->attributes['shortfall_qty']) && (float) $this->attributes['shortfall_qty'] > 0) {
            return (float) $this->attributes['shortfall_qty'];
        }

        return max(0.0, (float) $this->target_qty - $this->total_actual_qty);
    }

    /**
     * Alias for shortfall_qty.
     */
    public function getRemainingTargetQtyAttribute(): float
    {
        return $this->shortfall_qty;
    }

    public function getCalculatedShortfallQtyAttribute(): float
    {
        return $this->shortfall_qty;
    }

    /**
     * Percentage achievement = (Total Aktual / Target) * 100%.
     */
    public function getAchievementPercentageAttribute(): float
    {
        $target = (float) $this->target_qty;
        if ($target <= 0) {
            return 0.0;
        }

        return round(($this->total_actual_qty / $target) * 100, 2);
    }

    /**
     * Indicates whether the batch was partially produced.
     */
    public function getIsPartialAttribute(): bool
    {
        return $this->status === BatchStatus::SELESAI_PARSIAL ||
            ($this->total_actual_qty > 0 && $this->total_actual_qty < (float) $this->target_qty);
    }

    /**
     * Signed days difference between planned_end_date and productionResult.result_date.
     * Positive = faster (finished before target).
     * Zero = exactly on target.
     * Negative = late (finished after target).
     */
    public function getTimeDifferenceDaysAttribute(): ?int
    {
        $res = $this->relationLoaded('productionResult') ? $this->productionResult : ($this->latestResult ?? $this->productionResults()->latest()->first());
        if (! $this->planned_end_date || ! $res?->result_date) {
            return null;
        }

        return (int) $res->result_date->copy()->startOfDay()->diffInDays($this->planned_end_date->copy()->startOfDay(), false);
    }

    public function getTimePerformanceStatusAttribute(): ?string
    {
        $days = $this->time_difference_days;
        if ($days === null) {
            return null;
        }

        if ($days > 0) {
            return 'LEBIH_CEPAT';
        }
        if ($days === 0) {
            return 'TEPAT_WAKTU';
        }

        return 'TERLAMBAT';
    }

    public function getTimePerformanceLabelAttribute(): ?string
    {
        $days = $this->time_difference_days;
        if ($days === null) {
            return 'Belum Ada Data';
        }

        if ($days > 0) {
            return "{$days} hari lebih cepat";
        }
        if ($days === 0) {
            return 'Tepat waktu';
        }

        $lateDays = abs($days);
        return "Terlambat {$lateDays} hari";
    }

    public function getTimePerformanceBadgeClassAttribute(): string
    {
        return match($this->time_performance_status) {
            'LEBIH_CEPAT' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'TEPAT_WAKTU' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'TERLAMBAT'   => 'bg-amber-50 text-amber-700 border border-amber-200',
            default       => 'bg-gray-50 text-gray-700 border border-gray-200',
        };
    }

    public function getProductionQuantityAchievementPercentAttribute(): ?float
    {
        $res = $this->relationLoaded('productionResult') ? $this->productionResult : ($this->latestResult ?? $this->productionResults()->latest()->first());
        if (! $res || (float) $this->target_qty <= 0) {
            return null;
        }

        return round(((float) $res->actual_qty / (float) $this->target_qty) * 100, 2);
    }

    public function hasCompleteCostSnapshots(): bool
    {
        if ($this->direct_labor_cost_per_unit_snapshot === null
            || $this->packaging_process_cost_per_good_unit_snapshot === null
            || $this->other_direct_cost_per_unit_snapshot === null
        ) {
            return false;
        }

        if ($this->effective_requires_printing && $this->printing_cost_per_unit_snapshot === null) {
            return false;
        }

        return true;
    }

    // ── Computed ──────────────────────────────────────────────────────

    public function getActualQtyAttribute(): float
    {
        return (float) $this->productionResults->sum('actual_qty');
    }

    public function getVarianceAttribute(): float
    {
        return $this->actual_qty - (float) $this->target_qty;
    }

    public function getVarianceQtyAttribute(): ?string
    {
        $res = $this->latestResult ?? $this->productionResults()->latest()->first();

        if (! $res) {
            return null;
        }

        return bcsub((string) $res->actual_qty, (string) $this->target_qty, 4);
    }

    public function getAchievementPercentAttribute(): ?float
    {
        if ((float) $this->target_qty == 0) {
            return null;
        }

        return ($this->actual_qty / (float) $this->target_qty) * 100;
    }

    /**
     * Scope query to order completed batches by authoritative completion event:
     * 1. qc_results.qc_at (system recorded QC timestamp) or fallback qc_results.created_at
     * 2. production_batches.id desc (tie-breaker)
     */
    public function scopeOrderByLatestCompletion(Builder $query): Builder
    {
        $qcSubquery = QcResult::select(DB::raw('COALESCE(qc_results.qc_at, qc_results.created_at)'))
            ->join('production_results', 'production_results.id', '=', 'qc_results.production_result_id')
            ->whereColumn('production_results.batch_id', 'production_batches.id')
            ->limit(1);

        return $query->orderByDesc($qcSubquery)->orderByDesc('production_batches.id');
    }
}
