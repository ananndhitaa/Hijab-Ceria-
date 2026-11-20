<?php

namespace App\Services\Inventory;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use DomainException;
use Illuminate\Support\Facades\DB;

class MaterialAllocationService
{
    /**
     * Perform full physical material allocation from warehouse stock to a Production Batch.
     *
     * @param ProductionBatch $batch
     * @param int|null $userId ID of the authenticated Gudang user
     * @return ProductionBatch
     * @throws DomainException
     */
    public function allocateBatchMaterials(ProductionBatch $batch, ?int $userId = null): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $userId) {
            // 1. Lock and re-query ProductionBatch instance
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::query()
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== BatchStatus::BAHAN) {
                throw new DomainException("Alokasi bahan hanya dapat dilakukan pada batch berstatus BAHAN. Status saat ini: {$lockedBatch->status->label()}.");
            }

            if (config('nayara.tenant_isolation_enabled', false)) {
                $currentOutletId = app(\App\Services\System\TenantContext::class)->currentOutletId();

                if (! $lockedBatch->hasConsistentTenantRelations((int) $currentOutletId)) {
                    throw new DomainException('Integritas data tenant tidak valid: Batch memiliki relasi lintas-outlet atau material tidak sah.');
                }
            }

            // 2. Lock and load BatchMaterials
            $batchMaterials = BatchMaterial::where('batch_id', $lockedBatch->id)
                ->with('material')
                ->lockForUpdate()
                ->get();

            if ($batchMaterials->isEmpty()) {
                throw new DomainException('Batch produksi belum memiliki data perencanaan kebutuhan bahan (MRP snapshot).');
            }

            // 3. Validate allocation state matrix
            $totalItems = $batchMaterials->count();
            $fullyAllocatedCount = 0;
            $unallocatedCount = 0;

            foreach ($batchMaterials as $bm) {
                $reqQty = (string) $bm->required_qty;
                $allocQty = (string) $bm->allocated_qty;
                $usedQty = (string) $bm->used_qty;

                // required_qty integrity check
                if (bccomp($reqQty, '0', 4) <= 0) {
                    throw new DomainException("Integritas data tidak valid: kuantitas kebutuhan material ID {$bm->material_id} bernilai 0 atau negatif.");
                }

                // used_qty integrity check before allocation
                if (bccomp($usedQty, '0', 4) > 0) {
                    throw new DomainException("Integritas data tidak valid: ditemukan data pemakaian produksi (used_qty > 0) sebelum alokasi selesai.");
                }

                // Check allocation amount
                if (bccomp($allocQty, $reqQty, 4) === 0) {
                    $fullyAllocatedCount++;
                } elseif (bccomp($allocQty, '0', 4) === 0) {
                    $unallocatedCount++;
                } else {
                    // Partial or overflow allocation is invalid in Full Allocation model
                    throw new DomainException("Integritas data alokasi tidak valid: ditemukan alokasi parsial atau tidak konsisten pada material ID {$bm->material_id}.");
                }
            }

            // Idempotency: if already fully allocated
            if ($fullyAllocatedCount === $totalItems) {
                throw new DomainException('Bahan untuk batch ini sudah dialokasikan.');
            }

            // Mixed state (some items allocated, some unallocated) is invalid
            if ($unallocatedCount !== $totalItems) {
                throw new DomainException('Integritas data alokasi tidak valid: ditemukan kondisi alokasi campuran (sebagian sudah teralokasi, sebagian belum).');
            }

            // 4. Deterministic MaterialStock locking (sorted ascending by material_id)
            $materialIds = $batchMaterials->pluck('material_id')->unique()->sort()->values()->all();

            $materialStocks = MaterialStock::whereIn('material_id', $materialIds)
                ->orderBy('material_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('material_id');

            // 5. Pre-validate that every material has existing stock row, valid non-negative values, and sufficient stock
            foreach ($batchMaterials as $bm) {
                $material = $bm->material;
                $matName = $material?->material_name ?? "ID #{$bm->material_id}";

                if (! $materialStocks->has($bm->material_id)) {
                    throw new DomainException("Integritas data stok tidak valid: data stok untuk material \"{$matName}\" tidak ditemukan di sistem.");
                }

                $stock = $materialStocks->get($bm->material_id);
                $currentOnHand = (string) $stock->qty_on_hand;
                $avgCost = (string) $stock->avg_unit_cost;

                if (bccomp($currentOnHand, '0', 4) < 0) {
                    throw new DomainException("Integritas data stok tidak valid: stok saat ini untuk material \"{$matName}\" bernilai negatif ({$currentOnHand}).");
                }
                if (bccomp($avgCost, '0', 4) < 0) {
                    throw new DomainException("Integritas data stok tidak valid: harga rata-rata untuk material \"{$matName}\" bernilai negatif ({$avgCost}).");
                }

                // Check stock sufficiency
                if (bccomp($currentOnHand, (string) $bm->required_qty, 4) < 0) {
                    throw new DomainException('Stok belum mencukupi untuk mengalokasikan seluruh kebutuhan bahan batch.');
                }
            }

            $allocationDate = now('Asia/Jakarta')->toDateString();

            // 6. Mutate: Decrement stock, record StockMovement OUT, and set allocated_qty
            foreach ($batchMaterials as $bm) {
                $stock = $materialStocks->get($bm->material_id);

                $qtyBefore = (string) $stock->qty_on_hand;
                $reqQty = (string) $bm->required_qty;
                $newQty = bcsub($qtyBefore, $reqQty, 4);
                $avgCost = (string) $stock->avg_unit_cost;

                // High-precision cost calculation for movement snapshot (scale 2 for persistence)
                $rawTotalCost = bcmul($reqQty, $avgCost, 8);
                $totalCostSnapshot = number_format((float) $rawTotalCost, 2, '.', '');

                // Update MaterialStock (qty_on_hand decreases, avg_unit_cost UNCHANGED)
                $stock->update([
                    'qty_on_hand' => $newQty,
                ]);

                // Create StockMovement OUT
                StockMovement::create([
                    'material_id'         => $bm->material_id,
                    'movement_type'       => MovementType::OUT,
                    'qty'                 => $reqQty,
                    'unit_cost_snapshot'  => $avgCost,
                    'total_cost_snapshot' => $totalCostSnapshot,
                    'qty_before'          => $qtyBefore,
                    'qty_after'           => $newQty,
                    'reference_type'      => 'batch_materials',
                    'reference_id'        => $bm->id,
                    'movement_date'       => $allocationDate,
                    'notes'               => "Pengeluaran bahan untuk Batch {$lockedBatch->batch_no}",
                    'created_by'          => $userId,
                ]);

                // Update BatchMaterial: allocated_qty = required_qty, used_qty remains unchanged (0.0000)
                $bm->update([
                    'allocated_qty' => $reqQty,
                ]);
            }

            // Batch status remains BAHAN
            return $lockedBatch->load(['orderItem.variant.product', 'batchMaterials.material.unit']);
        });
    }

    /**
     * Perform additional physical material allocation from warehouse stock to a Production Batch.
     *
     * @param ProductionBatch $batch
     * @param int $batchMaterialId
     * @param string|float|int $additionalQty
     * @param int|null $userId ID of the authenticated user
     * @return BatchMaterial
     * @throws DomainException
     */
    public function allocateAdditionalMaterial(
        ProductionBatch $batch,
        int $batchMaterialId,
        string|float|int $additionalQty,
        ?int $userId = null
    ): BatchMaterial {
        return DB::transaction(function () use ($batch, $batchMaterialId, $additionalQty, $userId) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::query()
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBatch->status !== BatchStatus::PRODUKSI) {
                throw new DomainException("Tambahan bahan hanya dapat dialokasikan saat batch berstatus PRODUKSI. Status saat ini: {$lockedBatch->status->label()}.");
            }

            /** @var BatchMaterial|null $lockedBm */
            $lockedBm = BatchMaterial::where('batch_id', $lockedBatch->id)
                ->where('id', $batchMaterialId)
                ->with('material.unit')
                ->lockForUpdate()
                ->first();

            if (! $lockedBm) {
                throw new DomainException('Bahan yang diminta tidak ditemukan atau bukan milik batch ini.');
            }

            if (config('nayara.tenant_isolation_enabled', false)) {
                $currentOutletId = app(\App\Services\System\TenantContext::class)->currentOutletId();

                if (! $lockedBatch->hasConsistentTenantRelations((int) $currentOutletId)) {
                    throw new DomainException('Integritas data tenant tidak valid: Batch memiliki relasi lintas-outlet atau material tidak sah.');
                }
                if (! $lockedBm->material || $lockedBm->material->outlet_id === null || (int) $lockedBm->material->outlet_id !== (int) $currentOutletId) {
                    throw new DomainException('Integritas data tenant tidak valid: Material yang diminta bukan milik outlet aktif.');
                }
            }

            $additionalQtyStr = (string) $additionalQty;
            if (! is_numeric($additionalQtyStr) || bccomp($additionalQtyStr, '0.0000', 4) <= 0) {
                throw new DomainException('Jumlah tambahan bahan harus berupa angka positif lebih besar dari 0.');
            }

            /** @var MaterialStock $stock */
            $stock = MaterialStock::where('material_id', $lockedBm->material_id)
                ->lockForUpdate()
                ->first();

            $matName = $lockedBm->material?->material_name ?? "ID #{$lockedBm->material_id}";
            $unitSymbol = $lockedBm->material?->unit?->symbol ?? 'unit';

            if (! $stock) {
                throw new DomainException("Integritas data stok tidak valid: data stok untuk material \"{$matName}\" tidak ditemukan di sistem.");
            }

            $currentOnHand = (string) $stock->qty_on_hand;
            $avgCost = (string) $stock->avg_unit_cost;

            if (bccomp($currentOnHand, '0', 4) < 0) {
                throw new DomainException("Integritas data stok tidak valid: stok saat ini untuk material \"{$matName}\" bernilai negatif ({$currentOnHand}).");
            }

            // Check stock sufficiency
            if (bccomp($currentOnHand, $additionalQtyStr, 4) < 0) {
                $shortage = bcsub($additionalQtyStr, $currentOnHand, 4);
                $shortageFormatted = rtrim(rtrim(number_format((float) $shortage, 4), '0'), '.');
                throw new DomainException("Stok Gudang tidak mencukupi. Kekurangan {$shortageFormatted} {$unitSymbol}.");
            }

            $newQty = bcsub($currentOnHand, $additionalQtyStr, 4);
            $rawTotalCost = bcmul($additionalQtyStr, $avgCost, 8);
            $totalCostSnapshot = number_format((float) $rawTotalCost, 2, '.', '');

            // Decrement MaterialStock (qty_on_hand decreases, avg_unit_cost UNCHANGED)
            $stock->update([
                'qty_on_hand' => $newQty,
            ]);

            $allocationDate = now('Asia/Jakarta')->toDateString();

            // Create StockMovement OUT
            StockMovement::create([
                'material_id'         => $lockedBm->material_id,
                'movement_type'       => MovementType::OUT,
                'qty'                 => $additionalQtyStr,
                'unit_cost_snapshot'  => $avgCost,
                'total_cost_snapshot' => $totalCostSnapshot,
                'qty_before'          => $currentOnHand,
                'qty_after'           => $newQty,
                'reference_type'      => 'production_batches',
                'reference_id'        => $lockedBatch->id,
                'movement_date'       => $allocationDate,
                'notes'               => "Tambahan alokasi bahan {$matName} untuk Batch {$lockedBatch->batch_no}",
                'created_by'          => $userId,
            ]);

            // Update BatchMaterial: increment allocated_qty
            $newAllocated = bcadd((string) $lockedBm->allocated_qty, $additionalQtyStr, 4);
            $lockedBm->update([
                'allocated_qty' => $newAllocated,
            ]);

            return $lockedBm->fresh(['material.unit', 'batch']);
        });
    }
}
