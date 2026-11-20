<?php

namespace Tests\Unit;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\PurchasePayment;
use App\Models\Inventory\StockMovement;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\MaterialAllocationService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    private MaterialAllocationService $allocationService;
    private BatchMaterialPlanningService $mrpService;
    private User $gudangUser;
    private Material $kainVoal;
    private Material $benang;
    private Unit $meter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->allocationService = app(MaterialAllocationService::class);
        $this->mrpService = app(BatchMaterialPlanningService::class);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->kainVoal = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Voal Premium',
            'min_stock'     => 20,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '150.0000',
            'avg_unit_cost' => '43333.3333',
        ]);

        $this->benang = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Benang Jahit',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->benang->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '15000.0000',
        ]);
    }

    private function createBatchWithBom(array $bomConfig, int $targetQty = 100): ProductionBatch
    {
        $customer = Customer::create(['customer_name' => 'Toko Busana']);
        $product = Product::create(['product_name' => 'Hijab Segiempat', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Navy',
            'sku'          => 'HJB-NVY',
            'active'       => true,
        ]);

        foreach ($bomConfig as $materialId => $qtyPerUnit) {
            BomItem::create([
                'variant_id'   => $variant->id,
                'material_id'  => $materialId,
                'qty_per_unit' => $qtyPerUnit,
            ]);
        }

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0100',
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::DEAL,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 75000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202608-0100',
            'target_qty'    => $targetQty,
            'status'        => BatchStatus::BAHAN,
        ]);

        $this->mrpService->generateMaterialPlanning($batch);

        return $batch;
    }

    public function test_full_allocation_decrements_stock_records_out_movement_and_updates_allocated_qty(): void
    {
        // Batch target 100 pcs, kainVoal 1.29 m/pcs -> required 129 m
        $batch = $this->createBatchWithBom([$this->kainVoal->id => 1.2900], 100);

        $allocatedBatch = $this->allocationService->allocateBatchMaterials($batch, $this->gudangUser->id);

        // 1. ProductionBatch status remains BAHAN
        $this->assertEquals(BatchStatus::BAHAN, $allocatedBatch->status);

        // 2. BatchMaterial: allocated_qty = required_qty (129), used_qty = 0
        $bm = BatchMaterial::where('batch_id', $batch->id)->first();
        $this->assertEquals('129.0000', $bm->allocated_qty);
        $this->assertEquals('0.0000', $bm->used_qty);

        // 3. Historical MRP snapshot remains immutable
        $this->assertEquals('129.0000', $bm->required_qty);
        $this->assertEquals('150.0000', $bm->stock_at_calc);
        $this->assertEquals('0.0000', $bm->shortage_qty);

        // 4. MaterialStock: qty_on_hand decrements to 21 (150 - 129), avg_unit_cost EXACTLY unchanged (43333.3333)
        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('21.0000', $stock->qty_on_hand);
        $this->assertEquals('43333.3333', $stock->avg_unit_cost);

        // 5. StockMovement OUT created with exact snapshots
        $this->assertEquals(1, StockMovement::count());
        $movement = StockMovement::first();
        $this->assertEquals($this->kainVoal->id, $movement->material_id);
        $this->assertEquals(MovementType::OUT, $movement->movement_type);
        $this->assertEquals('129.0000', $movement->qty);
        $this->assertEquals('43333.3333', $movement->unit_cost_snapshot);
        // total_cost_snapshot = 129 * 43333.3333 = 5590000.00
        $this->assertEquals('5590000.00', $movement->total_cost_snapshot);
        $this->assertEquals('150.0000', $movement->qty_before);
        $this->assertEquals('21.0000', $movement->qty_after);
        $this->assertEquals('batch_materials', $movement->reference_type);
        $this->assertEquals($bm->id, $movement->reference_id);
        $this->assertEquals(now('Asia/Jakarta')->toDateString(), $movement->movement_date->format('Y-m-d'));
        $this->assertEquals($this->gudangUser->id, $movement->created_by);
    }

    public function test_multi_material_allocation_succeeds_when_all_stocks_sufficient(): void
    {
        $batch = $this->createBatchWithBom([
            $this->kainVoal->id => 1.2000, // 120 m (Stock 150)
            $this->benang->id   => 0.2000, // 20 m (Stock 50)
        ], 100);

        $this->allocationService->allocateBatchMaterials($batch, $this->gudangUser->id);

        $this->assertEquals('30.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals('43333.3333', $this->kainVoal->fresh(['stock'])->stock->avg_unit_cost);

        $this->assertEquals('30.0000', $this->benang->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals('15000.0000', $this->benang->fresh(['stock'])->stock->avg_unit_cost);

        $this->assertEquals(2, StockMovement::count());
        foreach (BatchMaterial::where('batch_id', $batch->id)->get() as $bm) {
            $this->assertEquals($bm->required_qty, $bm->allocated_qty);
            $this->assertEquals('0.0000', $bm->used_qty);
        }
    }

    public function test_allocation_fails_and_rolls_back_if_any_stock_insufficient(): void
    {
        // Kain sufficient (100 req / 150 stock), Benang insufficient (60 req / 50 stock)
        $batch = $this->createBatchWithBom([
            $this->kainVoal->id => 1.0000, // 100 m
            $this->benang->id   => 0.6000, // 60 m
        ], 100);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stok belum mencukupi untuk mengalokasikan seluruh kebutuhan bahan batch.');

        $this->allocationService->allocateBatchMaterials($batch);

        // Verify rollback: stock remains untouched, no OUT movements, allocated_qty remains 0
        $this->assertEquals('150.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals('50.0000', $this->benang->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());
        foreach (BatchMaterial::where('batch_id', $batch->id)->get() as $bm) {
            $this->assertEquals('0.0000', $bm->allocated_qty);
        }
    }

    public function test_double_allocation_is_rejected_without_extra_stock_reduction(): void
    {
        $batch = $this->createBatchWithBom([$this->kainVoal->id => 1.0000], 100);

        // First allocation succeeds
        $this->allocationService->allocateBatchMaterials($batch, $this->gudangUser->id);
        $this->assertEquals('50.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(1, StockMovement::count());

        // Second allocation must fail safely
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Bahan untuk batch ini sudah dialokasikan.');

        $this->allocationService->allocateBatchMaterials($batch, $this->gudangUser->id);

        $this->assertEquals('50.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(1, StockMovement::count());
    }

    public function test_allocation_succeeds_if_material_was_deactivated_after_mrp_snapshot(): void
    {
        $batch = $this->createBatchWithBom([$this->kainVoal->id => 1.0000], 100);

        // Deactivate material AFTER MRP snapshot
        $this->kainVoal->update(['active' => false]);

        $this->allocationService->allocateBatchMaterials($batch, $this->gudangUser->id);

        $this->assertEquals('50.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals('100.0000', BatchMaterial::where('batch_id', $batch->id)->first()->allocated_qty);
    }

    public function test_allocation_cost_snapshot_is_immutable_against_later_purchase_receiving(): void
    {
        $batch = $this->createBatchWithBom([$this->kainVoal->id => 1.0000], 100);

        // Allocate at current avg 43333.3333
        $this->allocationService->allocateBatchMaterials($batch, $this->gudangUser->id);
        $outMovement = StockMovement::where('movement_type', MovementType::OUT)->first();
        $this->assertEquals('43333.3333', $outMovement->unit_cost_snapshot);

        // Receive new PO with higher price (Rp60.000)
        $supplier = Supplier::create(['supplier_name' => 'PT. Supplier', 'active' => true]);
        $poService = app(PurchaseOrderService::class);
        $receivingService = app(PurchaseReceivingService::class);

        $purchase = $poService->createPurchase([
            'supplier_id'   => $supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 100, 'unit_price' => 60000]],
        ]);
        $purchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => $purchase->purchase_date->toDateString(),
            'amount'         => $purchase->total_amount,
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->gudangUser->id,
        ]);
        $receivingService->receivePurchase($purchase);

        // MaterialStock avg cost has changed
        $newAvg = $this->kainVoal->fresh(['stock'])->stock->avg_unit_cost;
        $this->assertNotEquals('43333.3333', $newAvg);

        // Historical OUT snapshot MUST remain 43333.3333
        $this->assertEquals('43333.3333', $outMovement->fresh()->unit_cost_snapshot);
    }

    public function test_mixed_or_corrupt_allocation_state_is_rejected(): void
    {
        $batch = $this->createBatchWithBom([
            $this->kainVoal->id => 1.0000,
            $this->benang->id   => 0.2000,
        ], 100);

        // Corrupt state: manually set one item partial allocated
        BatchMaterial::where('batch_id', $batch->id)->where('material_id', $this->kainVoal->id)
            ->update(['allocated_qty' => '50.0000']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas data alokasi tidak valid');

        $this->allocationService->allocateBatchMaterials($batch);
    }

    public function test_allocation_fails_if_used_qty_is_greater_than_zero_before_allocation(): void
    {
        $batch = $this->createBatchWithBom([$this->kainVoal->id => 1.0000], 100);

        BatchMaterial::where('batch_id', $batch->id)->update(['used_qty' => '10.0000']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas data tidak valid: ditemukan data pemakaian produksi');

        $this->allocationService->allocateBatchMaterials($batch);
    }

    public function test_allocation_fails_if_required_qty_is_zero_or_negative(): void
    {
        $batch = $this->createBatchWithBom([$this->kainVoal->id => 1.0000], 100);

        BatchMaterial::where('batch_id', $batch->id)->update(['required_qty' => '0.0000']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('bernilai 0 atau negatif');

        $this->allocationService->allocateBatchMaterials($batch);
    }

    public function test_allocation_fails_if_material_stock_row_is_missing(): void
    {
        $batch = $this->createBatchWithBom([$this->kainVoal->id => 1.0000], 100);

        $this->kainVoal->stock->delete();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tidak ditemukan di sistem');

        $this->allocationService->allocateBatchMaterials($batch);
    }

    public function test_allocation_fails_if_stock_on_hand_or_avg_cost_is_negative(): void
    {
        $batch = $this->createBatchWithBom([$this->kainVoal->id => 1.0000], 100);

        $this->kainVoal->stock->update(['avg_unit_cost' => '-1000.0000']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('harga rata-rata untuk material "Kain Voal Premium" bernilai negatif');

        $this->allocationService->allocateBatchMaterials($batch);
    }
}
