<?php

namespace Tests\Unit\Production;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderStatusHistory;
use App\Services\Sales\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusAggregationTest extends TestCase
{
    use RefreshDatabase;

    private OrderStatusService $service;
    private Product $product;
    private ProductVariant $variantPlain;
    private ProductVariant $variantPrint;

    private \App\Models\Master\Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OrderStatusService::class);
        $this->product = Product::create(['product_name' => 'Product Multi Batch', 'active' => true]);
        $this->customer = \App\Models\Master\Customer::create(['customer_name' => 'Customer Multi', 'phone' => '08123456789']);
        \App\Models\System\User::factory()->create([
            'role'   => \App\Enums\UserRole::ADMIN,
            'status' => \App\Enums\UserStatus::ACTIVE,
        ]);

        $this->variantPlain = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Plain',
            'sku'               => 'SKU-PLN',
            'requires_printing' => false,
            'active'            => true,
        ]);

        $this->variantPrint = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Printed',
            'sku'               => 'SKU-PRT',
            'requires_printing' => true,
            'active'            => true,
        ]);
    }

    public function test_case_a_produksi_and_printing_yields_order_printing(): void
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-001', 'order_date' => now()->toDateString(), 'status' => OrderStatus::BAHAN]);
        $item1 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 50, 'unit_selling_price' => 50000, 'subtotal' => 2500000]);
        $item2 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPrint->id, 'qty_ordered' => 50, 'unit_selling_price' => 60000, 'subtotal' => 3000000]);

        ProductionBatch::create(['order_item_id' => $item1->id, 'batch_no' => 'BAT-001', 'target_qty' => 50, 'status' => BatchStatus::PRODUKSI, 'requires_printing' => false]);
        ProductionBatch::create(['order_item_id' => $item2->id, 'batch_no' => 'BAT-002', 'target_qty' => 50, 'status' => BatchStatus::PRINTING, 'requires_printing' => true]);

        $newStatus = $this->service->syncOperationalStatus($order);

        $this->assertEquals(OrderStatus::PRINTING, $newStatus);
        $this->assertEquals(OrderStatus::PRINTING, $order->fresh()->status);
    }

    public function test_case_b_qc_and_produksi_yields_order_produksi(): void
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-002', 'order_date' => now()->toDateString(), 'status' => OrderStatus::BAHAN]);
        $item1 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 50, 'unit_selling_price' => 50000, 'subtotal' => 2500000]);
        $item2 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 50, 'unit_selling_price' => 50000, 'subtotal' => 2500000]);

        ProductionBatch::create(['order_item_id' => $item1->id, 'batch_no' => 'BAT-003', 'target_qty' => 50, 'status' => BatchStatus::QC, 'requires_printing' => false]);
        ProductionBatch::create(['order_item_id' => $item2->id, 'batch_no' => 'BAT-004', 'target_qty' => 50, 'status' => BatchStatus::PRODUKSI, 'requires_printing' => false]);

        $newStatus = $this->service->syncOperationalStatus($order);

        $this->assertEquals(OrderStatus::PRODUKSI, $newStatus);
        $this->assertEquals(OrderStatus::PRODUKSI, $order->fresh()->status);
    }

    public function test_case_c_selesai_and_packing_yields_order_packing(): void
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-003', 'order_date' => now()->toDateString(), 'status' => OrderStatus::BAHAN]);
        $item1 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 50, 'unit_selling_price' => 50000, 'subtotal' => 2500000]);
        $item2 = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 50, 'unit_selling_price' => 50000, 'subtotal' => 2500000]);

        ProductionBatch::create(['order_item_id' => $item1->id, 'batch_no' => 'BAT-005', 'target_qty' => 50, 'status' => BatchStatus::SELESAI, 'requires_printing' => false]);
        ProductionBatch::create(['order_item_id' => $item2->id, 'batch_no' => 'BAT-006', 'target_qty' => 50, 'status' => BatchStatus::PACKING, 'requires_printing' => false]);

        $newStatus = $this->service->syncOperationalStatus($order);

        $this->assertEquals(OrderStatus::PACKING, $newStatus);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_case_d_all_active_batches_selesai_yields_order_packing_not_selesai(): void
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-004', 'order_date' => now()->toDateString(), 'status' => OrderStatus::BAHAN]);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000, 'subtotal' => 5000000]);

        ProductionBatch::create(['order_item_id' => $item->id, 'batch_no' => 'BAT-007', 'target_qty' => 100, 'status' => BatchStatus::SELESAI, 'requires_printing' => false]);

        $newStatus = $this->service->syncOperationalStatus($order);

        // Batch SELESAI maps to Order candidate PACKING, NOT Order SELESAI (Shipment owns delivery/completion)
        $this->assertEquals(OrderStatus::PACKING, $newStatus);
        $this->assertEquals(OrderStatus::PACKING, $order->fresh()->status);
    }

    public function test_case_e_unbatched_remaining_quantity_forces_bahan(): void
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-005', 'order_date' => now()->toDateString(), 'status' => OrderStatus::BAHAN]);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000, 'subtotal' => 5000000]);

        // Batch only covers 60 pcs, remaining 40 pcs unbatched
        ProductionBatch::create(['order_item_id' => $item->id, 'batch_no' => 'BAT-008', 'target_qty' => 60, 'status' => BatchStatus::PRODUKSI, 'requires_printing' => false]);

        $newStatus = $this->service->syncOperationalStatus($order);

        $this->assertNull($newStatus); // Status remains BAHAN
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);
    }

    public function test_case_f_cancelled_batch_releases_remainder_and_forces_bahan(): void
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-006', 'order_date' => now()->toDateString(), 'status' => OrderStatus::BAHAN]);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000, 'subtotal' => 5000000]);

        // Batch 1 (50 pcs) SELESAI
        ProductionBatch::create(['order_item_id' => $item->id, 'batch_no' => 'BAT-009', 'target_qty' => 50, 'status' => BatchStatus::SELESAI, 'requires_printing' => false]);
        // Batch 2 (50 pcs) CANCELLED -> Leaves 50 pcs unscheduled
        ProductionBatch::create(['order_item_id' => $item->id, 'batch_no' => 'BAT-010', 'target_qty' => 50, 'status' => BatchStatus::CANCELLED, 'requires_printing' => false]);

        $newStatus = $this->service->syncOperationalStatus($order);

        $this->assertNull($newStatus); // Status remains BAHAN
        $this->assertEquals(OrderStatus::BAHAN, $order->fresh()->status);
    }

    public function test_unchanged_aggregate_status_creates_no_duplicate_history(): void
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-007', 'order_date' => now()->toDateString(), 'status' => OrderStatus::PRINTING]);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPrint->id, 'qty_ordered' => 50, 'unit_selling_price' => 60000, 'subtotal' => 3000000]);

        ProductionBatch::create(['order_item_id' => $item->id, 'batch_no' => 'BAT-011', 'target_qty' => 50, 'status' => BatchStatus::PRINTING, 'requires_printing' => true]);

        $initialHistoryCount = OrderStatusHistory::where('order_id', $order->id)->count();

        $result = $this->service->syncOperationalStatus($order);

        $this->assertNull($result);
        $this->assertEquals($initialHistoryCount, OrderStatusHistory::where('order_id', $order->id)->count());
    }

    public function test_valid_changed_aggregate_creates_exactly_one_history(): void
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-008', 'order_date' => now()->toDateString(), 'status' => OrderStatus::BAHAN]);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 50, 'unit_selling_price' => 50000, 'subtotal' => 2500000]);

        ProductionBatch::create(['order_item_id' => $item->id, 'batch_no' => 'BAT-012', 'target_qty' => 50, 'status' => BatchStatus::PRODUKSI, 'requires_printing' => false]);

        $initialHistoryCount = OrderStatusHistory::where('order_id', $order->id)->count();

        $result = $this->service->syncOperationalStatus($order);

        $this->assertEquals(OrderStatus::PRODUKSI, $result);
        $this->assertEquals($initialHistoryCount + 1, OrderStatusHistory::where('order_id', $order->id)->count());
        $this->assertEquals(OrderStatus::PRODUKSI, OrderStatusHistory::where('order_id', $order->id)->latest()->first()->status);
    }

    public function test_existing_shipped_or_completed_order_is_not_regressed(): void
    {
        // Order at DIKIRIM
        $orderShipped = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-009', 'order_date' => now()->toDateString(), 'status' => OrderStatus::DIKIRIM]);
        $item1 = OrderItem::create(['order_id' => $orderShipped->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 50, 'unit_selling_price' => 50000, 'subtotal' => 2500000]);
        ProductionBatch::create(['order_item_id' => $item1->id, 'batch_no' => 'BAT-013', 'target_qty' => 50, 'status' => BatchStatus::PRODUKSI, 'requires_printing' => false]);

        $resultShipped = $this->service->syncOperationalStatus($orderShipped);
        $this->assertNull($resultShipped);
        $this->assertEquals(OrderStatus::DIKIRIM, $orderShipped->fresh()->status);

        // Order at SELESAI
        $orderDone = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-010', 'order_date' => now()->toDateString(), 'status' => OrderStatus::SELESAI]);
        $item2 = OrderItem::create(['order_id' => $orderDone->id, 'variant_id' => $this->variantPlain->id, 'qty_ordered' => 50, 'unit_selling_price' => 50000, 'subtotal' => 2500000]);
        ProductionBatch::create(['order_item_id' => $item2->id, 'batch_no' => 'BAT-014', 'target_qty' => 50, 'status' => BatchStatus::BAHAN, 'requires_printing' => false]);

        $resultDone = $this->service->syncOperationalStatus($orderDone);
        $this->assertNull($resultDone);
        $this->assertEquals(OrderStatus::SELESAI, $orderDone->fresh()->status);
    }
}
