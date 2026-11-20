<?php

namespace App\Services\Inventory;

use App\Enums\MovementType;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use DomainException;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    /**
     * Perform controlled stock opname / physical inventory adjustment for a material.
     *
     * @param Material $material
     * @param string|float|int $physicalQty
     * @param string $notes
     * @param int|null $userId ID of the authenticated Gudang user
     * @return StockMovement|null Returns StockMovement if adjusted, or null if no-op (physical_qty == qty_on_hand)
     * @throws DomainException
     */
    public function adjustStock(Material $material, string|float|int $physicalQty, string $notes, ?int $userId = null): ?StockMovement
    {
        // 1. Audit notes validation & trimming
        $trimmedNotes = trim((string) $notes);
        if ($trimmedNotes === '') {
            throw new DomainException('Catatan penyesuaian stok wajib diisi.');
        }
        if (mb_strlen($trimmedNotes) > 500) {
            throw new DomainException('Catatan penyesuaian stok maksimal 500 karakter.');
        }

        // 2. Exact decimal normalization and validation
        if (! is_numeric($physicalQty)) {
            throw new DomainException('Kuantitas fisik stok harus berupa angka yang valid.');
        }

        $normalizedPhysicalQty = number_format((float) $physicalQty, 4, '.', '');
        if (bccomp($normalizedPhysicalQty, '0', 4) < 0) {
            throw new DomainException('Kuantitas fisik stok tidak boleh bernilai negatif.');
        }

        return DB::transaction(function () use ($material, $normalizedPhysicalQty, $trimmedNotes, $userId) {
            $matName = $material->material_name ?? "ID #{$material->id}";

            // Re-query and lock Material row
            /** @var Material $lockedMaterial */
            $lockedMaterial = Material::query()
                ->whereKey($material->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (config('nayara.tenant_isolation_enabled', false)) {
                $user = auth()->user() ?? ($userId ? \App\Models\System\User::find($userId) : null);
                if ($user) {
                    $outletId = app(\App\Services\System\TenantContext::class)->outletIdFor($user);
                    if ($lockedMaterial->outlet_id === null || (int) $lockedMaterial->outlet_id !== (int) $outletId) {
                        abort(404, "Material \"{$matName}\" tidak ditemukan untuk outlet ini.");
                    }
                }
            }

            // 3. Deterministic MaterialStock query and row-level locking
            /** @var MaterialStock|null $stock */
            $stock = MaterialStock::query()
                ->where('material_id', $lockedMaterial->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw new DomainException("Integritas data stok tidak valid: data stok untuk material \"{$matName}\" tidak ditemukan di sistem.");
            }

            $currentOnHand = (string) $stock->qty_on_hand;
            $avgCost = (string) $stock->avg_unit_cost;

            // 4. Integrity checks: reject corrupt negative stock or negative average cost
            if (bccomp($currentOnHand, '0', 4) < 0) {
                throw new DomainException("Integritas data stok tidak valid: stok saat ini untuk material \"{$matName}\" bernilai negatif ({$currentOnHand}).");
            }
            if (bccomp($avgCost, '0', 4) < 0) {
                throw new DomainException("Integritas data stok tidak valid: harga rata-rata untuk material \"{$matName}\" bernilai negatif ({$avgCost}).");
            }

            // 5. No-change comparison (physical_qty == qty_on_hand)
            $diffCmp = bccomp($normalizedPhysicalQty, $currentOnHand, 4);
            if ($diffCmp === 0) {
                return null;
            }

            // 6. Calculate absolute positive difference
            if ($diffCmp > 0) {
                $diffQty = bcsub($normalizedPhysicalQty, $currentOnHand, 4);
            } else {
                $diffQty = bcsub($currentOnHand, $normalizedPhysicalQty, 4);
            }

            // 7. Calculate valuation snapshot (DECIMAL(18,2) for total_cost_snapshot)
            $rawTotalCost = bcmul($diffQty, $avgCost, 8);
            $totalCostSnapshot = number_format((float) $rawTotalCost, 2, '.', '');

            // 8. Update MaterialStock (qty_on_hand changes, avg_unit_cost remains strictly UNCHANGED)
            $stock->update([
                'qty_on_hand' => $normalizedPhysicalQty,
            ]);

            // 9. Record StockMovement ADJUSTMENT
            $adjustmentDate = now('Asia/Jakarta')->toDateString();

            return StockMovement::create([
                'material_id'         => $lockedMaterial->id,
                'movement_type'       => MovementType::ADJUSTMENT,
                'qty'                 => $diffQty,
                'unit_cost_snapshot'  => $avgCost,
                'total_cost_snapshot' => $totalCostSnapshot,
                'qty_before'          => $currentOnHand,
                'qty_after'           => $normalizedPhysicalQty,
                'reference_type'      => null,
                'reference_id'        => null,
                'movement_date'       => $adjustmentDate,
                'notes'               => $trimmedNotes,
                'created_by'          => $userId,
            ]);
        });
    }
}
