<?php

namespace App\Services\Owner;

use App\Enums\BatchStatus;
use App\Enums\UserRole;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\System\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionPerformanceService
{
    /**
     * Resolve start and end dates from a given preset or custom input.
     *
     * @return array{0: string, 1: string, 2: string} [startDate, endDate, preset]
     */
    public function resolveDateRange(?string $preset, ?string $customStart, ?string $customEnd): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $preset = $preset ?: 'this_month';

        [$startDate, $endDate] = match ($preset) {
            'today'       => [$now->toDateString(), $now->toDateString()],
            'this_month'  => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month'  => [
                $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'all'         => [
                '2020-01-01',
                $now->toDateString(),
            ],
            'custom'      => [
                $customStart ?: $now->copy()->startOfMonth()->toDateString(),
                $customEnd ?: $now->copy()->endOfMonth()->toDateString(),
            ],
            default       => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
        };

        return [$startDate, $endDate, $preset];
    }

    /**
     * Calculate comprehensive Production performance metrics for Owner, scoped to an outlet.
     *
     * @param string $startDate (Y-m-d)
     * @param string $endDate   (Y-m-d)
     * @param string $preset
     * @param int|null $outletId
     * @return array
     */
    public function calculatePerformance(string $startDate, string $endDate, string $preset = 'this_month', ?int $outletId = null): array
    {
        $productionUsersQuery = User::where('role', UserRole::PRODUKSI);

        if ($outletId !== null) {
            $productionUsersQuery->where(function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)
                    ->orWhereNull('outlet_id');
            });
        }

        $productionUsers = $productionUsersQuery->orderBy('name')->get();

        $today = Carbon::today('Asia/Jakarta');
        $productionStats = collect();
        $grandTotalBatches = 0;
        $grandBatchesWithTarget = 0;
        $grandOnTimeBatches = 0;
        $grandTotalTargetQty = 0.0;
        $grandTotalActualQty = 0.0;

        $activeStaffCount = 0;
        $availableStaffCount = 0;
        $targetMetStaffCount = 0;

        foreach ($productionUsers as $employee) {
            // 1. Historical completed/produced batches in period
            $batchQuery = ProductionBatch::where('production_employee_id', $employee->id)
                ->whereHas('productionResult');

            if ($outletId !== null) {
                $batchQuery->where(function ($q) use ($outletId) {
                    $q->forOutlet($outletId)
                        ->orWhereHas('orderItem.order', fn ($oq) => $oq->whereNull('outlet_id'));
                });
            }

            if ($preset !== 'all') {
                $batchQuery->whereHas('productionResult', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('result_date', [$startDate, $endDate]);
                });
            }

            $batches = $batchQuery->with([
                'productionResult',
                'orderItem.order.customer',
                'orderItem.variant.product',
            ])->get();

            $totalBatches = $batches->count();
            $batchesWithTarget = 0;
            $onTimeCount = 0;
            $lateCount = 0;
            $fasterCount = 0;
            $exactCount = 0;
            $totalTargetQty = 0.0;
            $totalActualQty = 0.0;

            foreach ($batches as $batch) {
                $res = $batch->productionResult;
                $tQty = (float) $batch->target_qty;
                $aQty = $res ? (float) $res->actual_qty : 0.0;

                $totalTargetQty += $tQty;
                $totalActualQty += $aQty;

                if ($batch->planned_end_date !== null && $res?->result_date !== null) {
                    $batchesWithTarget++;
                    $diff = $batch->time_difference_days;

                    if ($diff !== null && $diff >= 0) {
                        $onTimeCount++;
                        if ($diff > 0) {
                            $fasterCount++;
                        } else {
                            $exactCount++;
                        }
                    } else {
                        $lateCount++;
                    }
                }
            }

            $onTimePercentage = $batchesWithTarget > 0
                ? round(($onTimeCount / $batchesWithTarget) * 100, 2)
                : null;

            $qtyAchievementPercentage = $totalTargetQty > 0
                ? round(($totalActualQty / $totalTargetQty) * 100, 2)
                : null;

            // 2. Live Active Workload for this employee (Tenant-scoped)
            $activeBatches = ProductionBatch::where('production_employee_id', $employee->id)
                ->whereNotIn('status', [BatchStatus::SELESAI->value, BatchStatus::SELESAI_PARSIAL->value, BatchStatus::CANCELLED->value])
                ->when($outletId !== null, fn ($q) => $q->forOutlet($outletId))
                ->with(['productionResult', 'orderItem.order'])
                ->orderBy('planned_end_date')
                ->get();

            $activeCount = $activeBatches->count();
            $activeTargetQty = 0.0;
            $activeActualQty = 0.0;
            $hasOverdue = false;
            $closestDeadline = null;

            foreach ($activeBatches as $ab) {
                $activeTargetQty += (float) $ab->target_qty;
                if ($ab->productionResult) {
                    $activeActualQty += (float) $ab->productionResult->actual_qty;
                }
                if ($ab->planned_end_date) {
                    if ($ab->planned_end_date->lt($today)) {
                        $hasOverdue = true;
                    }
                    if ($closestDeadline === null || $ab->planned_end_date->lt($closestDeadline)) {
                        $closestDeadline = $ab->planned_end_date;
                    }
                }
            }

            $activeRemainingQty = max(0.0, $activeTargetQty - $activeActualQty);

            if ($activeCount > 0) {
                $activeStaffCount++;
            } else {
                $availableStaffCount++;
            }

            $personalAchievementPct = $activeTargetQty > 0
                ? round(($activeActualQty / $activeTargetQty) * 100, 1)
                : ($totalTargetQty > 0 ? round(($totalActualQty / $totalTargetQty) * 100, 1) : 0.0);

            if ($personalAchievementPct >= 100.0) {
                $targetMetStaffCount++;
            }

            $workloadStatus = 'Tersedia';
            $workloadBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            if ($hasOverdue || $activeCount >= 3) {
                $workloadStatus = 'Beban Tinggi';
                $workloadBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
            } elseif ($activeCount > 0) {
                $workloadStatus = 'Beban Sedang';
                $workloadBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
            }

            $productionStats->push([
                'user'                            => $employee,
                'total_batches'                   => $totalBatches,
                'batches_with_target'             => $batchesWithTarget,
                'on_time_batches'                 => $onTimeCount,
                'late_batches'                    => $lateCount,
                'faster_batches'                  => $fasterCount,
                'exact_batches'                   => $exactCount,
                'on_time_percentage'              => $onTimePercentage,
                'on_time_percentage_text'         => $onTimePercentage !== null ? number_format($onTimePercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
                'total_target_qty'                => $totalTargetQty,
                'total_actual_qty'                => $totalActualQty,
                'qty_achievement_percentage'      => $qtyAchievementPercentage,
                'qty_achievement_percentage_text' => $qtyAchievementPercentage !== null ? number_format($qtyAchievementPercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
                'batches'                         => $batches,
                // Active Workload & Bagian 7 Fields
                'active_batches_count'            => $activeCount,
                'active_target_qty'               => $activeTargetQty,
                'active_actual_qty'               => $activeActualQty,
                'active_remaining_qty'            => $activeRemainingQty,
                'achievement_percentage'          => $personalAchievementPct,
                'closest_deadline'                => $closestDeadline,
                'closest_deadline_formatted'      => $closestDeadline ? $closestDeadline->format('d M Y') : '-',
                'has_overdue'                     => $hasOverdue,
                'workload_status'                 => $workloadStatus,
                'workload_badge_class'            => $workloadBadgeClass,
            ]);

            $grandTotalBatches += $totalBatches;
            $grandBatchesWithTarget += $batchesWithTarget;
            $grandOnTimeBatches += $onTimeCount;
            $grandTotalTargetQty += $totalTargetQty;
            $grandTotalActualQty += $totalActualQty;
        }

        // Aggregate active batches in tenant
        $activeBatchesGlobalQuery = ProductionBatch::whereNotIn('status', [BatchStatus::SELESAI->value, BatchStatus::SELESAI_PARSIAL->value, BatchStatus::CANCELLED->value]);
        if ($outletId !== null) {
            $activeBatchesGlobalQuery->where(function ($q) use ($outletId) {
                $q->forOutlet($outletId)
                    ->orWhereHas('orderItem.order', fn ($oq) => $oq->whereNull('outlet_id'));
            });
        }
        $allActiveBatches = $activeBatchesGlobalQuery->with('productionResult')->get();
        $totalActiveBatches = $allActiveBatches->count();
        $totalOverdueBatches = $allActiveBatches->filter(fn ($b) => $b->planned_end_date && $b->planned_end_date->lt($today))->count();
        $totalActiveTargetQty = $allActiveBatches->sum(fn ($b) => (float) $b->target_qty);
        $totalActiveActualQty = $allActiveBatches->sum(fn ($b) => (float) ($b->productionResult?->actual_qty ?? 0));
        $totalActiveRemainingQty = max(0.0, $totalActiveTargetQty - $totalActiveActualQty);

        // Unassigned batches in the period
        $unassignedQuery = ProductionBatch::whereNull('production_employee_id')
            ->whereHas('productionResult');

        if ($outletId !== null) {
            $unassignedQuery->where(function ($q) use ($outletId) {
                $q->forOutlet($outletId)
                    ->orWhereHas('orderItem.order', fn ($oq) => $oq->whereNull('outlet_id'));
            });
        }

        if ($preset !== 'all') {
            $unassignedQuery->whereHas('productionResult', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('result_date', [$startDate, $endDate]);
            });
        }
        $unassignedBatchesCount = $unassignedQuery->count();

        $overallOnTimePercentage = $grandBatchesWithTarget > 0
            ? round(($grandOnTimeBatches / $grandBatchesWithTarget) * 100, 2)
            : null;

        $overallQtyPercentage = $grandTotalTargetQty > 0
            ? round(($grandTotalActualQty / $grandTotalTargetQty) * 100, 2)
            : null;

        return [
            'production_stats'                => $productionStats,
            'grand_total_batches'             => $grandTotalBatches,
            'grand_batches_with_target'       => $grandBatchesWithTarget,
            'grand_on_time_batches'           => $grandOnTimeBatches,
            'overall_on_time_percentage'      => $overallOnTimePercentage,
            'overall_on_time_text'            => $overallOnTimePercentage !== null ? number_format($overallOnTimePercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
            'grand_total_target_qty'          => $grandTotalTargetQty,
            'grand_total_actual_qty'          => $grandTotalActualQty,
            'overall_qty_percentage'          => $overallQtyPercentage,
            'overall_qty_text'                => $overallQtyPercentage !== null ? number_format($overallQtyPercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
            'unassigned_batches_count'        => $unassignedBatchesCount,
            'start_date'                      => $startDate,
            'end_date'                        => $endDate,
            'preset'                          => $preset,

            // Bagian 7: Aggregate Monitoring Stats for Owner
            'total_production_staff'          => $productionUsers->count(),
            'active_production_staff'         => $activeStaffCount,
            'available_production_staff'      => $availableStaffCount,
            'target_met_staff'                => $targetMetStaffCount,
            'total_active_batches'            => $totalActiveBatches,
            'total_overdue_batches'           => $totalOverdueBatches,
            'total_active_target_qty'         => $totalActiveTargetQty,
            'total_active_actual_qty'         => $totalActiveActualQty,
            'total_active_remaining_qty'      => $totalActiveRemainingQty,
        ];
    }

    /**
     * Get live workload details and recommendations for active production employees in an outlet.
     *
     * @param int|null $outletId
     * @return Collection
     */
    public function getProductionEmployeesWorkload(?int $outletId = null): Collection
    {
        $query = User::where('role', UserRole::PRODUKSI)
            ->where('status', \App\Enums\UserStatus::ACTIVE);

        if ($outletId !== null) {
            $query->where(function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)
                    ->orWhereNull('outlet_id');
            });
        }

        $employees = $query->orderBy('name')->get();
        $today = Carbon::today('Asia/Jakarta');

        return $employees->map(function (User $employee) use ($today, $outletId) {
            $activeBatches = ProductionBatch::where('production_employee_id', $employee->id)
                ->whereNotIn('status', [BatchStatus::SELESAI->value, BatchStatus::SELESAI_PARSIAL->value, BatchStatus::CANCELLED->value])
                ->when($outletId !== null, fn ($q) => $q->forOutlet($outletId))
                ->with(['productionResult', 'orderItem.order'])
                ->orderBy('planned_end_date')
                ->get();

            $activeCount = $activeBatches->count();
            $targetQty = 0.0;
            $actualQty = 0.0;
            $unstartedCount = 0;
            $hasOverdue = false;
            $closestDeadline = null;

            foreach ($activeBatches as $b) {
                $targetQty += (float) $b->target_qty;
                $res = $b->productionResult;
                if ($res) {
                    $actualQty += (float) $res->actual_qty;
                }
                if (in_array($b->status, [BatchStatus::BAHAN, BatchStatus::PRINTING], true)) {
                    $unstartedCount++;
                }
                if ($b->planned_end_date) {
                    if ($b->planned_end_date->lt($today)) {
                        $hasOverdue = true;
                    }
                    if ($closestDeadline === null || $b->planned_end_date->lt($closestDeadline)) {
                        $closestDeadline = $b->planned_end_date;
                    }
                }
            }

            $remainingQty = max(0.0, $targetQty - $actualQty);

            // Status & Recommendation
            if ($activeCount === 0) {
                $workloadStatus = 'Tersedia';
                $workloadBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                $isRecommended = true;
                $recommendationLabel = 'Direkomendasikan';
                $recommendationBadgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                $recommendationReason = 'Kandidat tersedia, belum memiliki batch aktif.';
            } elseif ($hasOverdue || $activeCount >= 3) {
                $workloadStatus = 'Beban Tinggi';
                $workloadBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                $isRecommended = false;
                $recommendationLabel = 'Beban Tinggi';
                $recommendationBadgeClass = 'bg-rose-100 text-rose-800 border-rose-300';
                $recommendationReason = $hasOverdue
                    ? 'Memiliki batch yang melewati batas waktu (deadline).'
                    : "Sedang menangani {$activeCount} batch aktif sekaligus.";
            } else {
                $workloadStatus = 'Beban Sedang';
                $workloadBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                $isRecommended = true;
                $recommendationLabel = 'Direkomendasikan';
                $recommendationBadgeClass = 'bg-blue-100 text-blue-800 border-blue-300';
                $recommendationReason = "Beban kerja sedang ({$activeCount} batch aktif, sisa target " . number_format($remainingQty) . " pcs).";
            }

            return [
                'employee'                   => $employee,
                'id'                         => $employee->id,
                'name'                       => $employee->name,
                'email'                      => $employee->email,
                'active_batches_count'       => $activeCount,
                'active_target_qty'          => $targetQty,
                'active_actual_qty'          => $actualQty,
                'active_remaining_qty'       => $remainingQty,
                'unstarted_batches_count'    => $unstartedCount,
                'closest_deadline'           => $closestDeadline,
                'closest_deadline_formatted' => $closestDeadline ? $closestDeadline->format('d M Y') : '-',
                'has_overdue'                => $hasOverdue,
                'workload_status'            => $workloadStatus,
                'workload_badge_class'       => $workloadBadgeClass,
                'is_recommended'             => $isRecommended,
                'recommendation_label'       => $recommendationLabel,
                'recommendation_badge_class' => $recommendationBadgeClass,
                'recommendation_reason'      => $recommendationReason,
            ];
        });
    }

    /**
     * Get detailed production batch breakdown for a single production employee.
     */
    public function getProductionDetail(User $employee, string $startDate, string $endDate, string $preset = 'this_month'): array
    {
        $batchQuery = ProductionBatch::where('production_employee_id', $employee->id)
            ->whereHas('productionResult');

        if ($preset !== 'all') {
            $batchQuery->whereHas('productionResult', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('result_date', [$startDate, $endDate]);
            });
        }

        $batches = $batchQuery->with([
            'productionResult',
            'orderItem.order.customer',
            'orderItem.variant.product',
        ])
        ->orderByDesc(
            ProductionResult::select('result_date')
                ->whereColumn('production_results.batch_id', 'production_batches.id')
                ->limit(1)
        )
        ->orderByDesc('id')
        ->get();

        $totalBatches = $batches->count();
        $batchesWithTarget = 0;
        $onTimeCount = 0;
        $lateCount = 0;
        $fasterCount = 0;
        $exactCount = 0;
        $totalTargetQty = 0.0;
        $totalActualQty = 0.0;

        foreach ($batches as $batch) {
            $res = $batch->productionResult;
            $tQty = (float) $batch->target_qty;
            $aQty = $res ? (float) $res->actual_qty : 0.0;

            $totalTargetQty += $tQty;
            $totalActualQty += $aQty;

            if ($batch->planned_end_date !== null && $res?->result_date !== null) {
                $batchesWithTarget++;
                $diff = $batch->time_difference_days;

                if ($diff !== null && $diff >= 0) {
                    $onTimeCount++;
                    if ($diff > 0) {
                        $fasterCount++;
                    } else {
                        $exactCount++;
                    }
                } else {
                    $lateCount++;
                }
            }
        }

        $onTimePercentage = $batchesWithTarget > 0
            ? round(($onTimeCount / $batchesWithTarget) * 100, 2)
            : null;

        $qtyAchievementPercentage = $totalTargetQty > 0
            ? round(($totalActualQty / $totalTargetQty) * 100, 2)
            : null;

        return [
            'employee'                        => $employee,
            'batches'                         => $batches,
            'total_batches'                   => $totalBatches,
            'batches_with_target'             => $batchesWithTarget,
            'on_time_batches'                 => $onTimeCount,
            'late_batches'                    => $lateCount,
            'faster_batches'                  => $fasterCount,
            'exact_batches'                   => $exactCount,
            'on_time_percentage'              => $onTimePercentage,
            'on_time_percentage_text'         => $onTimePercentage !== null ? number_format($onTimePercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
            'total_target_qty'                => $totalTargetQty,
            'total_actual_qty'                => $totalActualQty,
            'qty_achievement_percentage'      => $qtyAchievementPercentage,
            'qty_achievement_percentage_text' => $qtyAchievementPercentage !== null ? number_format($qtyAchievementPercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
            'start_date'                      => $startDate,
            'end_date'                        => $endDate,
            'preset'                          => $preset,
        ];
    }
}
