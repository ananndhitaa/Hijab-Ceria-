<?php

namespace App\Services\Master;

use App\Enums\PurchaseStatus;
use Illuminate\Support\Facades\DB;

class SupplierMaterialBackfillService
{
    /**
     * Bootstrap supplier_materials pivot from valid, non-cancelled Purchase Order history.
     *
     * Invariants & Rules:
     * - This service is strictly an initial bootstrap utility for empty/uninitialized mappings.
     * - Once initialized, master supplier-material relationships are authoritative.
     * - It must NOT continuously reconstruct deleted/corrected master mappings from purchase history.
     * - Historical PurchaseItems serve only as an initial bootstrap source, not a permanent source of truth.
     * - Rerunning normal application processes will not re-add corrected relationships.
     *
     * @param bool $force Force re-population even if mappings already exist
     * @return array
     */
    public function backfill(bool $force = false): array
    {
        $currentCount = DB::table('supplier_materials')->count();

        // If already initialized and not forced, preserve authoritative master mappings
        if ($currentCount > 0 && ! $force) {
            return [
                'status'                 => 'already_initialized',
                'message'                => 'Master relasi Supplier ↔ Material sudah terinisialisasi. Master data saat ini bersifat otoritatif.',
                'total_historical_pairs' => 0,
                'inserted'               => 0,
                'existing'               => $currentCount,
                'total_relations'        => $currentCount,
                'pairs'                  => [],
            ];
        }

        $cancelledStatus = PurchaseStatus::CANCELLED instanceof \BackedEnum
            ? PurchaseStatus::CANCELLED->value
            : 'CANCELLED';

        $historicalPairs = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.status', '!=', $cancelledStatus)
            ->select('purchases.supplier_id', 'purchase_items.material_id')
            ->distinct()
            ->orderBy('purchases.supplier_id')
            ->orderBy('purchase_items.material_id')
            ->get();

        $inserted = 0;
        $existing = 0;
        $now = now();

        foreach ($historicalPairs as $pair) {
            $alreadyExists = DB::table('supplier_materials')
                ->where('supplier_id', $pair->supplier_id)
                ->where('material_id', $pair->material_id)
                ->exists();

            if ($alreadyExists) {
                $existing++;
            } else {
                DB::table('supplier_materials')->insert([
                    'supplier_id' => $pair->supplier_id,
                    'material_id' => $pair->material_id,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $inserted++;
            }
        }

        $totalRelations = DB::table('supplier_materials')->count();

        return [
            'status'                 => 'bootstrapped',
            'message'                => 'Bootstrap relasi Supplier ↔ Material berhasil.',
            'total_historical_pairs' => $historicalPairs->count(),
            'inserted'               => $inserted,
            'existing'               => $existing,
            'total_relations'        => $totalRelations,
            'pairs'                  => $historicalPairs->toArray(),
        ];
    }
}
