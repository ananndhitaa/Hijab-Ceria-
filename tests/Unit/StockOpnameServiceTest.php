<?php

namespace Tests\Unit;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
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
use App\Services\Inventory\StockOpnameService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockOpnameService $service;
    private User $gudangUser;
    private Material $kainVoal;
    private Unit $meter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StockOpnameService::class);

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
    }

    public function test_upward_stock_adjustment_100_to_108(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $movement = $this->service->adjustStock(
            $this->kainVoal,
            '108.0000',
            'Audit fisik bulanan: ditemukan kelebihan kain 8 meter.',
            $this->gudangUser->id
        );

        $this->assertNotNull($movement);
        $this->assertEquals(MovementType::ADJUSTMENT, $movement->movement_type);
        $this->assertEquals('8.0000', $movement->qty);
        $this->assertEquals('100.0000', $movement->qty_before);
        $this->assertEquals('108.0000', $movement->qty_after);
        $this->assertEquals('40000.0000', $movement->unit_cost_snapshot);
        $this->assertEquals('320000.00', $movement->total_cost_snapshot);
        $this->assertNull($movement->reference_type);
        $this->assertNull($movement->reference_id);
        $this->assertEquals(now('Asia/Jakarta')->toDateString(), $movement->movement_date->toDateString());
        $this->assertEquals('Audit fisik bulanan: ditemukan kelebihan kain 8 meter.', $movement->notes);
        $this->assertEquals($this->gudangUser->id, $movement->created_by);

        // Invariant check: qty_on_hand updated, avg_unit_cost strictly UNCHANGED
        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('108.0000', $stock->qty_on_hand);
        $this->assertEquals('40000.0000', $stock->avg_unit_cost);
    }

    public function test_downward_stock_adjustment_100_to_95(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $movement = $this->service->adjustStock(
            $this->kainVoal,
            '95.0000',
            'Audit fisik bulanan: kain cacat basah 5 meter dipisahkan.',
            $this->gudangUser->id
        );

        $this->assertNotNull($movement);
        $this->assertEquals(MovementType::ADJUSTMENT, $movement->movement_type);
        $this->assertEquals('5.0000', $movement->qty);
        $this->assertEquals('100.0000', $movement->qty_before);
        $this->assertEquals('95.0000', $movement->qty_after);
        $this->assertEquals('40000.0000', $movement->unit_cost_snapshot);
        $this->assertEquals('200000.00', $movement->total_cost_snapshot);

        // Invariant check: avg_unit_cost strictly UNCHANGED
        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('95.0000', $stock->qty_on_hand);
        $this->assertEquals('40000.0000', $stock->avg_unit_cost);
    }

    public function test_no_change_adjustment_100_to_100_returns_null_and_creates_no_movement(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $movement = $this->service->adjustStock(
            $this->kainVoal,
            '100.0000',
            'Stok fisik sesuai hasil audit.',
            $this->gudangUser->id
        );

        $this->assertNull($movement);
        $this->assertEquals(0, StockMovement::count());

        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('100.0000', $stock->qty_on_hand);
        $this->assertEquals('40000.0000', $stock->avg_unit_cost);
    }

    public function test_exact_decimal_quantities_are_supported(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '10.2500',
            'avg_unit_cost' => '15000.0000',
        ]);

        $movement = $this->service->adjustStock(
            $this->kainVoal,
            '15.7500',
            'Penyesuaian desimal presisi.',
            $this->gudangUser->id
        );

        $this->assertNotNull($movement);
        $this->assertEquals('5.5000', $movement->qty);
        $this->assertEquals('10.2500', $movement->qty_before);
        $this->assertEquals('15.7500', $movement->qty_after);
        $this->assertEquals('82500.00', $movement->total_cost_snapshot);

        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('15.7500', $stock->qty_on_hand);
        $this->assertEquals('15000.0000', $stock->avg_unit_cost);
    }

    public function test_zero_cost_upward_adjustment_is_allowed_and_preserves_zero_cost(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '0.0000',
            'avg_unit_cost' => '0.0000',
        ]);

        $movement = $this->service->adjustStock(
            $this->kainVoal,
            '10.0000',
            'Opname awal sampel tanpa biaya pembelian.',
            $this->gudangUser->id
        );

        $this->assertNotNull($movement);
        $this->assertEquals(MovementType::ADJUSTMENT, $movement->movement_type);
        $this->assertEquals('10.0000', $movement->qty);
        $this->assertEquals('0.0000', $movement->qty_before);
        $this->assertEquals('10.0000', $movement->qty_after);
        $this->assertEquals('0.0000', $movement->unit_cost_snapshot);
        $this->assertEquals('0.00', $movement->total_cost_snapshot);

        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('10.0000', $stock->qty_on_hand);
        $this->assertEquals('0.0000', $stock->avg_unit_cost);
    }

    public function test_zero_cost_downward_adjustment_is_allowed(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '10.0000',
            'avg_unit_cost' => '0.0000',
        ]);

        $movement = $this->service->adjustStock(
            $this->kainVoal,
            '4.0000',
            'Pengurangan sampel tanpa biaya.',
            $this->gudangUser->id
        );

        $this->assertNotNull($movement);
        $this->assertEquals('6.0000', $movement->qty);
        $this->assertEquals('0.00', $movement->total_cost_snapshot);

        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('4.0000', $stock->qty_on_hand);
        $this->assertEquals('0.0000', $stock->avg_unit_cost);
    }

    public function test_notes_trimming_and_empty_notes_rejection(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        // Empty string
        try {
            $this->service->adjustStock($this->kainVoal, '110.0000', '');
            $this->fail('Expected DomainException for empty notes');
        } catch (DomainException $e) {
            $this->assertEquals('Catatan penyesuaian stok wajib diisi.', $e->getMessage());
        }

        // Whitespace only
        try {
            $this->service->adjustStock($this->kainVoal, '110.0000', "   \n\t  ");
            $this->fail('Expected DomainException for whitespace-only notes');
        } catch (DomainException $e) {
            $this->assertEquals('Catatan penyesuaian stok wajib diisi.', $e->getMessage());
        }

        // Untrimmed valid notes are persisted trimmed
        $movement = $this->service->adjustStock($this->kainVoal, '110.0000', "  Catatan opname valid  \n");
        $this->assertEquals('Catatan opname valid', $movement->notes);
    }

    public function test_negative_physical_qty_is_rejected(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Kuantitas fisik stok tidak boleh bernilai negatif.');

        $this->service->adjustStock($this->kainVoal, '-5.0000', 'Catatan');
    }

    public function test_non_numeric_physical_qty_is_rejected(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Kuantitas fisik stok harus berupa angka yang valid.');

        $this->service->adjustStock($this->kainVoal, 'abc', 'Catatan');
    }

    public function test_missing_material_stock_row_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas data stok tidak valid: data stok untuk material "Kain Voal Premium" tidak ditemukan di sistem.');

        $this->service->adjustStock($this->kainVoal, '50.0000', 'Catatan');
    }

    public function test_corrupt_negative_stock_in_db_is_rejected(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '-10.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas data stok tidak valid: stok saat ini untuk material "Kain Voal Premium" bernilai negatif (-10.0000).');

        $this->service->adjustStock($this->kainVoal, '50.0000', 'Catatan');
    }

    public function test_corrupt_negative_avg_cost_in_db_is_rejected(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '-5000.0000',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas data stok tidak valid: harga rata-rata untuk material "Kain Voal Premium" bernilai negatif (-5000.0000).');

        $this->service->adjustStock($this->kainVoal, '50.0000', 'Catatan');
    }

    public function test_inactive_material_can_be_adjusted(): void
    {
        $this->kainVoal->update(['active' => false]);

        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $movement = $this->service->adjustStock(
            $this->kainVoal,
            '40.0000',
            'Opname material nonaktif.',
            $this->gudangUser->id
        );

        $this->assertNotNull($movement);
        $this->assertEquals('40.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
    }

    public function test_unallocated_batch_live_shortage_recalculates_naturally_after_stock_opname(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $customer = Customer::create([
            'customer_name' => 'Nayara Customer',
            'phone'         => '081234567890',
        ]);

        $order = Order::create([
            'order_no'    => 'SO-TEST-001',
            'customer_id' => $customer->id,
            'status'      => OrderStatus::BAHAN,
            'order_date'  => '2026-08-29',
        ]);

        $product = Product::create([
            'product_name' => 'Gamis Silk',
            'product_code' => 'PRD-GS-01',
        ]);

        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Black L',
            'sku'          => 'VAR-GS-01-BLK',
        ]);

        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 55000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-TEST-001',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);

        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        // Before opname: Required 129, Stock 100 -> Shortage 29 (KURANG)
        $mrpBefore = $mrpService->getMaterialRequirementsData($batch);
        $this->assertEquals('29.0000', $mrpBefore['items'][0]['shortage_qty']);
        $this->assertEquals('KURANG', $mrpBefore['items'][0]['status_label']);
        $this->assertTrue($mrpBefore['has_shortage']);

        // Perform stock opname: 100 -> 150
        $this->service->adjustStock($this->kainVoal, '150.0000', 'Opname penambahan stok.', $this->gudangUser->id);

        // After opname: Required 129, Stock 150 -> Live shortage 0 (SIAP)
        $mrpAfter = $mrpService->getMaterialRequirementsData($batch);
        $this->assertEquals('0.0000', $mrpAfter['items'][0]['shortage_qty']);
        $this->assertEquals('SIAP', $mrpAfter['items'][0]['status_label']);
        $this->assertFalse($mrpAfter['has_shortage']);

        // Historical snapshot in batch_materials remains immutable
        $bm = BatchMaterial::where('batch_id', $batch->id)->where('material_id', $this->kainVoal->id)->first();
        $this->assertEquals('129.0000', $bm->required_qty);
        $this->assertEquals('100.0000', $bm->stock_at_calc);
        $this->assertEquals('29.0000', $bm->shortage_qty);
    }

    public function test_allocated_batch_remains_teralokasi_and_no_status_mutation(): void
    {
        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '150.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $customer = Customer::create([
            'customer_name' => 'Nayara Customer 2',
            'phone'         => '081234567891',
        ]);

        $order = Order::create([
            'order_no'    => 'SO-TEST-002',
            'customer_id' => $customer->id,
            'status'      => OrderStatus::BAHAN,
            'order_date'  => '2026-08-29',
        ]);

        $product = Product::create([
            'product_name' => 'Hijab Segi Empat',
            'product_code' => 'PRD-HSE-01',
        ]);

        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Dusty Pink',
            'sku'          => 'VAR-HSE-01-DPK',
        ]);

        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 55000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-TEST-002',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);

        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        $allocationService = app(MaterialAllocationService::class);
        $allocationService->allocateBatchMaterials($batch, $this->gudangUser->id);

        // Stock after allocation: 150 - 129 = 21.0000
        $this->assertEquals('21.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);

        // Opname warehouse stock: 21 -> 10
        $this->service->adjustStock($this->kainVoal, '10.0000', 'Koreksi sisa gudang.', $this->gudangUser->id);

        // Batch status remains BAHAN, BatchMaterial allocated_qty remains 129, status TERALOKASI
        $this->assertEquals(BatchStatus::BAHAN, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);

        $bm = BatchMaterial::where('batch_id', $batch->id)->where('material_id', $this->kainVoal->id)->first();
        $this->assertEquals('129.0000', $bm->allocated_qty);
        $this->assertEquals('0.0000', $bm->used_qty);

        $mrpAfter = $mrpService->getMaterialRequirementsData($batch->fresh());
        $this->assertTrue($mrpAfter['is_fully_allocated']);
        $this->assertEquals('TERALOKASI', $mrpAfter['items'][0]['status_label']);
    }
}
