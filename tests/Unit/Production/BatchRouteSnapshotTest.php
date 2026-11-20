<?php

namespace Tests\Unit\Production;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\BomItem;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Production\BatchService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchRouteSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private BatchService $batchService;
    private User $adminUser;
    private Product $product;
    private \App\Models\Master\Customer $customer;
    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batchService = app(BatchService::class);
        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->product = Product::create(['product_name' => 'Produk Khusus', 'active' => true]);
        $this->customer = \App\Models\Master\Customer::create(['customer_name' => 'Customer Batch', 'phone' => '08123456789']);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Khusus',
            'min_stock'     => 10,
            'active'        => true,
        ]);
    }

    private function attachBom(ProductVariant $variant, float $qtyPerUnit = 1.0): void
    {
        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => $qtyPerUnit,
        ]);
    }

    private function attachConfirmedDp(Order $order): void
    {
        $invoice = \App\Models\Sales\Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => '5000000.00',
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);

        \App\Models\Sales\Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => '2500000.00',
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);
    }

    public function test_new_batch_copies_variant_requires_printing_snapshot_true(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Varian Print',
            'sku'                                  => 'SKU-PRT-001',
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => '2000.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);
        $this->attachBom($variant);

        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-101', 'order_date' => now()->toDateString(), 'status' => OrderStatus::DEAL]);
        $this->attachConfirmedDp($order);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000, 'subtotal' => 5000000]);

        $result = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 100,
        ], $this->adminUser);

        /** @var ProductionBatch $batch */
        $batch = $result['batch'];
        $this->assertTrue($batch->requires_printing);
        $this->assertEquals('Dengan Printing', $batch->route_label);
        $this->assertEquals('2000.00', $batch->printing_cost_per_unit_snapshot);
        $this->assertEquals('5000.00', $batch->direct_labor_cost_per_unit_snapshot);
        $this->assertEquals('1000.00', $batch->packaging_process_cost_per_good_unit_snapshot);
        $this->assertEquals('500.00', $batch->other_direct_cost_per_unit_snapshot);
    }

    public function test_new_batch_copies_variant_requires_printing_snapshot_false(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Varian Plain',
            'sku'                                  => 'SKU-PLN-001',
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => '0.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);
        $this->attachBom($variant);

        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-102', 'order_date' => now()->toDateString(), 'status' => OrderStatus::DEAL]);
        $this->attachConfirmedDp($order);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000, 'subtotal' => 5000000]);

        $result = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 100,
        ], $this->adminUser);

        /** @var ProductionBatch $batch */
        $batch = $result['batch'];
        $this->assertFalse($batch->requires_printing);
        $this->assertEquals('Tanpa Printing', $batch->route_label);
        $this->assertEquals('0.00', $batch->printing_cost_per_unit_snapshot);
        $this->assertEquals('5000.00', $batch->direct_labor_cost_per_unit_snapshot);
    }

    public function test_variant_with_null_routing_blocks_new_batch_creation(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Varian Unset',
            'sku'                                  => 'SKU-UNSET-001',
            'requires_printing'                    => null, // Unconfigured
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);
        $this->attachBom($variant);

        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-103', 'order_date' => now()->toDateString(), 'status' => OrderStatus::DEAL]);
        $this->attachConfirmedDp($order);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000, 'subtotal' => 5000000]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rute produksi varian belum ditetapkan. Atur kebutuhan Printing pada Master Produk terlebih dahulu.');

        $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 100,
        ], $this->adminUser);
    }

    public function test_existing_batch_snapshot_false_remains_false_after_master_changes_to_true(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Varian Dynamic',
            'sku'                                  => 'SKU-DYN-001',
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);
        $this->attachBom($variant);

        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-104', 'order_date' => now()->toDateString(), 'status' => OrderStatus::DEAL]);
        $this->attachConfirmedDp($order);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000, 'subtotal' => 5000000]);

        $result = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ], $this->adminUser);

        $batch1 = $result['batch'];
        $this->assertFalse($batch1->requires_printing);

        // Later Admin changes master to true and sets printing cost
        $variant->update([
            'requires_printing'      => true,
            'printing_cost_per_unit' => '2500.00',
        ]);

        // Existing Batch snapshot remains FALSE
        $this->assertFalse($batch1->fresh()->requires_printing);

        // New batch uses new master TRUE
        $result2 = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ], $this->adminUser);

        $batch2 = $result2['batch'];
        $this->assertTrue($batch2->requires_printing);
    }

    public function test_existing_batch_snapshot_true_remains_true_after_master_changes_to_false(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Varian Dynamic 2',
            'sku'                                  => 'SKU-DYN-002',
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => '3000.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);
        $this->attachBom($variant);

        $order = Order::create(['customer_id' => $this->customer->id, 'order_no' => 'ORD-105', 'order_date' => now()->toDateString(), 'status' => OrderStatus::DEAL]);
        $this->attachConfirmedDp($order);
        $item = OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000, 'subtotal' => 5000000]);

        $result = $this->batchService->createBatch($order, [
            'order_item_id' => $item->id,
            'target_qty'    => 50,
        ], $this->adminUser);

        $batch1 = $result['batch'];
        $this->assertTrue($batch1->requires_printing);

        // Later Admin changes master to false
        $variant->update(['requires_printing' => false]);

        // Existing Batch snapshot remains TRUE
        $this->assertTrue($batch1->fresh()->requires_printing);
    }
}
