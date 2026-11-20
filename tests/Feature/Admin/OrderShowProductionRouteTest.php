<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShowProductionRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->customer = Customer::create([
            'customer_name' => 'Butik Nabila',
            'phone'         => '08123456789',
            'address'       => 'Jl. Buah Batu No. 12, Bandung',
        ]);
        $this->product = Product::create(['product_name' => 'Hijab Bergo Jersey', 'active' => true]);
    }

    public function test_variant_with_requires_printing_true_displays_dengan_printing(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Motif Floral',
            'sku'               => 'HJB-PRT-001',
            'requires_printing' => true,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-TEST-PRINT-01',
            'order_date'  => '2026-08-30',
            'status'      => OrderStatus::DEAL,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 85000,
            'subtotal'           => 4250000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Rute Produksi');
        $response->assertSee('Dengan Printing');
        $response->assertDontSee('Tanpa Printing');
        $response->assertDontSee('Belum Ditetapkan');
    }

    public function test_variant_with_requires_printing_false_displays_tanpa_printing(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Hitam Polos',
            'sku'               => 'HJB-PLN-001',
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-TEST-PLAIN-01',
            'order_date'  => '2026-08-30',
            'status'      => OrderStatus::DEAL,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
            'subtotal'           => 3750000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Rute Produksi');
        $response->assertSee('Tanpa Printing');
        $response->assertDontSee('Dengan Printing');
        $response->assertDontSee('Belum Ditetapkan');
    }

    public function test_variant_with_requires_printing_null_displays_belum_ditetapkan_and_warning(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Unconfigured Variant',
            'sku'               => 'HJB-NULL-001',
            'requires_printing' => null,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-TEST-NULL-01',
            'order_date'  => '2026-08-30',
            'status'      => OrderStatus::DEAL,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
            'subtotal'           => 3750000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Rute Produksi');
        $response->assertSee('Belum Ditetapkan');
        $response->assertSee('Rute produksi varian belum ditetapkan. Lengkapi kebutuhan Printing pada Master Produk sebelum membuat Batch.');
    }

    public function test_viewing_order_detail_does_not_mutate_existing_batch_snapshots(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Hitam Polos',
            'sku'               => 'HJB-PLN-002',
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-TEST-SNAP-01',
            'order_date'  => '2026-08-30',
            'status'      => OrderStatus::BAHAN,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
            'subtotal'           => 3750000,
        ]);

        // Batch created with snapshot requires_printing = false
        $batch = ProductionBatch::create([
            'order_item_id'     => $item->id,
            'batch_no'          => 'BAT-SNAP-001',
            'target_qty'        => 50,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);

        // Master variant updated later to requires_printing = true
        $variant->update(['requires_printing' => true]);

        // Viewing order detail
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $response->assertOk();

        // Batch snapshot in DB must remain unchanged (false)
        $this->assertFalse($batch->fresh()->requires_printing);
    }
}
