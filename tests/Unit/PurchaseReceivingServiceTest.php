<?php

namespace Tests\Unit;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
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
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReceivingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseReceivingService $receivingService;
    private PurchaseOrderService $orderService;
    private Supplier $supplier;
    private Material $kainVoal;
    private Material $benang;
    private Unit $meter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->receivingService = app(PurchaseReceivingService::class);
        $this->orderService = app(PurchaseOrderService::class);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT. Bahan Tekstil',
            'phone'         => '021-888888',
            'active'        => true,
        ]);

        $this->kainVoal = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Voal Premium',
            'min_stock'     => 50,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->kainVoal->id, 'qty_on_hand' => '100.0000', 'avg_unit_cost' => '40000.0000']);

        $this->benang = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Benang Jahit',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->benang->id, 'qty_on_hand' => '0.0000', 'avg_unit_cost' => '0.0000']);
    }

    private function payPurchase(Purchase $purchase): void
    {
        $purchase->update([
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => \App\Enums\PurchaseApprovalStatus::APPROVED,
            'approved_at'     => now(),
        ]);
        \App\Models\Inventory\PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => $purchase->total_amount_string,
            'payment_date'   => '2026-08-28',
            'payment_method' => 'TRANSFER',
        ]);
    }

    public function test_receiving_fails_if_purchase_order_is_not_paid(): void
    {
        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);
        $purchase->update(['status' => PurchaseStatus::ORDERED]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Barang belum dapat diterima karena pembayaran supplier belum lunas.');

        $this->receivingService->receivePurchase($purchase);
    }

    public function test_receiving_fails_if_purchase_order_is_only_partially_paid(): void
    {
        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);
        $purchase->update(['status' => PurchaseStatus::ORDERED]);

        \App\Models\Inventory\PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => '100000.00',
            'payment_date'   => '2026-08-28',
            'payment_method' => 'TRANSFER',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Barang belum dapat diterima karena pembayaran supplier belum lunas.');

        $this->receivingService->receivePurchase($purchase);
    }

    public function test_full_receiving_ordered_po_updates_stock_cost_and_records_movement(): void
    {
        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-20', // past date to verify movement_date uses receiving date
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
            ],
        ]);
        $this->payPurchase($purchase);

        $user = \App\Models\System\User::factory()->create();
        $received = $this->receivingService->receivePurchase($purchase, $user->id);

        // 1. Purchase status becomes RECEIVED
        $this->assertEquals(PurchaseStatus::RECEIVED, $received->status);

        // 2. PurchaseItem qty_received equals ordered qty
        $item = $received->items->first();
        $this->assertEquals('50.0000', $item->qty_received);
        // Original unit_price is preserved
        $this->assertEquals('50000.00', $item->unit_price);

        // 3. MaterialStock moving average & stock updated:
        // (100 * 40,000 + 50 * 50,000) / 150 = 6,500,000 / 150 = 43,333.3333
        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('150.0000', $stock->qty_on_hand);
        $this->assertEquals('43333.3333', $stock->avg_unit_cost);

        // 4. StockMovement IN recorded correctly
        $this->assertEquals(1, StockMovement::count());
        $movement = StockMovement::first();
        $this->assertEquals($this->kainVoal->id, $movement->material_id);
        $this->assertEquals(MovementType::IN, $movement->movement_type);
        $this->assertEquals('50.0000', $movement->qty);
        $this->assertEquals('50000.0000', $movement->unit_cost_snapshot);
        $this->assertEquals('2500000.00', $movement->total_cost_snapshot);
        $this->assertEquals('100.0000', $movement->qty_before);
        $this->assertEquals('150.0000', $movement->qty_after);
        $this->assertEquals('purchase_items', $movement->reference_type);
        $this->assertEquals($item->id, $movement->reference_id);
        $this->assertEquals(now('Asia/Jakarta')->toDateString(), $movement->movement_date->format('Y-m-d'));
        $this->assertEquals($user->id, $movement->created_by);
    }

    public function test_moving_average_calculation_when_old_stock_is_zero(): void
    {
        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->benang->id, 'qty' => '50.0000', 'unit_price' => '15000.00'],
            ],
        ]);
        $this->payPurchase($purchase);

        $this->receivingService->receivePurchase($purchase);

        $stock = $this->benang->fresh(['stock'])->stock;
        $this->assertEquals('50.0000', $stock->qty_on_hand);
        $this->assertEquals('15000.0000', $stock->avg_unit_cost);
    }

    public function test_multi_item_receiving_updates_stocks_independently(): void
    {
        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => '50.0000', 'unit_price' => '50000.00'],
                ['material_id' => $this->benang->id, 'qty' => '20.0000', 'unit_price' => '15000.00'],
            ],
        ]);
        $this->payPurchase($purchase);

        $this->receivingService->receivePurchase($purchase);

        $this->assertEquals('150.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals('43333.3333', $this->kainVoal->fresh(['stock'])->stock->avg_unit_cost);

        $this->assertEquals('20.0000', $this->benang->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals('15000.0000', $this->benang->fresh(['stock'])->stock->avg_unit_cost);

        $this->assertEquals(2, StockMovement::count());
    }

    public function test_receiving_fails_if_purchase_status_is_not_ordered(): void
    {
        // 1. DRAFT PO
        $draftPO = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Purchase Order berstatus ORDERED yang dapat diterima');

        $this->receivingService->receivePurchase($draftPO);
    }

    public function test_double_receiving_is_rejected_and_creates_no_extra_movements(): void
    {
        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 10, 'unit_price' => 35000]],
        ]);
        $this->payPurchase($purchase);

        // First receive
        $this->receivingService->receivePurchase($purchase);
        $this->assertEquals(1, StockMovement::count());
        $this->assertEquals('110.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);

        // Second receive must fail
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Purchase Order berstatus ORDERED yang dapat diterima');

        $this->receivingService->receivePurchase($purchase);

        // Ensure no stock double increase
        $this->assertEquals('110.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(1, StockMovement::count());
    }

    public function test_receiving_still_succeeds_if_supplier_or_material_deactivated_after_ordered(): void
    {
        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 20, 'unit_price' => 35000]],
        ]);
        $this->payPurchase($purchase);

        // Deactivate supplier & material AFTER order is confirmed
        $this->supplier->update(['active' => false]);
        $this->kainVoal->update(['active' => false]);

        $received = $this->receivingService->receivePurchase($purchase);

        $this->assertEquals(PurchaseStatus::RECEIVED, $received->status);
        $this->assertEquals('120.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
    }

    public function test_receiving_fails_if_current_stock_is_negative(): void
    {
        $this->kainVoal->stock->update(['qty_on_hand' => '-5.0000']);

        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 20, 'unit_price' => 35000]],
        ]);
        $this->payPurchase($purchase);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas data stok tidak valid: stok saat ini untuk material');

        $this->receivingService->receivePurchase($purchase);
    }

    public function test_receiving_fails_if_material_stock_row_is_missing(): void
    {
        $this->kainVoal->stock->delete();

        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 20, 'unit_price' => 35000]],
        ]);
        $this->payPurchase($purchase);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('tidak ditemukan di sistem');

        $this->receivingService->receivePurchase($purchase);
    }

    public function test_atomic_rollback_when_one_item_encounters_failure(): void
    {
        // Set second material stock to negative to trigger failure
        $this->benang->stock->update(['qty_on_hand' => '-10.0000']);

        $initialKainStock = $this->kainVoal->fresh(['stock'])->stock->qty_on_hand;

        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [
                ['material_id' => $this->kainVoal->id, 'qty' => 30, 'unit_price' => 35000],
                ['material_id' => $this->benang->id, 'qty' => 10, 'unit_price' => 15000],
            ],
        ]);
        $this->payPurchase($purchase);

        try {
            $this->receivingService->receivePurchase($purchase);
            $this->fail('Expected DomainException was not thrown');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Integritas data stok tidak valid', $e->getMessage());
        }

        // Verify entire transaction rolled back
        $this->assertEquals($initialKainStock, $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());
        $this->assertEquals(PurchaseStatus::ORDERED, $purchase->fresh()->status);
        foreach ($purchase->fresh()->items as $item) {
            $this->assertEquals('0.0000', $item->qty_received);
        }
    }

    public function test_receiving_does_not_mutate_batch_order_or_create_stock_out(): void
    {
        $customer = Customer::create(['customer_name' => 'Toko Hijab']);
        $product = Product::create(['product_name' => 'Voal Polos', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'HJB-001',
            'active'       => true,
        ]);
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->kainVoal->id,
            'qty_per_unit' => 1.2900,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-28',
            'status'      => OrderStatus::DEAL,
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 85000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);

        $mrpService = app(BatchMaterialPlanningService::class);
        $mrpService->generateMaterialPlanning($batch);

        $purchase = $this->orderService->createPurchase([
            'supplier_id'   => $this->supplier->id,
            'purchase_date' => '2026-08-28',
            'items'         => [['material_id' => $this->kainVoal->id, 'qty' => 50, 'unit_price' => 40000]],
        ]);
        $this->payPurchase($purchase);

        $this->receivingService->receivePurchase($purchase);

        // Batch & Order status remain unchanged
        $this->assertEquals(BatchStatus::BAHAN, $batch->fresh()->status);
        $this->assertEquals(OrderStatus::DEAL, $order->fresh()->status);

        // Batch materials allocated_qty and used_qty remain 0
        $bm = BatchMaterial::where('batch_id', $batch->id)->first();
        $this->assertEquals('0.0000', $bm->allocated_qty);
        $this->assertEquals('0.0000', $bm->used_qty);

        // Historical MRP snapshot remains immutable
        $this->assertEquals('129.0000', $bm->required_qty);
        $this->assertEquals('100.0000', $bm->stock_at_calc);
        $this->assertEquals('29.0000', $bm->shortage_qty);

        // No StockMovement OUT
        $this->assertEquals(0, StockMovement::where('movement_type', MovementType::OUT)->count());
    }
}
