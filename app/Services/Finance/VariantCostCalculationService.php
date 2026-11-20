<?php

namespace App\Services\Finance;

use App\Enums\BatchStatus;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;

class VariantCostCalculationService
{
    public function __construct(
        private BatchCostCalculationService $batchCostService
    ) {}

    /**
     * Calculate estimated unit production cost for a variant based on
     * current BOM recipe (using current material MWA) and master direct cost rates.
     *
     * @param ProductVariant $variant
     * @return array{
     *     has_bom: bool,
     *     estimated_material_cost: string|null,
     *     estimated_direct_cost: string,
     *     estimated_total_cost: string|null,
     *     breakdown: array{
     *         materials: array<int, array{
     *             material_id: int,
     *             material_name: string,
     *             qty_per_unit: string,
     *             unit_symbol: string,
     *             avg_unit_cost: string,
     *             subtotal: string
     *         }>,
     *         printing: string,
     *         direct_labor: string,
     *         packaging_process: string,
     *         other_direct: string
     *     }
     * }
     */
    public function calculateEstimatedCost(ProductVariant $variant): array
    {
        $variant->load(['bomItems.material.unit', 'bomItems.material.stock']);

        $materialItems = [];
        $totalMaterialCost = '0.0000';
        $hasBom = $variant->bomItems->isNotEmpty();
        $missingReasons = [];
        $hasMissingMwa = false;
        $hasCorruptedBom = false;

        $isTenantIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);
        $hasValidVariantOutlet = $variant->outlet_id !== null && (int) $variant->outlet_id > 0;
        $variantOutletId = $hasValidVariantOutlet ? (int) $variant->outlet_id : null;

        $hasInvalidParentProduct = false;
        if ($isTenantIsolationEnabled) {
            $product = $variant->product;
            $productOutletId = $product?->outlet_id !== null && (int) $product->outlet_id > 0
                ? (int) $product->outlet_id
                : null;

            if (! $product || $productOutletId === null || $productOutletId !== $variantOutletId) {
                $hasInvalidParentProduct = true;
            }

            if (! $hasValidVariantOutlet || $hasInvalidParentProduct) {
                $hasCorruptedBom = true;
                $missingReasons[] = 'Integritas resep BOM tidak valid: varian belum terikat pada outlet yang sah atau produk induk tidak valid.';
            }
        }

        if (! $hasBom) {
            $missingReasons[] = 'BOM resep belum diatur / belum memiliki material.';
        }

        foreach ($variant->bomItems as $bomItem) {
            $qty = (string) $bomItem->qty_per_unit;
            $material = $bomItem->material;

            // Tenant boundary verification when isolation mode is active
            $isInvalidTenantMaterial = false;
            if ($isTenantIsolationEnabled) {
                if (
                    ! $hasValidVariantOutlet ||
                    $hasInvalidParentProduct ||
                    ! $material ||
                    $material->outlet_id === null ||
                    (int) $material->outlet_id <= 0 ||
                    (int) $material->outlet_id !== $variantOutletId
                ) {
                    $isInvalidTenantMaterial = true;
                }
            }

            if ($isInvalidTenantMaterial) {
                $hasCorruptedBom = true;
                $missingReasons[] = 'Integritas resep BOM tidak valid: terdapat material yang tidak sah atau berbeda outlet.';

                $materialItems[] = [
                    'material_id'   => null,
                    'material_name' => '[Integritas Tenant Tidak Valid: Material Tidak Sah]',
                    'qty_per_unit'  => $qty,
                    'unit_symbol'   => '—',
                    'unit_name'     => '—',
                    'avg_unit_cost' => null,
                    'subtotal'      => null,
                    'has_valid_mwa' => false,
                ];

                continue;
            }

            $stock = $material?->stock;

            $hasValidMwa = false;
            $avgCost = null;
            $subtotal = null;

            if (! $material || ! $stock || $stock->avg_unit_cost === null) {
                $hasMissingMwa = true;
                $missingReasons[] = "Modal material '" . ($material?->material_name ?? "#{$bomItem->material_id}") . "' belum tersedia (MWA belum tercatat).";
            } else {
                $avgCostVal = (string) $stock->avg_unit_cost;
                if (bccomp($avgCostVal, '0.0000', 4) <= 0) {
                    $hasMissingMwa = true;
                    $missingReasons[] = "Modal material '{$material->material_name}' belum tersedia (MWA = Rp 0).";
                    $avgCost = '0.0000';
                    $subtotal = '0.0000';
                } else {
                    $hasValidMwa = true;
                    $avgCost = $avgCostVal;
                    $subtotal = bcmul($qty, $avgCost, 4);
                    $totalMaterialCost = bcadd($totalMaterialCost, $subtotal, 4);
                }
            }

            $materialItems[] = [
                'material_id'   => $bomItem->material_id,
                'material_name' => $material?->material_name ?? "Material #{$bomItem->material_id}",
                'qty_per_unit'  => $qty,
                'unit_symbol'   => $material?->unit?->symbol ?? $material?->unit?->unit_name ?? 'unit',
                'unit_name'     => $material?->unit?->unit_name ?? $material?->unit?->symbol ?? 'unit',
                'avg_unit_cost' => $avgCost !== null ? number_format((float) $avgCost, 2, '.', '') : null,
                'subtotal'      => $subtotal !== null ? number_format((float) $subtotal, 2, '.', '') : null,
                'has_valid_mwa' => $hasValidMwa,
            ];
        }

        // Direct cost rates from master variant
        $printingCost = null;
        if ($variant->requires_printing === true) {
            if ($variant->printing_cost_per_unit === null) {
                $missingReasons[] = 'Biaya printing standar belum diisi.';
            } else {
                $printingCost = (string) $variant->printing_cost_per_unit;
            }
        } elseif ($variant->requires_printing === false) {
            $printingCost = '0.00';
        } else {
            $missingReasons[] = 'Rute printing belum ditetapkan.';
        }

        $laborCost = null;
        if ($variant->direct_labor_cost_per_unit === null) {
            $missingReasons[] = 'Biaya tenaga kerja standar belum diisi.';
        } else {
            $laborCost = (string) $variant->direct_labor_cost_per_unit;
        }

        $packagingCost = null;
        if ($variant->packaging_process_cost_per_good_unit === null) {
            $missingReasons[] = 'Biaya proses packing standar belum diisi.';
        } else {
            $packagingCost = (string) $variant->packaging_process_cost_per_good_unit;
        }

        $otherCost = null;
        if ($variant->other_direct_cost_per_unit === null) {
            $missingReasons[] = 'Biaya produksi tambahan standar belum diisi.';
        } else {
            $otherCost = (string) $variant->other_direct_cost_per_unit;
        }

        $hasCompleteDirectCosts = $printingCost !== null && $laborCost !== null && $packagingCost !== null && $otherCost !== null;

        $totalDirectCost = null;
        if ($hasCompleteDirectCosts) {
            $totalDirectCost = bcadd(
                bcadd(
                    bcadd($printingCost, $laborCost, 2),
                    $packagingCost,
                    2
                ),
                $otherCost,
                2
            );
        }

        $isAvailable = $hasBom && ! $hasMissingMwa && ! $hasCorruptedBom && $hasCompleteDirectCosts;

        $materialCostFormatted = null;
        $directCostFormatted = null;
        $estimatedTotal = null;

        if ($isAvailable) {
            $materialCostFormatted = number_format((float) $totalMaterialCost, 2, '.', '');
            $directCostFormatted = number_format((float) $totalDirectCost, 2, '.', '');
            $estimatedTotal = number_format((float) bcadd($materialCostFormatted, $directCostFormatted, 2), 2, '.', '');
        }

        $directCostsArray = [
            'requires_printing'                    => (bool) $variant->requires_printing,
            'printing'                             => $printingCost !== null ? number_format((float) $printingCost, 2, '.', '') : null,
            'printing_cost_per_unit'               => $printingCost !== null ? number_format((float) $printingCost, 2, '.', '') : null,
            'direct_labor'                         => $laborCost !== null ? number_format((float) $laborCost, 2, '.', '') : null,
            'direct_labor_cost_per_unit'           => $laborCost !== null ? number_format((float) $laborCost, 2, '.', '') : null,
            'packaging_process'                    => $packagingCost !== null ? number_format((float) $packagingCost, 2, '.', '') : null,
            'packaging_process_cost_per_good_unit' => $packagingCost !== null ? number_format((float) $packagingCost, 2, '.', '') : null,
            'other_direct'                         => $otherCost !== null ? number_format((float) $otherCost, 2, '.', '') : null,
            'other_direct_cost_per_unit'           => $otherCost !== null ? number_format((float) $otherCost, 2, '.', '') : null,
            'direct_subtotal'                      => $directCostFormatted,
            'direct_cost_subtotal'                 => $directCostFormatted,
        ];

        return [
            'is_available'            => $isAvailable,
            'has_bom'                 => $hasBom,
            'missing_reasons'         => array_values(array_unique($missingReasons)),
            'estimated_material_cost' => $materialCostFormatted,
            'material_estimated_cost' => $materialCostFormatted,
            'estimated_direct_cost'   => $directCostFormatted,
            'direct_estimated_cost'   => $directCostFormatted,
            'estimated_total_cost'    => $estimatedTotal,
            'total_estimated_cost'    => $estimatedTotal,
            'grand_total'             => $estimatedTotal,
            'breakdown'               => [
                'materials'            => $materialItems,
                'material_subtotal'    => $materialCostFormatted,
                'printing'             => $directCostsArray['printing'],
                'direct_labor'         => $directCostsArray['direct_labor'],
                'packaging_process'    => $directCostsArray['packaging_process'],
                'other_direct'         => $directCostsArray['other_direct'],
                'direct_subtotal'      => $directCostFormatted,
                'direct_cost_subtotal' => $directCostFormatted,
                'direct_costs'         => $directCostsArray,
                'grand_total'          => $estimatedTotal,
            ],
        ];
    }

    /**
     * Get the latest actual final HPP per good piece from authoritative completed batches.
     *
     * @param ProductVariant $variant
     * @return array{
     *     is_available: bool,
     *     latest_batch_id: int|null,
     *     latest_batch_no: string|null,
     *     latest_actual_hpp: string|null,
     *     total_hpp: string|null,
     *     good_qty: string|null,
     *     breakdown: array<string, mixed>
     * }
     */
    public function getLatestActualHpp(ProductVariant $variant): array
    {
        $completedBatches = ProductionBatch::query()
            ->whereHas('orderItem', function ($q) use ($variant) {
                $q->where('variant_id', $variant->id);
            })
            ->where('status', BatchStatus::SELESAI)
            ->with(['qcResult', 'latestResult', 'batchMaterials.material'])
            ->orderByLatestCompletion()
            ->get();

        foreach ($completedBatches as $batch) {
            $batchHpp = $this->batchCostService->calculateBatchHpp($batch);

            if ($batchHpp['is_ready'] && ! empty($batchHpp['hpp_per_good_pcs'])) {
                return [
                    'is_available'      => true,
                    'latest_batch_id'   => $batch->id,
                    'latest_batch_no'   => $batch->batch_no,
                    'latest_actual_hpp' => $batchHpp['hpp_per_good_pcs'],
                    'total_hpp'         => $batchHpp['total_hpp'],
                    'good_qty'          => (string) ($batch->qcResult?->good_qty ?? '0.0000'),
                    'breakdown'         => $batchHpp['breakdown'] ?? [],
                ];
            }
        }

        return [
            'is_available'      => false,
            'latest_batch_id'   => null,
            'latest_batch_no'   => null,
            'latest_actual_hpp' => null,
            'total_hpp'         => null,
            'good_qty'          => null,
            'breakdown'         => [],
        ];
    }

    /**
     * Resolve the authoritative reference HPP / modal for price comparison.
     * Always based on the Variant's current BOM & master direct cost rates (Reference HPP).
     *
     * @param ProductVariant $variant
     * @return array{
     *     reference_cost: string|null,
     *     source: string|null, // 'ESTIMATED' | null
     *     label: string,
     *     is_available: bool,
     *     has_reference: bool,
     *     latest_actual_hpp: string|null,
     *     estimated_total_cost: string|null,
     *     estimated_material_cost: string|null,
     *     estimated_direct_cost: string|null,
     *     breakdown: array<string, mixed>,
     *     missing_reasons: array<int, string>
     * }
     */
    public function getReferenceCost(ProductVariant $variant): array
    {
        $estimated = $this->calculateEstimatedCost($variant);

        if ($estimated['is_available'] && $estimated['estimated_total_cost'] !== null) {
            return [
                'reference_cost'          => $estimated['estimated_total_cost'],
                'source'                  => 'ESTIMATED',
                'label'                   => 'HPP / Modal Acuan',
                'is_available'            => true,
                'has_reference'           => true,
                'latest_actual_hpp'       => null,
                'estimated_total_cost'    => $estimated['estimated_total_cost'],
                'estimated_material_cost' => $estimated['estimated_material_cost'],
                'estimated_direct_cost'   => $estimated['estimated_direct_cost'],
                'breakdown'               => $estimated['breakdown'],
                'missing_reasons'         => [],
            ];
        }

        return [
            'reference_cost'          => null,
            'source'                  => null,
            'label'                   => 'Belum tersedia',
            'is_available'            => false,
            'has_reference'           => false,
            'latest_actual_hpp'       => null,
            'estimated_total_cost'    => null,
            'estimated_material_cost' => null,
            'estimated_direct_cost'   => null,
            'breakdown'               => $estimated['breakdown'] ?? [],
            'missing_reasons'         => ! empty($estimated['missing_reasons']) ? $estimated['missing_reasons'] : ['HPP / Modal Acuan belum dapat dihitung.'],
        ];
    }

    /**
     * Evaluate a selling price against the variant reference cost.
     *
     * @param ProductVariant $variant
     * @param string|float|int|null $sellingPrice
     * @return array{
     *     status: string,
     *     has_reference: bool,
     *     reference_cost: string|null,
     *     selling_price: string|null,
     *     margin: string|null,
     *     margin_per_unit: float|null,
     *     margin_percent: string|null,
     *     is_below_cost: bool,
     *     warning: string|null,
     *     reference_label: string
     * }
     */
    public function evaluatePriceMargin(ProductVariant $variant, string|float|int|null $sellingPrice): array
    {
        $ref = $this->getReferenceCost($variant);
        $priceStr = $sellingPrice !== null && $sellingPrice !== '' ? (string) $sellingPrice : null;

        if ($priceStr === null || ! is_numeric($priceStr)) {
            return [
                'status'          => 'PRICE_UNDEFINED',
                'price_status'    => 'HARGA BELUM DITETAPKAN',
                'status_message'  => 'Harga jual belum ditetapkan.',
                'has_reference'   => $ref['has_reference'],
                'reference_cost'  => $ref['reference_cost'],
                'selling_price'   => null,
                'margin'          => null,
                'margin_per_unit' => null,
                'margin_percent'  => null,
                'is_below_cost'   => false,
                'is_valid'        => false,
                'needs_revision'  => false,
                'warning'         => null,
                'reference_label' => $ref['label'],
            ];
        }

        if (! $ref['has_reference'] || $ref['reference_cost'] === null) {
            return [
                'status'          => 'COST_UNAVAILABLE',
                'price_status'    => 'BELUM DAPAT DIVERIFIKASI',
                'status_message'  => 'HPP belum tersedia sehingga harga jual belum dapat diverifikasi.',
                'has_reference'   => false,
                'reference_cost'  => null,
                'selling_price'   => number_format((float) $priceStr, 2, '.', ''),
                'margin'          => null,
                'margin_per_unit' => null,
                'margin_percent'  => null,
                'is_below_cost'   => false,
                'is_valid'        => false,
                'needs_revision'  => false,
                'warning'         => null,
                'reference_label' => $ref['label'],
            ];
        }

        $marginNominal = bcsub($priceStr, $ref['reference_cost'], 2);
        $cmp = bccomp($priceStr, $ref['reference_cost'], 2);
        $isAbove = $cmp > 0;
        $isBelowOrEqual = ! $isAbove;

        $marginPercent = '0.00';
        if (bccomp($priceStr, '0.00', 2) > 0) {
            $marginPercent = bcdiv(bcmul($marginNominal, '100', 4), $priceStr, 2);
        }

        if ($isAbove) {
            $priceStatus = 'VALID';
            $statusMessage = 'Harga jual di atas HPP';
        } else {
            $priceStatus = 'PERLU REVISI';
            $statusMessage = 'Harga jual saat ini sama dengan atau lebih rendah dari HPP. Perbarui harga jual sebelum Variant digunakan untuk order baru.';
        }

        return [
            'status'          => $isBelowOrEqual ? 'BELOW_OR_EQUAL_COST' : 'NORMAL',
            'price_status'    => $priceStatus,
            'status_message'  => $statusMessage,
            'has_reference'   => true,
            'reference_cost'  => number_format((float) $ref['reference_cost'], 2, '.', ''),
            'selling_price'   => number_format((float) $priceStr, 2, '.', ''),
            'margin'          => number_format((float) $marginNominal, 2, '.', ''),
            'margin_per_unit' => (float) $marginNominal,
            'margin_percent'  => number_format((float) $marginPercent, 2, '.', ''),
            'is_below_cost'   => $isBelowOrEqual,
            'is_valid'        => $isAbove,
            'needs_revision'  => $isBelowOrEqual,
            'warning'         => $isBelowOrEqual ? 'Harga jual tidak lebih tinggi dari HPP/modal.' : null,
            'reference_label' => $ref['label'],
        ];
    }
}
