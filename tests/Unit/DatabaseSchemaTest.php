<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\BatchStatus;
use App\Models\Production\BatchMaterial;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Master\Material;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Production\QcResult;
use App\Models\System\Shipment;
use App\Models\Master\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_status_enum_does_not_contain_partial(): void
    {
        $cases = array_map(fn($c) => $c->value, PurchaseStatus::cases());

        $this->assertNotContains('PARTIAL', $cases);
        $this->assertNotContains('PARTIALLY_RECEIVED', $cases);
        $this->assertNotContains('PARTIAL_RECEIPT', $cases);

        $expected = ['DRAFT', 'ORDERED', 'RECEIVED', 'CANCELLED'];
        $this->assertEquals($expected, $cases);
    }

    public function test_base_selling_price_can_be_null_or_decimal_money(): void
    {
        $product = Product::create(['product_name' => 'Hijab Segi Empat', 'active' => true]);

        // Variant newly created without setting base_selling_price defaults to NULL
        $variantNull = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Raw Voal',
            'sku'          => 'SKU-RAW-001',
            'active'       => true,
        ]);

        $this->assertNull($variantNull->base_selling_price);

        // Variant with explicit base_selling_price
        $variantPriced = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Voal Cream',
            'sku'                => 'SKU-CRM-001',
            'base_selling_price' => 85000.50,
            'active'             => true,
        ]);

        $this->assertEquals(85000.50, $variantPriced->base_selling_price);
    }

    public function test_bom_items_unique_constraint_rejects_duplicates(): void
    {
        $product = Product::create(['product_name' => 'Test Product', 'active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'variant_name' => 'Red', 'sku' => 'SKU-001', 'base_selling_price' => 85000, 'active' => true]);
        $unit    = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $material = Material::create(['unit_id' => $unit->id, 'material_name' => 'Kain Test', 'min_stock' => 10, 'active' => true]);

        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $material->id,
            'qty_per_unit' => 1.5,
        ]);

        $this->expectException(QueryException::class);

        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $material->id,
            'qty_per_unit' => 2.0,
        ]);
    }

    public function test_batch_materials_unique_constraint_rejects_duplicates(): void
    {
        $customer = Customer::create(['customer_name' => 'Test Customer']);
        $order    = Order::create(['customer_id' => $customer->id, 'order_no' => 'SO-001', 'order_date' => now(), 'status' => OrderStatus::KOMUNIKASI->value]);
        $product  = Product::create(['product_name' => 'Test Product 2', 'active' => true]);
        $variant  = ProductVariant::create(['product_id' => $product->id, 'variant_name' => 'Blue', 'sku' => 'SKU-002', 'base_selling_price' => 90000, 'active' => true]);
        $item     = OrderItem::create(['order_id' => $order->id, 'variant_id' => $variant->id, 'qty_ordered' => 100, 'unit_selling_price' => 50000]);
        $batch    = ProductionBatch::create(['order_item_id' => $item->id, 'batch_no' => 'BAT-001', 'target_qty' => 100, 'status' => 'BAHAN']);
        $unit     = Unit::create(['unit_name' => 'Pcs', 'symbol' => 'pcs']);
        $material = Material::create(['unit_id' => $unit->id, 'material_name' => 'Label Test', 'min_stock' => 10, 'active' => true]);

        BatchMaterial::create([
            'batch_id'     => $batch->id,
            'material_id'  => $material->id,
            'required_qty' => 100,
        ]);

        $this->expectException(QueryException::class);

        BatchMaterial::create([
            'batch_id'     => $batch->id,
            'material_id'  => $material->id,
            'required_qty' => 50,
        ]);
    }

    public function test_base_selling_price_is_copied_to_order_item_as_transaction_snapshot(): void
    {
        $product = Product::create(['product_name' => 'Hijab Voal', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Black',
            'sku'                => 'SKU-BLK-001',
            'base_selling_price' => 85000.00,
            'active'             => true,
        ]);

        $customer = Customer::create(['customer_name' => 'Budi Shop']);
        $order    = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-TEST-001',
            'order_date'  => now(),
            'status'      => OrderStatus::KOMUNIKASI->value,
        ]);

        // Copy master price into order item
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => $variant->base_selling_price,
        ]);

        $this->assertEquals(85000.00, $orderItem->unit_selling_price);

        // Update master price
        $variant->update(['base_selling_price' => 95000.00]);

        // Order item transaction snapshot MUST remain unchanged (85000.00)
        $orderItem->refresh();
        $this->assertEquals(85000.00, $orderItem->unit_selling_price);
    }

    public function test_one_order_cannot_have_two_invoices(): void
    {
        $customer = Customer::create(['customer_name' => 'Toko Aisyah']);
        $order    = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-INV-001',
            'order_date'  => now(),
            'status'      => OrderStatus::DEAL->value,
        ]);

        Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-001',
            'invoice_date' => now(),
            'total_amount' => 500000,
            'status'       => 'UNPAID',
        ]);

        $this->expectException(QueryException::class);

        // Order yang sama dilarang punya 2 invoice (UNIQUE order_id)
        Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-002',
            'invoice_date' => now(),
            'total_amount' => 500000,
            'status'       => 'UNPAID',
        ]);
    }

    public function test_order_has_many_shipments_relationship(): void
    {
        $customer = Customer::create(['customer_name' => 'Nayyara Hijab']);
        $order    = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-SHIP-001',
            'order_date'  => now(),
            'status'      => OrderStatus::PACKING->value,
        ]);

        $shipment1 = Shipment::create([
            'order_id'      => $order->id,
            'tracking_no'   => 'JNE-1001',
            'courier'       => 'JNE',
            'shipping_cost' => 15000,
            'status'        => 'SHIPPED',
        ]);

        $shipment2 = Shipment::create([
            'order_id'      => $order->id,
            'tracking_no'   => 'JNT-2002',
            'courier'       => 'J&T',
            'shipping_cost' => 20000,
            'status'        => 'PENDING',
        ]);

        $this->assertCount(2, $order->shipments);
        $this->assertTrue($order->shipments->contains($shipment1));
        $this->assertTrue($order->shipments->contains($shipment2));
    }

    public function test_qc_results_has_no_rework_columns(): void
    {
        $qcResult = new QcResult();

        $this->assertFalse(
            array_key_exists('rework_qty', $qcResult->getAttributes()),
            'QC Result must not contain rework_qty'
        );

        $fillables = $qcResult->getFillable();
        $this->assertNotContains('rework_qty', $fillables);
        $this->assertNotContains('rework_status', $fillables);
    }

    public function test_order_status_internal_values_match_exact_specification(): void
    {
        $statuses = array_map(fn($s) => $s->value, OrderStatus::cases());

        $expected = [
            'KOMUNIKASI',
            'DEAL',
            'BAHAN',
            'PRINTING',
            'PRODUKSI',
            'QC',
            'PACKING',
            'DIKIRIM',
            'SELESAI',
        ];

        $this->assertEquals($expected, $statuses);
    }

    public function test_batch_status_exact_values(): void
    {
        $cases = array_map(fn($c) => $c->value, BatchStatus::cases());

        $expected = [
            'BAHAN',
            'PRINTING',
            'PRODUKSI',
            'QC',
            'PACKING',
            'SELESAI',
            'SELESAI_PARSIAL',
            'CANCELLED',
        ];

        $this->assertEquals($expected, $cases);
        $this->assertNotContains('PENDING', $cases);
    }
}
