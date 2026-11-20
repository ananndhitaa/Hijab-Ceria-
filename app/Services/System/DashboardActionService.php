<?php

namespace App\Services\System;

use App\Enums\BatchStatus;
use App\Enums\MaterialReadiness;
use App\Enums\OrderStatus;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Models\Inventory\Purchase;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Services\Inventory\PurchaseOrderService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardActionService
{
    public function __construct(
        protected PurchaseOrderService $poService
    ) {}

    /**
     * Get actionable items for Admin Dashboard.
     *
     * @param int|null $limit
     * @return Collection
     */
    public function getAdminActions(?int $limit = null): Collection
    {
        $actions = collect();

        // 1. PO Menunggu Persetujuan Admin (Priority 1)
        $pendingApprovals = Purchase::with(['supplier', 'items'])
            ->where('approval_status', PurchaseApprovalStatus::PENDING_APPROVAL)
            ->where('status', '!=', PurchaseStatus::CANCELLED)
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        foreach ($pendingApprovals as $po) {
            $actions->push([
                'priority'     => 1,
                'reference'    => $po->purchase_no,
                'subtitle'     => $po->supplier?->supplier_name ?? '-',
                'status_label' => 'Menunggu Persetujuan',
                'badge_class'  => 'bg-amber-50 text-amber-700 border-amber-200',
                'action_label' => 'Review',
                'action_url'   => route('admin.purchases.approvals.show', $po),
                'button_class' => 'bg-[#8E1B5C] text-white hover:bg-[#68113F]',
            ]);
        }

        // 2. Orders in KOMUNIKASI (Menunggu Deal) (Priority 2)
        $komunikasiOrders = Order::with('customer')
            ->where('status', OrderStatus::KOMUNIKASI)
            ->latest('order_date')
            ->latest('id')
            ->get();

        foreach ($komunikasiOrders as $order) {
            $actions->push([
                'priority'     => 2,
                'reference'    => $order->order_no,
                'subtitle'     => $order->customer?->customer_name ?? '-',
                'status_label' => $order->status_label,
                'badge_class'  => $order->status_badge_class,
                'action_label' => 'Review',
                'action_url'   => route('admin.orders.show', $order),
                'button_class' => 'bg-[#8E1B5C] text-white hover:bg-[#68113F]',
            ]);
        }

        // 3. Approved POs awaiting supplier payment (Menunggu Pembayaran / Dibayar Sebagian) (Priority 3)
        $approvedPurchases = Purchase::with(['supplier', 'items', 'payments'])
            ->where('approval_status', PurchaseApprovalStatus::APPROVED)
            ->where('status', '!=', PurchaseStatus::CANCELLED)
            ->where('status', '!=', PurchaseStatus::ORDERED)
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (Purchase $p) => bccomp($p->outstanding_amount, '0.00', 2) > 0);

        foreach ($approvedPurchases as $po) {
            $isPartial = bccomp($po->paid_amount, '0.00', 2) > 0;
            $actions->push([
                'priority'     => 3,
                'reference'    => $po->purchase_no,
                'subtitle'     => $po->supplier?->supplier_name ?? '-',
                'status_label' => $isPartial ? 'Dibayar Sebagian' : 'Menunggu Pembayaran',
                'badge_class'  => $isPartial ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-amber-50 text-amber-800 border-amber-200',
                'action_label' => 'Bayar',
                'action_url'   => route('admin.purchases.payments.create', $po),
                'button_class' => 'bg-[#8E1B5C] text-white hover:bg-[#68113F]',
            ]);
        }

        // 4. DEAL orders awaiting production batch (Siap Batch) (Priority 4)
        $dealOrders = Order::with(['customer', 'items.productionBatches'])
            ->where('status', OrderStatus::DEAL)
            ->whereDoesntHave('items.productionBatches')
            ->latest('order_date')
            ->latest('id')
            ->get();

        foreach ($dealOrders as $order) {
            $actions->push([
                'priority'     => 4,
                'reference'    => $order->order_no,
                'subtitle'     => $order->customer?->customer_name ?? '-',
                'status_label' => 'Siap Batch',
                'badge_class'  => 'bg-purple-50 text-purple-700 border-purple-200',
                'action_label' => 'Buat Batch',
                'action_url'   => route('admin.batches.index'),
                'button_class' => 'bg-[#8E1B5C] text-white hover:bg-[#68113F]',
            ]);
        }

        $sorted = $actions->sortBy('priority')->values();

        return $limit !== null ? $sorted->take($limit) : $sorted;
    }

    /**
     * Get actionable items for Owner Dashboard (Owner no longer approves POs).
     *
     * @param int $limit
     * @return Collection
     */
    public function getOwnerActions(int $limit = 5): Collection
    {
        return collect();
    }

    /**
     * Get actionable items for Produksi Dashboard.
     *
     * @param int $limit
     * @param int|null $employeeId
     * @return Collection
     */
    public function getProduksiActions(int $limit = 5, ?int $employeeId = null): Collection
    {
        $actions = collect();
        $today = Carbon::today('Asia/Jakarta');

        $activeBatchesQuery = ProductionBatch::with([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'batchMaterials',
        ])
            ->whereNotIn('status', [BatchStatus::SELESAI->value, BatchStatus::CANCELLED->value]);

        if ($employeeId !== null) {
            $activeBatchesQuery->where('production_employee_id', $employeeId);
        }

        $activeBatches = $activeBatchesQuery->get();

        foreach ($activeBatches as $batch) {
            $isOverdue = $batch->planned_end_date && $batch->planned_end_date->lt($today);
            $targetFormatted = $batch->planned_end_date ? 'Target ' . $batch->planned_end_date->translatedFormat('d M') : null;

            if ($isOverdue) {
                // Priority 1: Melewati Target
                $actions->push([
                    'priority'     => 1,
                    'reference'    => $batch->batch_no,
                    'subtitle'     => ($batch->orderItem?->variant?->product?->product_name ?? 'Produk') . ' (' . number_format($batch->target_qty) . ' pcs)',
                    'status_label' => 'Melewati Target',
                    'target_label' => $targetFormatted,
                    'badge_class'  => 'bg-orange-50 text-orange-800 border-orange-300 font-bold',
                    'action_label' => 'Buka',
                    'action_url'   => route('produksi.batches.show', $batch),
                    'button_class' => 'bg-rose-700 text-white hover:bg-rose-800',
                ]);
            } elseif ($batch->status === BatchStatus::QC) {
                // Priority 2: Menunggu QC
                $actions->push([
                    'priority'     => 2,
                    'reference'    => $batch->batch_no,
                    'subtitle'     => ($batch->orderItem?->variant?->product?->product_name ?? 'Produk') . ' (' . number_format($batch->target_qty) . ' pcs)',
                    'status_label' => 'Menunggu QC',
                    'target_label' => $targetFormatted,
                    'badge_class'  => 'bg-sky-50 text-sky-700 border-sky-200',
                    'action_label' => 'Input QC',
                    'action_url'   => route('produksi.batches.qc.create', $batch),
                    'button_class' => 'bg-amber-600 text-white hover:bg-amber-700',
                ]);
            } elseif ($batch->status === BatchStatus::BAHAN && $batch->isMaterialReady()) {
                // Priority 3: Siap Dikerjakan
                $actions->push([
                    'priority'     => 3,
                    'reference'    => $batch->batch_no,
                    'subtitle'     => ($batch->orderItem?->variant?->product?->product_name ?? 'Produk') . ' (' . number_format($batch->target_qty) . ' pcs)',
                    'status_label' => 'Siap Dikerjakan',
                    'target_label' => $targetFormatted,
                    'badge_class'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'action_label' => 'Mulai',
                    'action_url'   => route('produksi.batches.show', $batch),
                    'button_class' => 'bg-emerald-700 text-white hover:bg-emerald-800',
                ]);
            } elseif (in_array($batch->status, [BatchStatus::PRINTING, BatchStatus::PRODUKSI], true)) {
                // Priority 4: Dalam Produksi
                $actions->push([
                    'priority'     => 4,
                    'reference'    => $batch->batch_no,
                    'subtitle'     => ($batch->orderItem?->variant?->product?->product_name ?? 'Produk') . ' (' . number_format($batch->target_qty) . ' pcs)',
                    'status_label' => 'Dalam Produksi',
                    'target_label' => $targetFormatted,
                    'badge_class'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    'action_label' => 'Catat Hasil',
                    'action_url'   => route('produksi.batches.production-result.create', $batch),
                    'button_class' => 'border border-[#EBDDE4] bg-white text-[#172B4D] hover:bg-[#F7E6EE]',
                ]);
            }
        }

        return $actions->sortBy('priority')->values()->take($limit);
    }

    /**
     * Get actionable items for Gudang Dashboard (Purchases + MRP Batches).
     *
     * @param int $limit
     * @return array{total: int, items: Collection}
     */
    public function getGudangActions(int $limit = 5): array
    {
        $allActions = collect();

        // 1. Domain A: Purchase Orders
        $purchases = Purchase::with(['supplier', 'items', 'payments'])
            ->whereNotIn('status', [PurchaseStatus::RECEIVED, PurchaseStatus::CANCELLED])
            ->get();

        foreach ($purchases as $purchase) {
            $mapped = $this->poService->mapActionQueueItem($purchase);
            if ($mapped !== null) {
                $allActions->push($mapped);
            }
        }

        // 2. Domain B: Material / MRP Batches
        $batches = ProductionBatch::with([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'batchMaterials.material.stock',
        ])
            ->whereNotIn('status', [BatchStatus::SELESAI->value, BatchStatus::CANCELLED->value])
            ->get();

        foreach ($batches as $batch) {
            $readiness = $batch->material_readiness;

            $productName = $batch->orderItem?->variant?->product?->product_name ?? 'Produk';
            $variantName = $batch->orderItem?->variant?->variant_name;
            $combinedTitle = $variantName ? "{$productName} - {$variantName}" : $productName;

            $sortKey = ($batch->planned_end_date ? $batch->planned_end_date->format('Y-m-d') : '1970-01-01')
                . '_'
                . ($batch->created_at ? $batch->created_at->format('Y-m-d_H:i:s') : '00:00:00')
                . '_'
                . str_pad((string) $batch->id, 8, '0', STR_PAD_LEFT);

            $dateLabel = $batch->created_at ? $batch->created_at->format('d/m/Y') : '-';

            if ($readiness === MaterialReadiness::SIAP_DIALOKASIKAN) {
                // Priority 2: Siap Dialokasikan
                $allActions->push([
                    'domain'        => 'batch',
                    'batch'         => $batch,
                    'batch_id'      => $batch->id,
                    'purchase_no'   => $batch->batch_no,
                    'reference'     => $batch->batch_no,
                    'supplier_name' => $combinedTitle,
                    'subtitle'      => $combinedTitle,
                    'date'          => $dateLabel,
                    'priority'      => 2,
                    'status_label'  => 'Siap Dialokasikan',
                    'action_label'  => 'Alokasikan',
                    'badge_class'   => 'bg-[#F7E6EE] text-[#8E1B5C] border border-[#EBDDE4]',
                    'button_class'  => 'bg-[#8E1B5C] text-white hover:bg-[#68113F]',
                    'action_url'    => route('gudang.material-planning.show', $batch),
                    'sort_key'      => $sortKey,
                ]);
            } elseif ($readiness === MaterialReadiness::PERLU_PEMBELIAN) {
                // Priority 5: Kekurangan Bahan
                $allActions->push([
                    'domain'        => 'batch',
                    'batch'         => $batch,
                    'batch_id'      => $batch->id,
                    'purchase_no'   => $batch->batch_no,
                    'reference'     => $batch->batch_no,
                    'supplier_name' => $combinedTitle,
                    'subtitle'      => $combinedTitle,
                    'date'          => $dateLabel,
                    'priority'      => 5,
                    'status_label'  => 'Kekurangan Bahan',
                    'action_label'  => 'Buka',
                    'badge_class'   => 'bg-amber-50 text-amber-800 border-amber-200',
                    'button_class'  => 'border border-[#8E1B5C] text-[#8E1B5C] bg-white hover:bg-[#F7E6EE]',
                    'action_url'    => route('gudang.material-planning.show', $batch),
                    'sort_key'      => $sortKey,
                ]);
            }
        }

        $sorted = $allActions->sort(function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }
            return strcmp($b['sort_key'], $a['sort_key']);
        })->values();

        return [
            'total' => $sorted->count(),
            'items' => $sorted->take($limit),
        ];
    }
}
