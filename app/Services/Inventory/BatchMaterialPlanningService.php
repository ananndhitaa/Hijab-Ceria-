<?php

namespace App\Services\Inventory;

use App\Enums\BatchStatus;
use App\Models\Master\BomItem;
use App\Models\Master\Material;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use DomainException;
use Illuminate\Support\Facades\DB;

class BatchMaterialPlanningService
{
    /**
     * Validate Product Variant BOM master recipe before planning or batch creation.
     * Throws DomainException on missing BOM, inactive material, or invalid quantity.
     *
     * @param \App\Models\Master\ProductVariant $variant
     * @return \Illuminate\Database\Eloquent\Collection<\App\Models\Master\BomItem>
     * @throws DomainException
     */
    public function validateBom(\App\Models\Master\ProductVariant $variant): \Illuminate\Database\Eloquent\Collection
    {
        $bomItems = BomItem::where('variant_id', $variant->id)
            ->with(['material.unit', 'material.stock'])
            ->get();

        if ($bomItems->isEmpty()) {
            throw new DomainException('BOM varian belum tersedia. Lengkapi BOM pada Master Produk sebelum membuat Batch Produksi.');
        }

        foreach ($bomItems as $bomItem) {
            if (! $bomItem->material) {
                throw new DomainException('BOM merujuk pada material yang tidak ditemukan di sistem.');
            }

            if (! $bomItem->material->active) {
                throw new DomainException("BOM menggunakan material nonaktif ({$bomItem->material->material_name}). Perbarui BOM terlebih dahulu.");
            }

            if (bccomp((string) $bomItem->qty_per_unit, '0.0000', 4) <= 0) {
                throw new DomainException("Kuantitas BOM untuk material {$bomItem->material->material_name} tidak valid (harus > 0).");
            }

            if (config('nayara.tenant_isolation_enabled', false)) {
                $tenantOutletId = null;
                try {
                    $tenantOutletId = app(\App\Services\System\TenantContext::class)->currentOutletId();
                } catch (\Throwable) {
                    $tenantOutletId = null;
                }

                $targetOutletId = $tenantOutletId ?? $variant->outlet_id;
                if ($targetOutletId !== null) {
                    if ($bomItem->material->outlet_id !== null && (int) $bomItem->material->outlet_id !== (int) $targetOutletId) {
                        throw new DomainException("BOM menggunakan material dari outlet yang berbeda ({$bomItem->material->material_name}).");
                    }
                }
            }
        }

        return $bomItems;
    }

    /**
     * Generate the immutable BOM material requirement snapshot for a ProductionBatch.
     * Operates within a DB transaction with row-locking to ensure idempotency.
     *
     * @param ProductionBatch $batch
     * @param \Illuminate\Database\Eloquent\Collection|null $validatedBomItems
     * @return array{batch: ProductionBatch, created: bool, count: int, message: string}
     * @throws DomainException
     */
    public function generateMaterialPlanning(ProductionBatch $batch, ?\Illuminate\Database\Eloquent\Collection $validatedBomItems = null): array
    {
        return DB::transaction(function () use ($batch, $validatedBomItems) {
            /** @var ProductionBatch $lockedBatch */
            $lockedBatch = ProductionBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Validate Batch Status: Must be in planning state (BAHAN)
            if ($lockedBatch->status !== BatchStatus::BAHAN) {
                throw new DomainException("Kebutuhan bahan hanya dapat dihitung untuk batch dengan status BAHAN (status saat ini: {$lockedBatch->status->label()}).");
            }

            // 2. Check Idempotency: If snapshot already exists, do not duplicate
            $existingCount = BatchMaterial::where('batch_id', $lockedBatch->id)->count();
            if ($existingCount > 0) {
                return [
                    'batch'   => $lockedBatch,
                    'created' => false,
                    'count'   => $existingCount,
                    'message' => 'Kebutuhan bahan untuk batch ini sudah dihitung.',
                ];
            }

            // 3. Resolve and validate BOM items if not already provided
            $bomItems = $validatedBomItems;
            if ($bomItems === null) {
                $orderItem = $lockedBatch->orderItem;
                if (! $orderItem || ! $orderItem->variant) {
                    throw new DomainException('Varian produk untuk batch ini tidak ditemukan.');
                }

                $bomItems = $this->validateBom($orderItem->variant);
            }

            // 4. Generate snapshot records for every validated BOM item
            $targetQty = (string) $lockedBatch->target_qty;
            $createdCount = 0;

            foreach ($bomItems as $bomItem) {
                $material = $bomItem->material;
                $bomQty = (string) $bomItem->qty_per_unit;

                // required_qty = bom_qty_per_unit * batch.target_qty (DECIMAL 18,4)
                $requiredQty = bcmul($bomQty, $targetQty, 4);

                // Live stock at the moment of calculation
                $currentStock = (string) ($material->stock?->qty_on_hand ?? '0.0000');

                // shortage_qty = MAX(required_qty - stock_at_calc, 0)
                $shortageQty = bccomp($requiredQty, $currentStock, 4) > 0
                    ? bcsub($requiredQty, $currentStock, 4)
                    : '0.0000';

                BatchMaterial::create([
                    'batch_id'      => $lockedBatch->id,
                    'material_id'   => $material->id,
                    'required_qty'  => $requiredQty,
                    'stock_at_calc' => $currentStock,
                    'shortage_qty'  => $shortageQty,
                    'allocated_qty' => '0.0000',
                    'used_qty'      => '0.0000',
                    'calculated_at' => now(),
                ]);

                $createdCount++;
            }

            return [
                'batch'   => $lockedBatch,
                'created' => true,
                'count'   => $createdCount,
                'message' => "Kebutuhan bahan untuk batch {$lockedBatch->batch_no} berhasil dihitung ({$createdCount} material).",
            ];
        });
    }

    /**
     * Calculate MRP view data combining immutable snapshot requirements (or live BOM preview)
     * with canonical current stock on hand.
     *
     * @param ProductionBatch $batch
     * @return array{
     *     has_snapshot: bool,
     *     items: array<int, array{
     *         material_id: int,
     *         material_name: string,
     *         unit_name: string,
     *         symbol: string,
     *         bom_qty_per_unit: string,
     *         formatted_bom_qty: string,
     *         required_qty: string,
     *         formatted_required_qty: string,
     *         current_stock: string,
     *         formatted_current_stock: string,
     *         shortage_qty: string,
     *         formatted_shortage_qty: string,
     *         is_shortage: bool,
     *         status_label: string,
     *         status_badge: string
     *     }>,
     *     total_materials: int,
     *     sufficient_materials: int,
     *     shortage_materials: int,
     *     has_shortage: bool
     * }
     */
    public function getMaterialRequirementsData(ProductionBatch $batch): array
    {
        $batchMaterials = BatchMaterial::where('batch_id', $batch->id)
            ->with(['material.unit', 'material.stock'])
            ->get();

        $hasSnapshot = $batchMaterials->isNotEmpty();
        $items = [];
        $sufficientCount = 0;
        $shortageCount = 0;
        $allocatedCount = 0;
        $canAllocateAll = true;

        $targetQty = (string) $batch->target_qty;

        if ($hasSnapshot) {
            // Use frozen transaction snapshot for required quantities
            $variant = $batch->orderItem?->variant;
            $bomMap = [];
            if ($variant) {
                $bomMap = BomItem::where('variant_id', $variant->id)->pluck('qty_per_unit', 'material_id')->toArray();
            }

            foreach ($batchMaterials as $bm) {
                $material = $bm->material;
                $unit = $material?->unit;

                $requiredQty = (string) $bm->required_qty;
                $allocatedQty = (string) ($bm->allocated_qty ?? '0.0000');
                $currentStock = (string) ($material?->stock?->qty_on_hand ?? '0.0000');
                $historicalShortage = (string) ($bm->shortage_qty ?? '0.0000');

                // Determine whether this item is physically allocated
                $isAllocated = bccomp($allocatedQty, '0', 4) > 0 && bccomp($allocatedQty, $requiredQty, 4) >= 0;
                if ($isAllocated) {
                    $allocatedCount++;
                }

                // Live warehouse shortage vs current physical stock
                $liveShortageQty = bccomp($requiredQty, $currentStock, 4) > 0
                    ? bcsub($requiredQty, $currentStock, 4)
                    : '0.0000';

                // Effective shortage for this Batch: 0 if already allocated
                $effectiveShortageQty = $isAllocated ? '0.0000' : $liveShortageQty;

                // Check if current warehouse stock can fulfill allocation
                if (bccomp($currentStock, $requiredQty, 4) < 0) {
                    $canAllocateAll = false;
                }

                $isShortage = bccomp($effectiveShortageQty, '0.0000', 4) > 0;
                if ($isShortage) {
                    $shortageCount++;
                } else {
                    $sufficientCount++;
                }

                $bomQty = isset($bomMap[$bm->material_id])
                    ? (string) $bomMap[$bm->material_id]
                    : (bccomp($targetQty, '0', 4) > 0 ? bcdiv($requiredQty, $targetQty, 4) : '0.0000');

                $stockAtCalc = (string) ($bm->stock_at_calc ?? '0.0000');

                $statusLabel = $isAllocated ? 'TERALOKASI' : ($isShortage ? 'KURANG' : 'SIAP');
                $statusBadge = $isAllocated ? 'badge-success' : ($isShortage ? 'badge-warning' : 'bg-[#F7E6EE] text-[#8E1B5C] border border-[#EBDDE4]');

                $items[] = [
                    'material_id'                      => (int) $bm->material_id,
                    'material_name'                    => $material?->material_name ?? "Material #{$bm->material_id}",
                    'unit_name'                        => $unit?->unit_name ?? '',
                    'symbol'                           => $unit?->symbol ?? '',
                    'bom_qty_per_unit'                 => $bomQty,
                    'formatted_bom_qty'                => $this->formatQuantity($bomQty),
                    'required_qty'                     => $requiredQty,
                    'formatted_required_qty'           => $this->formatQuantity($requiredQty),
                    'allocated_qty'                    => $allocatedQty,
                    'formatted_allocated_qty'          => $this->formatQuantity($allocatedQty),
                    'current_stock'                    => $currentStock,
                    'formatted_current_stock'          => $this->formatQuantity($currentStock),
                    'stock_at_calc'                    => $stockAtCalc,
                    'formatted_stock_at_calc'          => $this->formatQuantity($stockAtCalc),
                    'historical_shortage_qty'          => $historicalShortage,
                    'formatted_historical_shortage_qty'=> $this->formatQuantity($historicalShortage),
                    'live_shortage_qty'                => $liveShortageQty,
                    'formatted_live_shortage_qty'      => $this->formatQuantity($liveShortageQty),
                    'effective_shortage_qty'           => $effectiveShortageQty,
                    'formatted_effective_shortage_qty' => $this->formatQuantity($effectiveShortageQty),
                    'shortage_qty'                     => $effectiveShortageQty,
                    'formatted_shortage_qty'           => $this->formatQuantity($effectiveShortageQty),
                    'used_qty'                         => (string) ($bm->used_qty ?? '0.0000'),
                    'formatted_used_qty'               => $this->formatQuantity((string) ($bm->used_qty ?? '0.0000')),
                    'is_allocated'                     => $isAllocated,
                    'is_shortage'                      => $isShortage,
                    'status_label'                     => $statusLabel,
                    'status_badge'                     => $statusBadge,
                ];
            }
        } else {
            // Preview mode: Calculate from master BOM before snapshot generation
            $variant = $batch->orderItem?->variant;
            $bomItems = $variant
                ? BomItem::where('variant_id', $variant->id)->with(['material.unit', 'material.stock'])->get()
                : collect();

            foreach ($bomItems as $bomItem) {
                $material = $bomItem->material;
                $unit = $material?->unit;
                $bomQty = (string) $bomItem->qty_per_unit;

                $requiredQty = bcmul($bomQty, $targetQty, 4);
                $currentStock = (string) ($material?->stock?->qty_on_hand ?? '0.0000');

                $shortageQty = bccomp($requiredQty, $currentStock, 4) > 0
                    ? bcsub($requiredQty, $currentStock, 4)
                    : '0.0000';

                if (bccomp($currentStock, $requiredQty, 4) < 0) {
                    $canAllocateAll = false;
                }

                $isShortage = bccomp($shortageQty, '0.0000', 4) > 0;
                if ($isShortage) {
                    $shortageCount++;
                } else {
                    $sufficientCount++;
                }

                $items[] = [
                    'material_id'                      => (int) $bomItem->material_id,
                    'material_name'                    => $material?->material_name ?? "Material #{$bomItem->material_id}",
                    'unit_name'                        => $unit?->unit_name ?? '',
                    'symbol'                           => $unit?->symbol ?? '',
                    'bom_qty_per_unit'                 => $bomQty,
                    'formatted_bom_qty'                => $this->formatQuantity($bomQty),
                    'required_qty'                     => $requiredQty,
                    'formatted_required_qty'           => $this->formatQuantity($requiredQty),
                    'allocated_qty'                    => '0.0000',
                    'formatted_allocated_qty'          => '0',
                    'current_stock'                    => $currentStock,
                    'formatted_current_stock'          => $this->formatQuantity($currentStock),
                    'stock_at_calc'                    => $currentStock,
                    'formatted_stock_at_calc'          => $this->formatQuantity($currentStock),
                    'historical_shortage_qty'          => $shortageQty,
                    'formatted_historical_shortage_qty'=> $this->formatQuantity($shortageQty),
                    'live_shortage_qty'                => $shortageQty,
                    'formatted_live_shortage_qty'      => $this->formatQuantity($shortageQty),
                    'effective_shortage_qty'           => $shortageQty,
                    'formatted_effective_shortage_qty' => $this->formatQuantity($shortageQty),
                    'shortage_qty'                     => $shortageQty,
                    'formatted_shortage_qty'           => $this->formatQuantity($shortageQty),
                    'is_allocated'                     => false,
                    'is_shortage'                      => $isShortage,
                    'status_label'                     => $isShortage ? 'KURANG' : 'SIAP',
                    'status_badge'                     => $isShortage ? 'badge-warning' : 'bg-[#F7E6EE] text-[#8E1B5C] border border-[#EBDDE4]',
                ];
            }
        }

        $totalItems = count($items);
        $isFullyAllocated = $hasSnapshot && $totalItems > 0 && $allocatedCount === $totalItems;
        $canAllocate = $hasSnapshot && ! $isFullyAllocated && $batch->status === BatchStatus::BAHAN && $canAllocateAll && $totalItems > 0;

        $readinessStatus = 'BELUM TERSEDIA';
        if ($hasSnapshot) {
            if ($isFullyAllocated) {
                $readinessStatus = 'TERALOKASI';
            } elseif ($shortageCount === 0 && ! $isFullyAllocated) {
                $readinessStatus = 'SIAP DIALOKASIKAN';
            } else {
                $readinessStatus = 'PERLU PEMBELIAN';
            }
        }

        return [
            'has_snapshot'         => $hasSnapshot,
            'items'                => $items,
            'total_materials'      => $totalItems,
            'sufficient_materials' => $sufficientCount,
            'shortage_materials'   => $shortageCount,
            'has_shortage'         => $shortageCount > 0,
            'is_fully_allocated'   => $isFullyAllocated,
            'can_allocate'         => $canAllocate,
            'readiness_status'     => $readinessStatus,
        ];
    }

    /**
     * Format decimal quantity string naturally for user display without trailing zeros.
     */
    private function formatQuantity(string $qty): string
    {
        $val = (float) $qty;
        return number_format($val, fmod($val, 1) !== 0.0 ? 2 : 0, ',', '.');
    }
}
