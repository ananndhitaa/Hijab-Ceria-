<?php

namespace App\Http\Controllers\Produksi;

use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Services\System\DashboardActionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $employeeId = auth()->id();
        $today = Carbon::today('Asia/Jakarta');

        // 1. Strict employee-scoped active batches base query
        $activeBatchesQuery = ProductionBatch::where('production_employee_id', $employeeId)
            ->whereNotIn('status', [BatchStatus::SELESAI->value, BatchStatus::CANCELLED->value]);

        $activeBatchesCount = (clone $activeBatchesQuery)->count();

        $overdueCount = (clone $activeBatchesQuery)
            ->whereNotNull('planned_end_date')
            ->where('planned_end_date', '<', $today)
            ->count();

        $waitingQcCount = (clone $activeBatchesQuery)
            ->where('status', BatchStatus::QC->value)
            ->count();

        $inProgressCount = (clone $activeBatchesQuery)->whereIn('status', [
            BatchStatus::PRINTING->value,
            BatchStatus::PRODUKSI->value,
            BatchStatus::QC->value,
            BatchStatus::PACKING->value,
        ])->count();

        $completedCount = ProductionBatch::where('production_employee_id', $employeeId)
            ->where('status', BatchStatus::SELESAI->value)
            ->count();

        // Batches at BAHAN assigned to this employee to calculate material readiness
        $bahanBatches = ProductionBatch::where('production_employee_id', $employeeId)
            ->where('status', BatchStatus::BAHAN->value)
            ->with('batchMaterials')
            ->get();

        $readyToStartCount = $bahanBatches->filter(fn (ProductionBatch $b) => $b->isMaterialReady())->count();

        // 2. Strict employee-scoped actionable items
        $actionService = app(DashboardActionService::class);
        $produksiActions = $actionService->getProduksiActions(5, $employeeId);

        // 3. Assigned jobs query with sorting & filters (Bagian 5)
        $sort = $request->input('sort', 'deadline');
        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $jobsQuery = ProductionBatch::with([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'batchMaterials',
            'productionResult',
            'qcResult',
            'picHistories' => fn ($q) => $q->where('to_employee_id', $employeeId)->latest('assigned_at'),
        ])
            ->where('production_batches.production_employee_id', $employeeId)
            ->whereNotIn('production_batches.status', [BatchStatus::SELESAI->value, BatchStatus::CANCELLED->value]);

        if ($search) {
            $jobsQuery->where(function ($q) use ($search) {
                $q->where('production_batches.batch_no', 'like', "%{$search}%")
                    ->orWhereHas('orderItem.order', function ($oq) use ($search) {
                        $oq->where('order_no', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('customer_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('orderItem.variant', function ($vq) use ($search) {
                        $vq->where('variant_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($pq) use ($search) {
                                $pq->where('product_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($statusFilter && $statusEnum = BatchStatus::tryFrom($statusFilter)) {
            $jobsQuery->where('production_batches.status', $statusEnum->value);
        }

        // Apply authoritative sorting
        if ($sort === 'newest_order') {
            // Newest Sales Order creation date
            $jobsQuery->join('order_items', 'production_batches.order_item_id', '=', 'order_items.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->select('production_batches.*')
                ->orderByDesc('orders.created_at');
        } elseif ($sort === 'oldest_order') {
            // Oldest Sales Order creation date
            $jobsQuery->join('order_items', 'production_batches.order_item_id', '=', 'order_items.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->select('production_batches.*')
                ->orderBy('orders.created_at', 'asc');
        } else {
            // Default: 'deadline' (Overdue first, then closest deadline ASC, tie-breaker order created_at ASC)
            $todayStr = $today->toDateString();
            $jobsQuery->join('order_items', 'production_batches.order_item_id', '=', 'order_items.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->select('production_batches.*')
                ->orderByRaw("
                    CASE 
                        WHEN production_batches.planned_end_date IS NOT NULL AND production_batches.planned_end_date < '{$todayStr}' THEN 0
                        WHEN production_batches.planned_end_date IS NOT NULL THEN 1
                        ELSE 2
                    END ASC
                ")
                ->orderBy('production_batches.planned_end_date', 'asc')
                ->orderBy('orders.created_at', 'asc');
        }

        $assignedJobs = $jobsQuery->get();

        // Calculate personal aggregate targets
        $personalTotalTarget = $assignedJobs->sum(fn ($b) => (float) $b->target_qty);
        $personalTotalActual = $assignedJobs->sum(fn ($b) => (float) ($b->productionResult?->actual_qty ?? 0));
        $personalTotalRemaining = max(0.0, $personalTotalTarget - $personalTotalActual);

        return view('dashboard.produksi', compact(
            'activeBatchesCount',
            'readyToStartCount',
            'waitingQcCount',
            'overdueCount',
            'inProgressCount',
            'completedCount',
            'produksiActions',
            'assignedJobs',
            'sort',
            'search',
            'statusFilter',
            'personalTotalTarget',
            'personalTotalActual',
            'personalTotalRemaining'
        ));
    }
}
