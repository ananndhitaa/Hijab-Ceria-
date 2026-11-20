<?php

namespace App\Services\Production;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Sales\Order;
use App\Models\System\Shipment;
use App\Models\System\User;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\OrderStatusService;
use DomainException;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public function __construct(
        private BatchService $batchService,
        private OrderStatusService $orderStatusService,
        private InvoiceService $invoiceService
    ) {}

    /**
     * Determine full shipment eligibility for a Sales Order.
     *
     * Invariants:
     * - Order must be in status PACKING.
     * - Every OrderItem must have confirmed GOOD quantity from QC >= qty_ordered.
     * - No unbatched or replacement quantity remaining (remaining_to_plan == 0).
     * - No batches still pending production/QC (pending_production_qty == 0).
     * - All existing non-cancelled batches must be SELESAI.
     * - Commercial requirement: Order must have non-cancelled Invoice and be fully settled (CONFIRMED payments >= total_amount).
     * - Order must not currently have another active Shipment (PENDING, PACKED, SHIPPED).
     *
     * @param Order $order
     * @return array{
     *     is_eligible: bool,
     *     status_is_packing: bool,
     *     has_batches: bool,
     *     all_batches_finished: bool,
     *     all_items_fulfilled: bool,
     *     is_payment_settled: bool,
     *     has_active_shipment: bool,
     *     reasons: array<string>
     * }
     */
    public function getEligibility(Order $order): array
    {
        $statusIsPacking = ($order->status === OrderStatus::PACKING);
        $reasons = [];

        if (! $statusIsPacking) {
            $reasons[] = "Status pesanan saat ini adalah {$order->status->label()} (harus PACKING).";
        }

        if (! $order->relationLoaded('items')) {
            $order->load(['items.productionBatches.productionResults.qcResult']);
        }

        $allItems = $order->items;
        $hasBatches = false;
        $allBatchesFinished = true;
        $allItemsFulfilled = true;

        if ($allItems->isEmpty()) {
            $reasons[] = 'Pesanan tidak memiliki item pesanan.';
            $allItemsFulfilled = false;
        }

        foreach ($allItems as $item) {
            $coverage = $this->batchService->calculateProductionCoverage($item);

            // Check if active batches exist
            $activeBatches = $item->productionBatches->where('status', '!=', BatchStatus::CANCELLED);
            if ($activeBatches->isNotEmpty()) {
                $hasBatches = true;
            }

            foreach ($activeBatches as $batch) {
                if ($batch->status !== BatchStatus::SELESAI) {
                    $allBatchesFinished = false;
                }
            }

            // Must satisfy full ordered qty with confirmed good pieces
            if (bccomp($coverage['confirmed_good_qty'], (string) $item->qty_ordered, 4) < 0) {
                $allItemsFulfilled = false;
                $diff = bcsub((string) $item->qty_ordered, $coverage['confirmed_good_qty'], 4);
                $diffFloat = (float) $diff;
                $reasons[] = "Varian '{$item->variant?->variant_name}' kekurangan {$diffFloat} pcs Good Qty.";
            }

            // Must have 0 remaining to plan and 0 pending
            if (bccomp($coverage['remaining_to_plan'], '0.0000', 4) > 0) {
                $allItemsFulfilled = false;
            }

            if (bccomp($coverage['pending_production_qty'], '0.0000', 4) > 0) {
                $allBatchesFinished = false;
            }
        }

        if (! $hasBatches) {
            $reasons[] = 'Belum ada batch produksi yang dibuat untuk pesanan ini.';
        } elseif (! $allBatchesFinished) {
            $reasons[] = 'Masih terdapat batch produksi yang belum selesai (SELESAI).';
        }

        // Commercial payment settlement check
        $isPaymentSettled = $this->invoiceService->isOrderFullyPaid($order);
        if (! $isPaymentSettled) {
            $invoice = $order->relationLoaded('invoice') ? $order->invoice : $order->invoice()->first();
            if (! $invoice) {
                $reasons[] = 'Pesanan belum dapat dikirim karena invoice belum diterbitkan.';
            } else {
                $reasons[] = 'Pesanan belum dapat dikirim karena pembayaran belum lunas.';
            }
        }

        // Check active shipments
        $hasActiveShipment = Shipment::where('order_id', $order->id)
            ->whereIn('status', [
                ShipmentStatus::PENDING->value,
                ShipmentStatus::PACKED->value,
                ShipmentStatus::SHIPPED->value,
            ])
            ->exists();

        if ($hasActiveShipment) {
            $reasons[] = 'Pesanan sudah memiliki pengiriman aktif yang sedang berjalan.';
        }

        $isEligible = $statusIsPacking
            && $hasBatches
            && $allBatchesFinished
            && $allItemsFulfilled
            && $isPaymentSettled
            && ! $hasActiveShipment;

        return [
            'is_eligible'          => $isEligible,
            'status_is_packing'    => $statusIsPacking,
            'has_batches'          => $hasBatches,
            'all_batches_finished' => $allBatchesFinished,
            'all_items_fulfilled'  => $allItemsFulfilled,
            'is_payment_settled'   => $isPaymentSettled,
            'has_active_shipment'  => $hasActiveShipment,
            'reasons'              => $reasons,
        ];
    }

    /**
     * Check whether an Order has completed physical production and packing prerequisites.
     *
     * @param Order $order
     * @return bool
     */
    public function isProductionFulfilled(Order $order): bool
    {
        $eligibility = $this->getEligibility($order);

        return $eligibility['status_is_packing']
            && $eligibility['has_batches']
            && $eligibility['all_batches_finished']
            && $eligibility['all_items_fulfilled']
            && ! $eligibility['has_active_shipment'];
    }

    /**
     * Check if a Sales Order is eligible to prepare a new Shipment.
     *
     * @param Order $order
     * @return bool
     */
    public function canPrepareShipment(Order $order): bool
    {
        return $this->getEligibility($order)['is_eligible'];
    }

    /**
     * Check whether an order's physical production and packing are completed.
     *
     * Invariants:
     * - If order is already in DIKIRIM or SELESAI, packing was completed previously.
     * - If order is in PACKING, all active batches must be SELESAI and all items fulfilled.
     * - Returns false if order is in earlier stages (KOMUNIKASI, DEAL, BAHAN, PRINTING, PRODUKSI, QC).
     *
     * @param Order $order
     * @return bool
     */
    public function isPackingCompleted(Order $order): bool
    {
        if (in_array($order->status, [OrderStatus::DIKIRIM, OrderStatus::SELESAI], true)) {
            return true;
        }

        if ($order->status !== OrderStatus::PACKING) {
            return false;
        }

        $eligibility = $this->getEligibility($order);

        return $eligibility['has_batches']
            && $eligibility['all_batches_finished']
            && $eligibility['all_items_fulfilled'];
    }

    /**
     * Determine the derived fulfillment display state for presentation consistency.
     *
     * @param Order $order
     * @return string One of: 'PACKING_IN_PROGRESS', 'WAITING_PAYMENT', 'READY_TO_SHIP', 'SHIPPED', 'COMPLETED', or order status value
     */
    public function getFulfillmentDisplayState(Order $order): string
    {
        if ($order->status === OrderStatus::SELESAI) {
            return 'COMPLETED';
        }

        if ($order->status === OrderStatus::DIKIRIM) {
            return 'SHIPPED';
        }

        if ($order->status === OrderStatus::PACKING) {
            if (! $this->isPackingCompleted($order)) {
                return 'PACKING_IN_PROGRESS';
            }

            if (! $this->invoiceService->isOrderFullyPaid($order)) {
                return 'WAITING_PAYMENT';
            }

            return 'READY_TO_SHIP';
        }

        return $order->status->value;
    }

    /**
     * Get a comprehensive presentation payload for UI consistency across Admin, Kanban, Portal, and Tracking.
     *
     * @param Order $order
     * @param bool $isCustomerPortal
     * @return array{
     *     state: string,
     *     is_packing_completed: bool,
     *     is_fully_paid: bool,
     *     operational_label: string,
     *     badge_class: string,
     *     customer_headline: string,
     *     customer_message: ?string,
     *     timeline_packing_completed: bool,
     *     timeline_dikirim_active: bool,
     *     timeline_dikirim_completed: bool,
     *     timeline_order_completed: bool
     * }
     */
    public function getFulfillmentPresentation(Order $order, bool $isCustomerPortal = false): array
    {
        $state = $this->getFulfillmentDisplayState($order);
        $isPackingCompleted = $this->isPackingCompleted($order);
        $isFullyPaid = $this->invoiceService->isOrderFullyPaid($order);

        return match ($state) {
            'COMPLETED' => [
                'state'                      => 'COMPLETED',
                'is_packing_completed'       => true,
                'is_fully_paid'              => true,
                'operational_label'          => 'Selesai',
                'badge_class'                => 'badge-success',
                'customer_headline'          => 'Pesanan Selesai',
                'customer_message'           => 'Pesanan telah selesai dan diterima oleh customer.',
                'timeline_packing_completed' => true,
                'timeline_dikirim_active'    => false,
                'timeline_dikirim_completed' => true,
                'timeline_order_completed'   => true,
            ],
            'SHIPPED' => [
                'state'                      => 'SHIPPED',
                'is_packing_completed'       => true,
                'is_fully_paid'              => true,
                'operational_label'          => 'Dikirim',
                'badge_class'                => 'badge-pink',
                'customer_headline'          => 'Pesanan Dikirim',
                'customer_message'           => 'Pesanan sedang dalam proses pengiriman oleh kurir ke alamat tujuan.',
                'timeline_packing_completed' => true,
                'timeline_dikirim_active'    => true,
                'timeline_dikirim_completed' => false,
                'timeline_order_completed'   => false,
            ],
            'READY_TO_SHIP' => [
                'state'                      => 'READY_TO_SHIP',
                'is_packing_completed'       => true,
                'is_fully_paid'              => true,
                'operational_label'          => 'Siap Dikirim',
                'badge_class'                => 'badge-success',
                'customer_headline'          => 'Siap Dikirim',
                'customer_message'           => 'Pesanan telah selesai dikemas dan siap dikirim.',
                'timeline_packing_completed' => true,
                'timeline_dikirim_active'    => false,
                'timeline_dikirim_completed' => false,
                'timeline_order_completed'   => false,
            ],
            'WAITING_PAYMENT' => [
                'state'                      => 'WAITING_PAYMENT',
                'is_packing_completed'       => true,
                'is_fully_paid'              => false,
                'operational_label'          => 'Menunggu Pelunasan',
                'badge_class'                => 'badge-warning',
                'customer_headline'          => 'Pengemasan Selesai',
                'customer_message'           => $isCustomerPortal
                    ? 'Pesanan selesai dikemas dan menunggu penyelesaian pembayaran.'
                    : 'Pesanan telah selesai dikemas.',
                'timeline_packing_completed' => true,
                'timeline_dikirim_active'    => false,
                'timeline_dikirim_completed' => false,
                'timeline_order_completed'   => false,
            ],
            'PACKING_IN_PROGRESS' => [
                'state'                      => 'PACKING_IN_PROGRESS',
                'is_packing_completed'       => false,
                'is_fully_paid'              => $isFullyPaid,
                'operational_label'          => 'Sedang Dikemas',
                'badge_class'                => 'badge-primary',
                'customer_headline'          => 'Sedang dalam tahapan ini',
                'customer_message'           => 'Pesanan sedang dalam proses pengemasan akhir.',
                'timeline_packing_completed' => false,
                'timeline_dikirim_active'    => false,
                'timeline_dikirim_completed' => false,
                'timeline_order_completed'   => false,
            ],
            default => [
                'state'                      => $state,
                'is_packing_completed'       => false,
                'is_fully_paid'              => $isFullyPaid,
                'operational_label'          => $order->status->label(),
                'badge_class'                => $order->status->badgeClass(),
                'customer_headline'          => $order->status->customerLabel(),
                'customer_message'           => null,
                'timeline_packing_completed' => false,
                'timeline_dikirim_active'    => false,
                'timeline_dikirim_completed' => false,
                'timeline_order_completed'   => false,
            ],
        };
    }

    /**
     * Atomically prepare/create a Shipment for an eligible Order.
     * Concurrency protected via DB transaction and row-level locking.
     *
     * @param Order $order
     * @param array $data
     * @param User $user
     * @return Shipment
     * @throws DomainException
     */
    public function createShipment(Order $order, array $data, User $user): Shipment
    {
        return DB::transaction(function () use ($order, $data) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $eligibility = $this->getEligibility($lockedOrder);
            if (! $eligibility['is_eligible']) {
                $reasonText = implode(' ', $eligibility['reasons']);
                throw new DomainException("Pesanan tidak memenuhi syarat pengiriman: {$reasonText}");
            }

            // Snapshot destination address: use input address or fallback to Customer master address
            $recipientAddress = ! empty($data['recipient_address'])
                ? $data['recipient_address']
                : ($lockedOrder->customer?->address ?? null);

            $shippingCost = isset($data['shipping_cost']) && is_numeric($data['shipping_cost'])
                ? (float) $data['shipping_cost']
                : 0.00;

            if ($shippingCost < 0) {
                throw new DomainException('Ongkos kirim tidak boleh bernilai negatif.');
            }

            // Create Shipment record in PENDING status (Order remains PACKING)
            $shipment = Shipment::create([
                'order_id'          => $lockedOrder->id,
                'tracking_no'       => ! empty($data['tracking_no']) ? trim($data['tracking_no']) : null,
                'courier'           => ! empty($data['courier']) ? trim($data['courier']) : null,
                'courier_service'   => ! empty($data['courier_service']) ? trim($data['courier_service']) : null,
                'shipping_cost'     => $shippingCost,
                'recipient_address' => $recipientAddress,
                'status'            => ShipmentStatus::PENDING,
                'notes'             => ! empty($data['notes']) ? trim($data['notes']) : null,
            ]);

            return $shipment;
        });
    }

    /**
     * Transition a Shipment from PENDING/PACKED to SHIPPED.
     * Advances the associated Order from PACKING to DIKIRIM.
     * Concurrency protected and idempotent.
     *
     * @param Shipment $shipment
     * @param User $user
     * @param array $extraData
     * @return Shipment
     * @throws DomainException
     */
    public function markAsShipped(Shipment $shipment, User $user, array $extraData = []): Shipment
    {
        return DB::transaction(function () use ($shipment, $user, $extraData) {
            /** @var Shipment $lockedShipment */
            $lockedShipment = Shipment::where('id', $shipment->id)->lockForUpdate()->firstOrFail();

            if ($lockedShipment->status === ShipmentStatus::SHIPPED) {
                return $lockedShipment;
            }

            if ($lockedShipment->status === ShipmentStatus::DELIVERED) {
                throw new DomainException('Pengiriman yang sudah terkirim (DELIVERED) tidak dapat diubah kembali ke SHIPPED.');
            }

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedShipment->order_id)->lockForUpdate()->firstOrFail();

            // Re-verify that all items have required good fulfillment and payment is settled
            $eligibility = $this->getEligibility($lockedOrder);
            if (! $eligibility['all_items_fulfilled']) {
                throw new DomainException('Pesanan belum memenuhi kuantitas Good Qty yang dipesan untuk dapat dikirim.');
            }

            if (! $this->invoiceService->isOrderFullyPaid($lockedOrder)) {
                throw new DomainException('Pesanan belum dapat dikirim karena pembayaran belum lunas.');
            }

            // Update shipment fields if provided
            if (array_key_exists('tracking_no', $extraData)) {
                $lockedShipment->tracking_no = ! empty($extraData['tracking_no']) ? trim($extraData['tracking_no']) : null;
            }
            if (array_key_exists('courier', $extraData)) {
                $lockedShipment->courier = ! empty($extraData['courier']) ? trim($extraData['courier']) : null;
            }
            if (array_key_exists('courier_service', $extraData)) {
                $lockedShipment->courier_service = ! empty($extraData['courier_service']) ? trim($extraData['courier_service']) : null;
            }
            if (array_key_exists('recipient_address', $extraData) && ! empty($extraData['recipient_address'])) {
                $lockedShipment->recipient_address = trim($extraData['recipient_address']);
            }
            if (array_key_exists('notes', $extraData) && ! empty($extraData['notes'])) {
                $lockedShipment->notes = trim($extraData['notes']);
            }

            // Enforce required fields for SHIPPED
            if (empty($lockedShipment->tracking_no)) {
                throw new DomainException('Nomor resi wajib diisi sebelum pengiriman ditandai Dikirim.');
            }
            if (empty($lockedShipment->courier)) {
                throw new DomainException('Nama kurir / ekspedisi wajib diisi sebelum pengiriman ditandai Dikirim.');
            }
            if (empty($lockedShipment->recipient_address)) {
                throw new DomainException('Alamat tujuan pengiriman wajib diisi sebelum pengiriman ditandai Dikirim.');
            }

            $lockedShipment->status = ShipmentStatus::SHIPPED;
            $lockedShipment->shipped_at = now();
            $lockedShipment->save();

            // Advance Order: PACKING -> DIKIRIM via OrderStatusService
            $courierName = $lockedShipment->courier ?: 'Kurir';
            $resiText = $lockedShipment->tracking_no ? " (No. Resi: {$lockedShipment->tracking_no})" : '';
            $historyNote = "Pesanan dikirim via {$courierName}{$resiText}.";

            $this->orderStatusService->transitionToDikirimFromShipment($lockedOrder, $user, $historyNote);

            return $lockedShipment;
        });
    }

    /**
     * Transition a Shipment from SHIPPED to DELIVERED.
     * Advances the associated Order from DIKIRIM to SELESAI.
     * Concurrency protected and idempotent.
     *
     * @param Shipment $shipment
     * @param User $user
     * @param string|null $notes
     * @return Shipment
     * @throws DomainException
     */
    public function markAsDelivered(Shipment $shipment, User $user, ?string $notes = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $user, $notes) {
            /** @var Shipment $lockedShipment */
            $lockedShipment = Shipment::where('id', $shipment->id)->lockForUpdate()->firstOrFail();

            if ($lockedShipment->status === ShipmentStatus::DELIVERED) {
                // Self-healing check: if shipment is already DELIVERED but Order was still DIKIRIM
                /** @var Order $lockedOrder */
                $lockedOrder = Order::where('id', $lockedShipment->order_id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status === OrderStatus::DIKIRIM) {
                    $historyNote = $notes ?: 'Pesanan telah diterima oleh pelanggan (Pengiriman selesai).';
                    $this->orderStatusService->transitionToSelesaiFromShipment(
                        $lockedOrder,
                        $user,
                        $historyNote,
                        $lockedShipment->delivered_at ?? now()
                    );
                }
                return $lockedShipment;
            }

            if ($lockedShipment->status !== ShipmentStatus::SHIPPED) {
                throw new DomainException("Pengiriman harus berstatus DIKIRIM (SHIPPED) terlebih dahulu sebelum dapat ditandai Selesai / Terkirim (status saat ini: {$lockedShipment->status->label()}).");
            }

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedShipment->order_id)->lockForUpdate()->firstOrFail();

            $deliveredAt = now();
            $lockedShipment->status = ShipmentStatus::DELIVERED;
            $lockedShipment->delivered_at = $deliveredAt;
            if ($notes) {
                $lockedShipment->notes = trim($notes);
            }
            $lockedShipment->save();

            // Advance Order: DIKIRIM -> SELESAI via OrderStatusService
            $historyNote = $notes ?: 'Pesanan telah diterima oleh pelanggan (Pengiriman selesai).';
            $this->orderStatusService->transitionToSelesaiFromShipment($lockedOrder, $user, $historyNote, $deliveredAt);

            return $lockedShipment;
        });
    }
}
