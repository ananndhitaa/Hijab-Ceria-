<?php

namespace App\Services\Sales;

use App\Enums\OrderStatus;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\System\OrderStatusHistory;
use App\Models\System\User;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    private InvoiceService $invoiceService;

    public function __construct(?InvoiceService $invoiceService = null)
    {
        $this->invoiceService = $invoiceService ?? app(InvoiceService::class);
    }

    /**
     * Confirm a Sales Order from KOMUNIKASI to DEAL with concurrency protection.
     * Uses DB transaction and lockForUpdate on the order row.
     *
     * @param Order $order
     * @param User $user
     * @param string|null $note
     * @return Order
     * @throws DomainException
     */
    public function confirmDeal(Order $order, User $user, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $user, $note) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::KOMUNIKASI) {
                throw new DomainException('Hanya pesanan dengan status Komunikasi yang dapat dikonfirmasi Deal.');
            }

            $lockedOrder->status = OrderStatus::DEAL;
            $lockedOrder->save();

            OrderStatusHistory::create([
                'order_id'           => $lockedOrder->id,
                'status'             => OrderStatus::DEAL,
                'changed_by_user_id' => $user->id,
                'changed_at'         => now(),
                'notes'              => $note,
            ]);

            return $lockedOrder;
        });
    }

    /**
     * Transition an already-locked Order from DEAL to BAHAN upon batch creation.
     * Operates strictly inside the caller's outer transaction.
     * If the Order is already BAHAN, does NOT update status or create duplicate status history.
     *
     * @param Order $lockedOrder
     * @param User $user
     * @param ProductionBatch $batch
     * @return bool True if transitioned from DEAL, false if already BAHAN
     */
    public function transitionToBahanForBatch(Order $lockedOrder, User $user, ProductionBatch $batch): bool
    {
        if ($lockedOrder->status === OrderStatus::DEAL) {
            $lockedOrder->status = OrderStatus::BAHAN;
            $lockedOrder->save();

            OrderStatusHistory::create([
                'order_id'           => $lockedOrder->id,
                'status'             => OrderStatus::BAHAN,
                'changed_by_user_id' => $user->id,
                'changed_at'         => now(),
                'notes'              => "Batch produksi {$batch->batch_no} dibuat.",
            ]);

            return true;
        }

        return false;
    }

    /**
     * Check if an order is eligible for editing.
     * Order can be edited if status is KOMUNIKASI or DEAL, no Invoice exists, and no downstream ProductionBatches exist.
     *
     * @param Order $order
     * @return bool
     */
    public function canEdit(Order $order): bool
    {
        if (! in_array($order->status, [OrderStatus::KOMUNIKASI, OrderStatus::DEAL], true)) {
            return false;
        }

        if ($this->hasInvoice($order)) {
            return false;
        }

        if ($this->hasDownstreamBatches($order)) {
            return false;
        }

        return true;
    }

    /**
     * Check if an order already has an issued Invoice.
     *
     * @param Order $order
     * @return bool
     */
    public function hasInvoice(Order $order): bool
    {
        return $order->invoice()->exists();
    }

    /**
     * Check if an order can be transitioned to DEAL.
     *
     * @param Order $order
     * @return bool
     */
    public function canConfirmDeal(Order $order): bool
    {
        return $order->status === OrderStatus::KOMUNIKASI;
    }

    /**
     * Synchronize the operational Order status based on multi-batch progress.
     * Uses the lowest unfinished operational stage principle with decimal-safe unbatched remainder check.
     *
     * Operational Stage Mapping:
     * - Unbatched remainder > 0 -> BAHAN (rank 1)
     * - Batch BAHAN            -> BAHAN (rank 1)
     * - Batch PRINTING         -> PRINTING (rank 2)
     * - Batch PRODUKSI         -> PRODUKSI (rank 3)
     * - Batch QC               -> QC (rank 4)
     * - Batch PACKING          -> PACKING (rank 5)
     * - Batch SELESAI          -> PACKING (rank 5)
     *
     * Invariants:
     * - CANCELLED batches are excluded from active batch candidates.
     * - Never regresses KOMUNIKASI or DEAL.
     * - Never regresses or sets DIKIRIM or SELESAI (owned by Shipment workflow).
     * - Creates exactly one OrderStatusHistory entry only when status actually changes.
     *
     * @param Order $order
     * @param User|null $user
     * @param string|null $note
     * @return OrderStatus|null The new status if changed, null if unchanged or skipped
     */
    public function syncOperationalStatus(Order $order, ?User $user = null, ?string $note = null): ?OrderStatus
    {
        return DB::transaction(function () use ($order, $user, $note) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // Do not sync if order is in pre-production or post-production shipment/completion states
            if (in_array($lockedOrder->status, [
                OrderStatus::KOMUNIKASI,
                OrderStatus::DEAL,
                OrderStatus::DIKIRIM,
                OrderStatus::SELESAI,
            ], true)) {
                return null;
            }

            // Load all items with their batches under lock
            $items = $lockedOrder->items()->with(['productionBatches' => function ($q) {
                $q->with(['productionResults.qcResult'])->lockForUpdate();
            }])->get();

            $ranks = [
                OrderStatus::BAHAN->value    => 1,
                OrderStatus::PRINTING->value => 2,
                OrderStatus::PRODUKSI->value => 3,
                OrderStatus::QC->value       => 4,
                OrderStatus::PACKING->value  => 5,
            ];

            $candidateStatuses = [];
            $hasUnbatchedRemainder = false;

            foreach ($items as $item) {
                $orderedQty = (string) $item->qty_ordered;
                $confirmedGood = '0.0000';
                $pendingProduction = '0.0000';

                foreach ($item->productionBatches as $batch) {
                    if ($batch->status === \App\Enums\BatchStatus::CANCELLED) {
                        continue;
                    }

                    $batchGood = (string) $batch->total_good_qty;
                    if (bccomp($batchGood, '0.0000', 4) > 0 || (float) $batch->total_reject_qty > 0) {
                        $confirmedGood = bcadd($confirmedGood, $batchGood, 4);
                        if (in_array($batch->status, [\App\Enums\BatchStatus::BAHAN, \App\Enums\BatchStatus::PRINTING, \App\Enums\BatchStatus::PRODUKSI], true)) {
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

                    // Map batch status to Order candidate status
                    $candidateStatus = match ($batch->status) {
                        \App\Enums\BatchStatus::BAHAN    => OrderStatus::BAHAN,
                        \App\Enums\BatchStatus::PRINTING => OrderStatus::PRINTING,
                        \App\Enums\BatchStatus::PRODUKSI => OrderStatus::PRODUKSI,
                        \App\Enums\BatchStatus::QC       => OrderStatus::QC,
                        \App\Enums\BatchStatus::PACKING  => OrderStatus::PACKING,
                        \App\Enums\BatchStatus::SELESAI          => OrderStatus::PACKING,
                        \App\Enums\BatchStatus::SELESAI_PARSIAL  => OrderStatus::PACKING,
                        default                          => null,
                    };

                    if ($candidateStatus !== null) {
                        $candidateStatuses[] = $candidateStatus;
                    }
                }

                $productionCoverage = bcadd($confirmedGood, $pendingProduction, 4);
                $remainingToPlan = bcsub($orderedQty, $productionCoverage, 4);
                if (bccomp($remainingToPlan, '0.0000', 4) > 0) {
                    $hasUnbatchedRemainder = true;
                }
            }

            if ($hasUnbatchedRemainder) {
                $candidateStatuses[] = OrderStatus::BAHAN;
            }

            if (empty($candidateStatuses)) {
                $candidateStatuses[] = OrderStatus::BAHAN;
            }

            // Select the candidate with the lowest operational rank
            usort($candidateStatuses, function (OrderStatus $a, OrderStatus $b) use ($ranks) {
                return $ranks[$a->value] <=> $ranks[$b->value];
            });

            /** @var OrderStatus $targetStatus */
            $targetStatus = $candidateStatuses[0];

            if ($lockedOrder->status !== $targetStatus) {
                $userId = $user?->id
                    ?? auth()->id()
                    ?? User::where('role', \App\Enums\UserRole::ADMIN)->value('id')
                    ?? User::value('id');

                $historyNote = $note;
                if (! $historyNote && $lockedOrder->status === OrderStatus::PACKING && $targetStatus === OrderStatus::BAHAN && $hasUnbatchedRemainder) {
                    $historyNote = 'Hasil QC belum memenuhi qty pesanan; produksi tambahan diperlukan.';
                }

                $lockedOrder->status = $targetStatus;
                $lockedOrder->save();

                OrderStatusHistory::create([
                    'order_id'           => $lockedOrder->id,
                    'status'             => $targetStatus,
                    'changed_by_user_id' => $userId,
                    'changed_at'         => now(),
                    'notes'              => $historyNote ?? "Status pesanan disinkronkan ke {$targetStatus->label()} berdasarkan progres batch produksi.",
                ]);

                return $targetStatus;
            }

            return null;
        });
    }

    /**
     * Transition an Order from PACKING to DIKIRIM during Shipment dispatch.
     * Concurrency protected via DB transaction and row-level locking.
     * Idempotent: returns existing locked order if already DIKIRIM.
     *
     * @param Order $order
     * @param User $user
     * @param string|null $notes
     * @return Order
     * @throws DomainException
     */
    public function transitionToDikirimFromShipment(Order $order, User $user, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($order, $user, $notes) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === OrderStatus::DIKIRIM) {
                return $lockedOrder;
            }

            if ($lockedOrder->status !== OrderStatus::PACKING) {
                throw new DomainException("Pesanan hanya dapat dikirim saat berstatus PACKING (status saat ini: {$lockedOrder->status->label()}).");
            }

            if (! $this->invoiceService->isOrderFullyPaid($lockedOrder)) {
                throw new DomainException('Pesanan belum dapat dikirim karena pembayaran belum lunas.');
            }

            $lockedOrder->status = OrderStatus::DIKIRIM;
            $lockedOrder->save();

            OrderStatusHistory::create([
                'order_id'           => $lockedOrder->id,
                'status'             => OrderStatus::DIKIRIM,
                'changed_by_user_id' => $user->id,
                'changed_at'         => now(),
                'notes'              => $notes ?? 'Pesanan telah diserahkan ke jasa pengiriman (Dikirim).',
            ]);

            return $lockedOrder;
        });
    }

    /**
     * Transition an Order from DIKIRIM to SELESAI upon Shipment delivery.
     * Concurrency protected via DB transaction and row-level locking.
     * Idempotent: returns existing locked order if already SELESAI.
     *
     * @param Order $order
     * @param User $user
     * @param string|null $notes
     * @param CarbonInterface|null $changedAt
     * @return Order
     * @throws DomainException
     */
    public function transitionToSelesaiFromShipment(Order $order, User $user, ?string $notes = null, ?CarbonInterface $changedAt = null): Order
    {
        return DB::transaction(function () use ($order, $user, $notes, $changedAt) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === OrderStatus::SELESAI) {
                return $lockedOrder;
            }

            if ($lockedOrder->status !== OrderStatus::DIKIRIM) {
                throw new DomainException("Pesanan hanya dapat diselesaikan saat berstatus DIKIRIM (status saat ini: {$lockedOrder->status->label()}).");
            }

            $lockedOrder->status = OrderStatus::SELESAI;
            $lockedOrder->save();

            OrderStatusHistory::create([
                'order_id'           => $lockedOrder->id,
                'status'             => OrderStatus::SELESAI,
                'changed_by_user_id' => $user->id,
                'changed_at'         => $changedAt ?? now(),
                'notes'              => $notes ?? 'Pesanan telah diterima oleh pelanggan (Pengiriman selesai).',
            ]);

            return $lockedOrder;
        });
    }

    /**
     * Check if any order item has downstream production batch data.
     *
     * @param Order $order
     * @return bool
     */
    public function hasDownstreamBatches(Order $order): bool
    {
        return $order->items()->whereHas('productionBatches')->exists();
    }
}
