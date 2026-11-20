<?php

namespace App\Services\Production;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\Production\BatchPicHistory;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Platform\PlatformNotificationService;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\OrderStatusService;
use App\Services\System\DocumentNumberGenerator;
use App\Services\System\TenantActivityLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

class BatchService
{
    public function __construct(
        private DocumentNumberGenerator $numberGenerator,
        private OrderStatusService $statusService,
        private BatchMaterialPlanningService $planningService,
        private InvoiceService $invoiceService
    ) {}

    /**
     * Calculate the production coverage metrics for an OrderItem:
     * - confirmed_good_qty: SUM(QcResult.good_qty) for non-cancelled batches having a final QcResult.
     * - pending_production_qty: SUM(target_qty) for non-cancelled batches that do NOT yet have a final QcResult.
     * - production_coverage: confirmed_good_qty + pending_production_qty.
     * - remaining_to_plan: MAX(qty_ordered - production_coverage, 0).
     *
     * @param OrderItem $item
     * @return array{
     *     ordered_qty: string,
     *     confirmed_good_qty: string,
     *     pending_production_qty: string,
     *     production_coverage: string,
     *     remaining_to_plan: string,
     *     total_reject_qty: string
     * }
     */
    public function calculateProductionCoverage(OrderItem $item): array
    {
        $ordered = (string) $item->qty_ordered;

        $batches = $item->relationLoaded('productionBatches')
            ? $item->productionBatches
            : $item->productionBatches()->with(['productionResults.qcResult'])->get();

        $confirmedGood = '0.0000';
        $pendingProduction = '0.0000';
        $totalReject = '0.0000';

        foreach ($batches as $batch) {
            if ($batch->status === BatchStatus::CANCELLED) {
                continue;
            }

            $batchGood = (string) $batch->total_good_qty;
            $batchReject = (string) $batch->total_reject_qty;

            if (bccomp($batchGood, '0.0000', 4) > 0 || bccomp($batchReject, '0.0000', 4) > 0) {
                $confirmedGood = bcadd($confirmedGood, $batchGood, 4);
                $totalReject = bcadd($totalReject, $batchReject, 4);

                // If batch was actively resumed back to BAHAN, PRINTING, or PRODUKSI, remaining target is pending
                if (in_array($batch->status, [BatchStatus::BAHAN, BatchStatus::PRINTING, BatchStatus::PRODUKSI], true)) {
                    $remTarget = bcsub((string) $batch->target_qty, (string) $batch->total_actual_qty, 4);
                    if (bccomp($remTarget, '0.0000', 4) > 0) {
                        $pendingProduction = bcadd($pendingProduction, $remTarget, 4);
                    }
                }
            } else {
                if (! $batch->status->isTerminal()) {
                    $pendingProduction = bcadd($pendingProduction, (string) $batch->target_qty, 4);
                }
            }
        }

        $productionCoverage = bcadd($confirmedGood, $pendingProduction, 4);
        $diff = bcsub($ordered, $productionCoverage, 4);
        $remainingToPlan = bccomp($diff, '0.0000', 4) < 0 ? '0.0000' : $diff;

        return [
            'ordered_qty'            => $ordered,
            'confirmed_good_qty'     => $confirmedGood,
            'pending_production_qty' => $pendingProduction,
            'production_coverage'    => $productionCoverage,
            'remaining_to_plan'      => $remainingToPlan,
            'total_reject_qty'       => $totalReject,
        ];
    }

    /**
     * Calculate the remaining unbatched / replacement quantity for an OrderItem.
     * Uses production coverage formula (confirmed GOOD from QC + pending target_qty).
     *
     * @param OrderItem $item
     * @return string
     */
    public function calculateRemainingQty(OrderItem $item): string
    {
        return $this->calculateProductionCoverage($item)['remaining_to_plan'];
    }

    /**
     * Calculate the current production coverage quantity for an OrderItem.
     *
     * @param OrderItem $item
     * @return string
     */
    public function calculateCommittedQty(OrderItem $item): string
    {
        return $this->calculateProductionCoverage($item)['production_coverage'];
    }

    /**
     * Check if an Order has remaining unbatched capacity across its items.
     *
     * @param Order $order
     * @return bool
     */
    public function hasRemainingCapacity(Order $order): bool
    {
        if (! $order->relationLoaded('items')) {
            $order->load(['items.productionBatches']);
        }

        foreach ($order->items as $item) {
            if (bccomp($this->calculateRemainingQty($item), '0.0000', 4) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an Order can have a ProductionBatch created.
     * Allowed only if:
     * 1. Order is in DEAL or BAHAN.
     * 2. Order has a valid confirmed Down Payment (DP > 0).
     * 3. At least one item has remaining unbatched quantity > 0.
     *
     * @param Order $order
     * @return bool
     */
    public function canCreateForOrder(Order $order): bool
    {
        if (! in_array($order->status, [OrderStatus::DEAL, OrderStatus::BAHAN], true)) {
            return false;
        }

        if (! $this->invoiceService->hasConfirmedDownPayment($order)) {
            return false;
        }

        return $this->hasRemainingCapacity($order);
    }

    /**
     * Check if a specific OrderItem can have a ProductionBatch created.
     * Allowed only if Order is in DEAL/BAHAN, DP is confirmed, and item has remaining unbatched qty.
     *
     * @param OrderItem $item
     * @param Order|null $order
     * @return bool
     */
    public function canCreateForOrderItem(OrderItem $item, ?Order $order = null): bool
    {
        $order = $order ?? $item->order;

        if (! in_array($order->status, [OrderStatus::DEAL, OrderStatus::BAHAN], true)) {
            return false;
        }

        if (! $this->invoiceService->hasConfirmedDownPayment($order)) {
            return false;
        }

        return bccomp($this->calculateRemainingQty($item), '0.0000', 4) > 0;
    }

    /**
     * Atomically create a ProductionBatch with strict row-locking order, capacity validation,
     * routing check, BOM master recipe validation, and automatic BatchMaterial snapshot creation.
     *
     * @param Order $order
     * @param array $data
     * @param User $user
     * @return array{batch: ProductionBatch, was_deal: bool}
     * @throws DomainException
     */
    public function createBatch(Order $order, array $data, User $user): array
    {
        return DB::transaction(function () use ($order, $data, $user) {
            // 1. Lock Order row
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // 2. Verify order status eligibility
            if (! in_array($lockedOrder->status, [OrderStatus::DEAL, OrderStatus::BAHAN], true)) {
                throw new DomainException("Batch produksi hanya dapat dibuat untuk pesanan dengan status DEAL atau BAHAN (status saat ini: {$lockedOrder->status->label()}).");
            }

            // 2b. Verify down payment gate (DP must be confirmed > 0)
            if (! $this->invoiceService->hasConfirmedDownPayment($lockedOrder)) {
                throw new DomainException('Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.');
            }

            // 3. Lock OrderItem row
            /** @var OrderItem $lockedItem */
            $lockedItem = OrderItem::where('id', $data['order_item_id'])->lockForUpdate()->firstOrFail();

            // 4. Verify OrderItem belongs to the locked Order
            if ((int) $lockedItem->order_id !== (int) $lockedOrder->id) {
                throw new DomainException('Item pesanan yang dipilih tidak sesuai dengan pesanan ini.');
            }

            // 5. Calculate production coverage from existing non-CANCELLED batches under lock
            $existingBatches = ProductionBatch::where('order_item_id', $lockedItem->id)
                ->where('status', '!=', BatchStatus::CANCELLED->value)
                ->with(['productionResults.qcResult'])
                ->lockForUpdate()
                ->get();

            $confirmedGood = '0.0000';
            $pendingProduction = '0.0000';
            foreach ($existingBatches as $eb) {
                $batchGood = (string) $eb->total_good_qty;
                if (bccomp($batchGood, '0.0000', 4) > 0 || (float) $eb->total_reject_qty > 0) {
                    $confirmedGood = bcadd($confirmedGood, $batchGood, 4);

                    if (in_array($eb->status, [BatchStatus::BAHAN, BatchStatus::PRINTING, BatchStatus::PRODUKSI], true)) {
                        $remTarget = bcsub((string) $eb->target_qty, (string) $eb->total_actual_qty, 4);
                        if (bccomp($remTarget, '0.0000', 4) > 0) {
                            $pendingProduction = bcadd($pendingProduction, $remTarget, 4);
                        }
                    }
                } else {
                    if (! $eb->status->isTerminal()) {
                        $pendingProduction = bcadd($pendingProduction, (string) $eb->target_qty, 4);
                    }
                }
            }

            $coverage = bcadd($confirmedGood, $pendingProduction, 4);
            $diff = bcsub((string) $lockedItem->qty_ordered, $coverage, 4);
            $remainingQty = bccomp($diff, '0.0000', 4) < 0 ? '0.0000' : $diff;

            // 6. Validate requested target_qty
            $targetQty = (string) $data['target_qty'];
            if (bccomp($targetQty, '0.0000', 4) <= 0) {
                throw new DomainException('Target kuantitas produksi harus lebih besar dari 0.');
            }

            if (bccomp($targetQty, $remainingQty, 4) > 0) {
                $remDisplay = (float) $remainingQty;
                $reqDisplay = (float) $targetQty;
                throw new DomainException("Target kuantitas ({$reqDisplay} pcs) melebihi sisa kuantitas pesanan yang belum dijadwalkan ({$remDisplay} pcs).");
            }

            // 8. Validate variant routing configuration
            $variant = $lockedItem->variant;
            if ($variant === null || $variant->requires_printing === null) {
                throw new DomainException('Rute produksi varian belum ditetapkan. Atur kebutuhan Printing pada Master Produk terlebih dahulu.');
            }

            // 9. Validate variant costing configuration
            if ($variant->direct_labor_cost_per_unit === null
                || $variant->packaging_process_cost_per_good_unit === null
                || $variant->other_direct_cost_per_unit === null
                || ($variant->requires_printing && $variant->printing_cost_per_unit === null)
            ) {
                throw new DomainException('Tarif biaya produksi varian belum lengkap. Lengkapi konfigurasi biaya pada Master Produk sebelum membuat Batch.');
            }

            // 9b. Validate tenant consistency when tenant isolation is enabled
            if (config('nayara.tenant_isolation_enabled', false)) {
                $tenantOutletId = null;
                try {
                    $tenantOutletId = app(\App\Services\System\TenantContext::class)->currentOutletId();
                } catch (\Throwable) {
                    $tenantOutletId = null;
                }
                $targetOutletId = $tenantOutletId ?? $lockedOrder->outlet_id;
                if ($targetOutletId !== null) {
                    if ($lockedOrder->outlet_id === null || (int) $lockedOrder->outlet_id !== (int) $targetOutletId) {
                        throw new DomainException('Pesanan bukan milik outlet aktif.');
                    }
                    if ($variant->outlet_id !== null && (int) $variant->outlet_id !== (int) $targetOutletId) {
                        throw new DomainException('Varian produk bukan milik outlet pesanan ini.');
                    }
                    if ($variant->product && $variant->product->outlet_id !== null && (int) $variant->product->outlet_id !== (int) $targetOutletId) {
                        throw new DomainException('Produk bukan milik outlet pesanan ini.');
                    }
                    if (! empty($data['production_employee_id'])) {
                        $employee = User::find($data['production_employee_id']);
                        if ($employee && $employee->outlet_id !== null && (int) $employee->outlet_id !== (int) $targetOutletId) {
                            throw new DomainException('Karyawan produksi bukan milik outlet pesanan ini.');
                        }
                    }
                }
            }

            // 10. Validate BOM master recipe and obtain authoritative collection (prevents TOCTOU gap)
            $validatedBomItems = $this->planningService->validateBom($variant);

            // 11. Allocate BAT document number
            $batchNo = $this->numberGenerator->generate('BAT');

            // 12. Create ProductionBatch with immutable routing & cost rate snapshots
            $batch = ProductionBatch::create([
                'order_item_id'                                 => $lockedItem->id,
                'production_employee_id'                        => $data['production_employee_id'] ?? null,
                'batch_no'                                      => $batchNo,
                'target_qty'                                    => $targetQty,
                'status'                                        => BatchStatus::BAHAN,
                'requires_printing'                             => (bool) $variant->requires_printing,
                'printing_cost_per_unit_snapshot'               => $variant->requires_printing ? (string) $variant->printing_cost_per_unit : '0.00',
                'direct_labor_cost_per_unit_snapshot'           => (string) $variant->direct_labor_cost_per_unit,
                'packaging_process_cost_per_good_unit_snapshot' => (string) $variant->packaging_process_cost_per_good_unit,
                'other_direct_cost_per_unit_snapshot'           => (string) $variant->other_direct_cost_per_unit,
                'planned_start_date'                            => $data['planned_start_date'] ?? null,
                'planned_end_date'                              => $data['planned_end_date'] ?? null,
                'notes'                                         => $data['notes'] ?? null,
            ]);

            // 13. Automatically generate BatchMaterial snapshot using the same validated BOM items
            $planningResult = $this->planningService->generateMaterialPlanning($batch, $validatedBomItems);

            // 14. Enforce invariant: every newly created Batch must have at least one BatchMaterial snapshot
            if (! $planningResult['created'] || $planningResult['count'] <= 0) {
                throw new DomainException('Gagal membuat snapshot kebutuhan bahan untuk batch produksi.');
            }

            // 14. OrderStatus handoff if Order was DEAL
            $wasDeal = $this->statusService->transitionToBahanForBatch($lockedOrder, $user, $batch);

            $rawOutletId = (int) ($lockedOrder->outlet_id ?? $targetOutletId ?? 0);
            $resolvedOutletId = ($rawOutletId > 0 && \App\Models\System\Outlet::where('id', $rawOutletId)->exists()) ? $rawOutletId : null;

            // 15. Record initial PIC assignment history & notification
            if (! empty($batch->production_employee_id)) {
                $assignedEmployee = User::find($batch->production_employee_id);

                BatchPicHistory::create([
                    'batch_id'         => $batch->id,
                    'outlet_id'        => $resolvedOutletId,
                    'assigned_by'      => $user->id,
                    'from_employee_id' => null,
                    'to_employee_id'   => $batch->production_employee_id,
                    'assigned_at'      => now(),
                    'notes'            => 'Penugasan awal PIC saat pembuatan batch.',
                ]);

                if ($assignedEmployee) {
                    app(PlatformNotificationService::class)->notify(
                        outletId: $resolvedOutletId,
                        userId: $assignedEmployee->id,
                        type: 'BATCH_ASSIGNED',
                        title: 'Penugasan Batch Baru',
                        message: "Anda ditugaskan pada Batch Produksi {$batch->batch_no} (Target: " . number_format($batch->target_qty) . " pcs, Deadline: " . ($batch->planned_end_date ? $batch->planned_end_date->format('d/m/Y') : '-') . ").",
                        actionUrl: route('produksi.batches.show', $batch, false)
                    );
                }

                $targetQtyDisplay = (float) $batch->target_qty == (int) $batch->target_qty
                    ? number_format((float) $batch->target_qty, 0, ',', '.')
                    : number_format((float) $batch->target_qty, 2, ',', '.');

                TenantActivityLogger::log(
                    outletId: $resolvedOutletId,
                    user: $user,
                    module: 'BATCH_PRODUKSI',
                    actionType: 'CREATE_BATCH',
                    description: "Batch produksi {$batch->batch_no} dibuat dengan target {$targetQtyDisplay} pcs dan PIC ditugaskan kepada " . ($assignedEmployee?->name ?? 'karyawan') . ".",
                    referenceNo: $batch->batch_no,
                    subject: $batch,
                    afterValues: [
                        'batch_no'         => $batch->batch_no,
                        'target_qty'       => (float) $batch->target_qty,
                        'pic'              => $assignedEmployee?->name,
                        'planned_end_date' => $batch->planned_end_date?->toDateString(),
                    ]
                );
            } else {
                $targetQtyDisplay = (float) $batch->target_qty == (int) $batch->target_qty
                    ? number_format((float) $batch->target_qty, 0, ',', '.')
                    : number_format((float) $batch->target_qty, 2, ',', '.');

                TenantActivityLogger::log(
                    outletId: $resolvedOutletId,
                    user: $user,
                    module: 'BATCH_PRODUKSI',
                    actionType: 'CREATE_BATCH',
                    description: "Batch produksi {$batch->batch_no} dibuat dengan target {$targetQtyDisplay} pcs tanpa PIC (draft/unassigned).",
                    referenceNo: $batch->batch_no,
                    subject: $batch,
                    afterValues: [
                        'batch_no'         => $batch->batch_no,
                        'target_qty'       => (float) $batch->target_qty,
                        'pic'              => null,
                        'planned_end_date' => $batch->planned_end_date?->toDateString(),
                    ]
                );
            }

            return [
                'batch'    => $batch,
                'was_deal' => $wasDeal,
            ];
        });
    }

    /**
     * Reassign PIC for an existing production batch.
     * Revokes old PIC access, grants new PIC access, records audit history, logs activity, and notifies both parties.
     *
     * @param ProductionBatch $batch
     * @param int|null $newEmployeeId
     * @param User $actor
     * @param string|null $notes
     * @return ProductionBatch
     * @throws DomainException
     */
    public function reassignPic(ProductionBatch $batch, ?int $newEmployeeId, User $actor, ?string $notes = null): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $newEmployeeId, $actor, $notes) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)->lockForUpdate()->firstOrFail();
            $oldEmployeeId = $lockedBatch->production_employee_id;
            $outletId = $lockedBatch->orderItem?->order?->outlet_id;

            if ($newEmployeeId !== null) {
                $newEmployee = User::where('id', $newEmployeeId)
                    ->where('role', \App\Enums\UserRole::PRODUKSI)
                    ->firstOrFail();

                if ($outletId && $newEmployee->outlet_id && (int) $newEmployee->outlet_id !== (int) $outletId) {
                    throw new DomainException('Karyawan produksi bukan milik outlet batch ini.');
                }
            }

            if ((int) $oldEmployeeId === (int) $newEmployeeId) {
                return $lockedBatch;
            }

            $oldEmployee = $oldEmployeeId ? User::find($oldEmployeeId) : null;
            $newEmployee = $newEmployeeId ? User::find($newEmployeeId) : null;

            $lockedBatch->production_employee_id = $newEmployeeId;
            $lockedBatch->save();

            $rawOutletId = (int) ($outletId ?? 0);
            $resolvedOutletId = ($rawOutletId > 0 && \App\Models\System\Outlet::where('id', $rawOutletId)->exists()) ? $rawOutletId : null;

            // 1. Record History
            BatchPicHistory::create([
                'batch_id'         => $lockedBatch->id,
                'outlet_id'        => $resolvedOutletId,
                'assigned_by'      => $actor->id,
                'from_employee_id' => $oldEmployeeId,
                'to_employee_id'   => $newEmployeeId,
                'assigned_at'      => now(),
                'notes'            => $notes ?? 'Pengalihan PIC Produksi oleh ' . $actor->name,
            ]);

            // 2. Activity Log
            TenantActivityLogger::log(
                outletId: $resolvedOutletId,
                user: $actor,
                module: 'BATCH_PRODUKSI',
                actionType: 'REASSIGN_PIC',
                description: "PIC Batch {$lockedBatch->batch_no} dialihkan dari " . ($oldEmployee?->name ?? 'Belum Ada PIC') . ' ke ' . ($newEmployee?->name ?? 'Belum Ditugaskan') . '.',
                referenceNo: $lockedBatch->batch_no,
                subject: $lockedBatch,
                beforeValues: ['pic_id' => $oldEmployeeId, 'pic_name' => $oldEmployee?->name],
                afterValues: ['pic_id' => $newEmployeeId, 'pic_name' => $newEmployee?->name, 'notes' => $notes]
            );

            // 3. Notifications
            $notifService = app(PlatformNotificationService::class);
            if ($oldEmployee) {
                $notifService->notify(
                    outletId: $resolvedOutletId,
                    userId: $oldEmployee->id,
                    type: 'BATCH_UNASSIGNED',
                    title: 'Penugasan Batch Dialihkan',
                    message: "Penugasan Batch {$lockedBatch->batch_no} telah dialihkan ke karyawan lain oleh {$actor->name}.",
                    actionUrl: null
                );
            }

            if ($newEmployee) {
                $notifService->notify(
                    outletId: $resolvedOutletId,
                    userId: $newEmployee->id,
                    type: 'BATCH_ASSIGNED',
                    title: 'Penugasan Batch Baru',
                    message: "Anda ditugaskan pada Batch Produksi {$lockedBatch->batch_no} (Target: " . number_format($lockedBatch->target_qty) . " pcs, Deadline: " . ($lockedBatch->planned_end_date ? $lockedBatch->planned_end_date->format('d/m/Y') : '-') . ").",
                    actionUrl: route('produksi.batches.show', $lockedBatch, false)
                );
            }

            return $lockedBatch;
        });
    }
}
