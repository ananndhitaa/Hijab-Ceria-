<?php

namespace App\Services\Inventory;

use App\Enums\MovementType;
use App\Enums\PurchaseStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\StockMovement;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseReceivingService
{
    /**
     * Perform full receiving of an ORDERED Purchase Order.
     *
     * @param Purchase $purchase
     * @param int|null $userId ID of the authenticated Gudang user
     * @return Purchase
     * @throws DomainException
     */
    public function receivePurchase(Purchase $purchase, ?int $userId = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $userId) {
            $isIsolation = config('nayara.tenant_isolation_enabled', false);
            $outletId = null;

            if ($isIsolation) {
                try {
                    $outletId = app(TenantContext::class)->currentOutletId();
                } catch (\Throwable $e) {
                    abort(403, 'Akses ditolak: Konteks tenant tidak valid.');
                }
            }

            // 1. Lock and re-query Purchase instance
            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($isIsolation) {
                if ($lockedPurchase->outlet_id === null || (int) $lockedPurchase->outlet_id !== (int) $outletId) {
                    abort(404, 'Purchase Order tidak ditemukan untuk tenant ini.');
                }

                if (! $lockedPurchase->hasConsistentTenantRelations($outletId)) {
                    abort(404, 'Purchase Order memiliki inkonsistensi relasi tenant.');
                }
            }

            if ($lockedPurchase->status !== PurchaseStatus::ORDERED) {
                throw new DomainException("Hanya Purchase Order berstatus ORDERED yang dapat diterima. Status saat ini: {$lockedPurchase->status->label()}.");
            }

            $lockedPurchase->load(['items', 'payments']);

            if (! $lockedPurchase->isFullyPaid()) {
                throw new DomainException('Barang belum dapat diterima karena pembayaran supplier belum lunas.');
            }

            $items = $lockedPurchase->items()->with('material')->get();

            if ($items->isEmpty()) {
                throw new DomainException('Purchase Order tidak memiliki item material untuk diterima.');
            }

            // 2. Validate all items have qty_received == 0 (Full receiving precondition)
            foreach ($items as $item) {
                if (bccomp((string) $item->qty_received, '0', 4) > 0) {
                    throw new DomainException('Purchase Order memiliki data penerimaan parsial yang tidak sesuai dengan aturan Full Receiving.');
                }
                if (bccomp((string) $item->qty, '0', 4) <= 0) {
                    throw new DomainException("Kuantitas pemesanan untuk material ID {$item->material_id} tidak valid.");
                }
                if (bccomp((string) $item->unit_price, '0', 2) <= 0) {
                    throw new DomainException("Harga beli untuk material ID {$item->material_id} tidak valid.");
                }
            }

            // 3. Lock affected MaterialStock rows in deterministic order (ascending material_id)
            $materialIds = $items->pluck('material_id')->unique()->sort()->values()->all();

            $materialStocks = MaterialStock::whereIn('material_id', $materialIds)
                ->orderBy('material_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('material_id');

            // 4. Pre-validate that every material has an existing MaterialStock row and valid integrity
            foreach ($items as $item) {
                $material = $item->material;
                $matName = $material?->material_name ?? "ID #{$item->material_id}";

                if ($isIsolation) {
                    if (! $material || $material->outlet_id === null || (int) $material->outlet_id !== (int) $outletId) {
                        abort(404, "Material \"{$matName}\" tidak valid untuk tenant ini.");
                    }
                }

                if (! $materialStocks->has($item->material_id)) {
                    throw new DomainException("Integritas data stok tidak valid: data stok untuk material \"{$matName}\" tidak ditemukan di sistem.");
                }

                $stock = $materialStocks->get($item->material_id);
                if (bccomp((string) $stock->qty_on_hand, '0', 4) < 0) {
                    throw new DomainException("Integritas data stok tidak valid: stok saat ini untuk material \"{$matName}\" bernilai negatif ({$stock->qty_on_hand}).");
                }
                if (bccomp((string) $stock->avg_unit_cost, '0', 4) < 0) {
                    throw new DomainException("Integritas data stok tidak valid: harga rata-rata untuk material \"{$matName}\" bernilai negatif ({$stock->avg_unit_cost}).");
                }
            }

            $receivingDate = now('Asia/Jakarta')->toDateString();

            // 5. Process each item: update stock, moving average, record StockMovement, and set qty_received
            foreach ($items as $item) {
                $stock = $materialStocks->get($item->material_id);

                $oldQty = (string) $stock->qty_on_hand;
                $oldAvg = (string) $stock->avg_unit_cost;
                $incomingQty = (string) $item->qty;
                $incomingPrice = (string) $item->unit_price;

                // Moving Average Calculation
                if (bccomp($oldQty, '0', 4) === 0) {
                    $newQty = bcadd('0', $incomingQty, 4);
                    $newAvg = bcadd('0', $incomingPrice, 4);
                } else {
                    $newQty = bcadd($oldQty, $incomingQty, 4);
                    // High-precision intermediate calculation (scale 8)
                    $oldTotalVal = bcmul($oldQty, $oldAvg, 8);
                    $incomingTotalVal = bcmul($incomingQty, $incomingPrice, 8);
                    $newTotalVal = bcadd($oldTotalVal, $incomingTotalVal, 8);
                    $newAvg = bcdiv($newTotalVal, $newQty, 4);
                }

                // Update MaterialStock
                $stock->update([
                    'qty_on_hand'   => $newQty,
                    'avg_unit_cost' => $newAvg,
                ]);

                // Calculate total cost snapshot (scale 2 for persistence)
                $totalCostSnapshot = bcmul($incomingQty, $incomingPrice, 2);

                // Create StockMovement IN
                StockMovement::create([
                    'material_id'         => $item->material_id,
                    'movement_type'       => MovementType::IN,
                    'qty'                 => $incomingQty,
                    'unit_cost_snapshot'  => bcadd('0', $incomingPrice, 4),
                    'total_cost_snapshot' => $totalCostSnapshot,
                    'qty_before'          => $oldQty,
                    'qty_after'           => $newQty,
                    'reference_type'      => 'purchase_items',
                    'reference_id'        => $item->id,
                    'movement_date'       => $receivingDate,
                    'notes'               => "Penerimaan barang dari PO {$lockedPurchase->purchase_no}",
                    'created_by'          => $userId,
                ]);

                // Update PurchaseItem qty_received to exact ordered qty
                $item->update([
                    'qty_received' => $incomingQty,
                ]);
            }

            // 6. Transition Purchase status to RECEIVED
            $lockedPurchase->update([
                'status' => PurchaseStatus::RECEIVED,
            ]);

            return $lockedPurchase->load(['supplier', 'items.material.unit']);
        });
    }
}
