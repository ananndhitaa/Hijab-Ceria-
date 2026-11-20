<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Unit;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderTrackingToken;
use App\Models\Master\Product;
use App\Models\Production\ProductionBatch;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\SalesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Product $product;
    private ProductVariant $variant1;
    private ProductVariant $variant2;
    private ProductVariant $variantNullPrice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->customer = Customer::create([
            'customer_name' => 'Toko Busana Muslimah',
            'phone'         => '081234567890',
            'address'       => 'Jl. Sukajadi No. 100 Bandung',
        ]);

        $this->product = Product::create([
            'product_name' => 'Pashmina Silk Premium',
            'active'       => true,
        ]);

        $this->variant1 = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Hitam',
            'sku'                                  => 'HJB-BJ-001',
            'base_selling_price'                   => 65000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
        ]);

        $this->variant2 = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Dusty Pink',
            'sku'                                  => 'SKU-SILK-DP',
            'base_selling_price'                   => 75000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
        ]);

        $this->variantNullPrice = ProductVariant::create([
            'product_id'         => $this->product->id,
            'variant_name'       => 'Custom Color',
            'sku'                => 'SKU-SILK-CC',
            'base_selling_price' => null, // Explicit null master price
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $material = Material::create([
            'material_name' => 'Kain Sutra Silk',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 20000,
        ]);
        BomItem::create(['variant_id' => $this->variant1->id, 'material_id' => $material->id, 'qty_per_unit' => 1]);
        BomItem::create(['variant_id' => $this->variant2->id, 'material_id' => $material->id, 'qty_per_unit' => 1]);
    }

    public function test_admin_can_view_sales_order_list(): void
    {
        Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('SO-202608-0001');
        $response->assertSee('Toko Busana Muslimah');
    }

    public function test_admin_can_filter_sales_orders_by_search_and_status(): void
    {
        $order1 = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $otherCustomer = Customer::create(['customer_name' => 'Toko Cantik']);
        $order2 = Order::create([
            'customer_id' => $otherCustomer->id,
            'order_no'    => 'SO-202608-0002',
            'order_date'  => '2026-08-16',
            'status'      => OrderStatus::DEAL,
        ]);

        // Filter by status DEAL
        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'DEAL']));
        $response->assertStatus(200);
        $response->assertSee('SO-202608-0002');
        $response->assertDontSee('SO-202608-0001');

        // Filter by customer name search
        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['search' => 'Cantik']));
        $response->assertStatus(200);
        $response->assertSee('SO-202608-0002');
        $response->assertDontSee('SO-202608-0001');
    }

    public function test_admin_can_open_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.create'));

        $response->assertStatus(200);
        $response->assertSee('Buat Sales Order Baru');
        $response->assertSee('Toko Busana Muslimah');
    }

    public function test_master_price_automatically_copied_to_order_item_snapshot(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'notes'       => 'Kirim via kurir langganan.',
            'items'       => [
                [
                    'variant_id'  => $this->variant1->id,
                    'qty_ordered' => 20,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $order = Order::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('raw_tracking_url');

        $this->assertEquals($this->customer->id, $order->customer_id);
        $this->assertStringStartsWith('SO-', $order->order_no);
        $this->assertEquals(OrderStatus::KOMUNIKASI, $order->status);
        $this->assertEquals(1, $order->items()->count());

        $item = $order->items->first();
        $this->assertEquals('65000.00', $item->unit_selling_price);
        $this->assertEquals(1300000, $order->total_amount);
    }

    public function test_crafted_submitted_price_below_hpp_is_rejected_server_side(): void
    {
        // Client maliciously submits 1000 which is below Reference HPP 20000
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                [
                    'variant_id'         => $this->variant1->id,
                    'qty_ordered'        => 20,
                    'unit_selling_price' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors(['items.0.variant_id']);
        $this->assertDatabaseMissing('orders', ['customer_id' => $this->customer->id]);
    }

    public function test_transaction_price_can_differ_from_master_price_as_long_as_above_hpp(): void
    {
        // Master price is 65000, HPP is 20000. Custom transaction price 60000 (> HPP) is valid
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                [
                    'variant_id'         => $this->variant1->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 60000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertNotNull($order);
        $item = $order->items->first();
        $this->assertEquals('60000.00', $item->unit_selling_price);
        $this->assertEquals(600000, $order->total_amount);
    }

    public function test_admin_can_create_order_with_multiple_items_using_master_prices(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                [
                    'variant_id'  => $this->variant1->id,
                    'qty_ordered' => 20, // 20 * 65000 = 1,300,000
                ],
                [
                    'variant_id'  => $this->variant2->id,
                    'qty_ordered' => 30, // 30 * 75000 = 2,250,000
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $order = Order::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('admin.orders.show', $order));

        $this->assertEquals(2, $order->items()->count());
        $this->assertEquals(3550000, $order->total_amount);
        $this->assertEquals(50, $order->total_qty);
    }

    public function test_order_creation_fails_when_customer_or_items_missing(): void
    {
        $payload = [
            'order_date' => '2026-08-15',
            'items'      => [],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors(['customer_id', 'items']);
        $this->assertEquals(0, Order::count());
    }

    public function test_order_creation_fails_with_duplicate_variants(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                ['variant_id' => $this->variant1->id, 'qty_ordered' => 10],
                ['variant_id' => $this->variant1->id, 'qty_ordered' => 5],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors('items.0.variant_id');
        $this->assertEquals(0, Order::count());
    }

    public function test_order_creation_fails_when_qty_ordered_is_zero_or_negative(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                ['variant_id' => $this->variant1->id, 'qty_ordered' => 0],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors('items.0.qty_ordered');
        $this->assertEquals(0, Order::count());
    }

    public function test_order_creation_fails_when_parent_product_is_inactive(): void
    {
        $inactiveProduct = Product::create(['product_name' => 'Produk Nonaktif', 'active' => false]);
        $inactiveVariant = ProductVariant::create([
            'product_id'         => $inactiveProduct->id,
            'variant_name'       => 'Varian Nonaktif',
            'sku'                => 'SKU-INACTIVE-01',
            'base_selling_price' => 50000,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                ['variant_id' => $inactiveVariant->id, 'qty_ordered' => 10],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors('items.0.variant_id');
        $this->assertEquals(0, Order::count());
    }

    public function test_unpriced_variant_is_rejected_from_sales_order_creation(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                ['variant_id' => $this->variantNullPrice->id, 'qty_ordered' => 15],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors('items.0.variant_id');
        $this->assertEquals(0, Order::count());
    }

    public function test_historical_order_item_price_unaffected_by_subsequent_master_price_change(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                ['variant_id' => $this->variant1->id, 'qty_ordered' => 10],
            ],
        ];

        $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $order = Order::first();
        $this->assertEquals(65000, $order->items->first()->unit_selling_price);

        // Master price changed to 70000
        $this->variant1->update(['base_selling_price' => 70000]);

        // Historical order item must remain 65000
        $order->refresh();
        $this->assertEquals(65000, $order->items->first()->unit_selling_price);
    }

    public function test_new_order_after_master_price_change_uses_new_master_price(): void
    {
        // 1st order when price is 65000
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [['variant_id' => $this->variant1->id, 'qty_ordered' => 10]],
        ]);
        $order1 = Order::first();
        $this->assertEquals(65000, $order1->items->first()->unit_selling_price);

        // Master price updated to 70000
        $this->variant1->update(['base_selling_price' => 70000]);

        // 2nd order created after price update
        $this->actingAs($this->admin)->post(route('admin.orders.store'), [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [['variant_id' => $this->variant1->id, 'qty_ordered' => 10]],
        ]);
        $order2 = Order::where('id', '!=', $order1->id)->first();
        $this->assertEquals(70000, $order2->items->first()->unit_selling_price);

        // Ensure order1 still 65000
        $order1->refresh();
        $this->assertEquals(65000, $order1->items->first()->unit_selling_price);
    }

    public function test_editing_existing_order_without_changing_variant_preserves_historical_snapshot(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $item = $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        // Master price is later updated to 70000
        $this->variant1->update(['base_selling_price' => 70000]);

        // Admin edits Qty and notes
        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'notes'       => 'Updated notes.',
            'items'       => [
                [
                    'order_item_id' => $item->id,
                    'variant_id'    => $this->variant1->id, // Same variant
                    'qty_ordered'   => 15, // Changed qty
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);
        $response->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertEquals(1, $order->items()->count());
        $this->assertEquals(15, $order->items->first()->qty_ordered);
        // Preserved historical snapshot 65000, NOT new master price 70000!
        $this->assertEquals(65000, $order->items->first()->unit_selling_price);
        $this->assertEquals(975000, $order->total_amount);
    }

    public function test_editing_existing_order_preserves_snapshot_even_if_master_price_became_null(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $item = $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        // Master price becomes null
        $this->variant1->update(['base_selling_price' => null]);

        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [
                [
                    'order_item_id' => $item->id,
                    'variant_id'    => $this->variant1->id, // Unchanged variant
                    'qty_ordered'   => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);
        $response->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertEquals(65000, $order->items->first()->unit_selling_price);
        $this->assertEquals(650000, $order->total_amount);
    }

    public function test_changing_variant_on_edit_applies_new_variant_master_price(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $item = $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        // Admin changes variant to variant2 (master price 75000)
        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [
                [
                    'order_item_id' => $item->id,
                    'variant_id'    => $this->variant2->id, // Changed to variant2
                    'qty_ordered'   => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);
        $response->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertEquals($this->variant2->id, $order->items->first()->variant_id);
        $this->assertEquals(75000, $order->items->first()->unit_selling_price);
        $this->assertEquals(750000, $order->total_amount);
    }

    public function test_new_item_added_on_edit_uses_current_master_price(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $item1 = $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        // Add a second item without order_item_id
        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [
                [
                    'order_item_id' => $item1->id,
                    'variant_id'    => $this->variant1->id,
                    'qty_ordered'   => 5,
                ],
                [
                    'order_item_id' => null, // Newly added item
                    'variant_id'    => $this->variant2->id, // Master 75000
                    'qty_ordered'   => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);
        $response->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertEquals(2, $order->items()->count());
        $items = $order->items->keyBy('variant_id');
        $this->assertEquals(65000, $items[$this->variant1->id]->unit_selling_price);
        $this->assertEquals(75000, $items[$this->variant2->id]->unit_selling_price);
    }

    public function test_cross_order_item_id_injection_is_rejected(): void
    {
        $orderA = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $itemA = $orderA->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        $orderB = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0002',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $itemB = $orderB->items()->create([
            'variant_id'         => $this->variant2->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 75000,
        ]);

        // Attempt to update Order A using itemB's ID from Order B
        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [
                [
                    'order_item_id' => $itemB->id, // Malicious cross-order injection
                    'variant_id'    => $this->variant1->id,
                    'qty_ordered'   => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $orderA), $updatePayload);
        $response->assertSessionHasErrors('items.0.order_item_id');
    }

    public function test_duplicate_order_item_ids_in_update_request_is_rejected(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $item = $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [
                ['order_item_id' => $item->id, 'variant_id' => $this->variant1->id, 'qty_ordered' => 5],
                ['order_item_id' => $item->id, 'variant_id' => $this->variant2->id, 'qty_ordered' => 10], // Duplicate ID
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);
        $response->assertSessionHasErrors('items');
    }

    public function test_invoice_creation_uses_order_item_transaction_snapshot(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::DEAL,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 65000, // Snapshot
        ]);

        // Master price later changes
        $this->variant1->update(['base_selling_price' => 90000]);

        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->createForOrder($order);

        // Invoice total must be 20 * 65000 = 1300000.00
        $this->assertEquals('1300000.00', $invoice->total_amount);
    }

    public function test_sales_report_uses_order_item_transaction_snapshot(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::DEAL,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 65000, // Snapshot
        ]);

        // Master price later changes
        $this->variant1->update(['base_selling_price' => 90000]);

        $salesReportService = app(SalesReportService::class);
        $report = $salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('1300000.00', $report['totalRevenue']);
    }

    public function test_admin_can_confirm_deal_transition(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.orders.confirm-deal', $order),
            ['notes' => 'Pembeli telah transfer DP.']
        );

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(OrderStatus::DEAL, $order->status);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id'           => $order->id,
            'status'             => 'DEAL',
            'changed_by_user_id' => $this->admin->id,
            'notes'              => 'Pembeli telah transfer DP.',
        ]);
    }

    public function test_repeated_confirm_deal_is_rejected(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::DEAL, // Already DEAL
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.confirm-deal', $order));

        $response->assertSessionHas('error');
        $order->refresh();
        $this->assertEquals(OrderStatus::DEAL, $order->status);
    }

    public function test_sales_order_module_cannot_advance_status_to_bahan(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::DEAL,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/orders/{$order->id}/advance-status");
        $response->assertStatus(404);
    }

    public function test_order_edit_is_rejected_when_status_is_bahan_or_later(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::BAHAN,
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.edit', $order));
        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error');

        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [
                ['variant_id' => $this->variant1->id, 'qty_ordered' => 10],
            ],
        ];

        $putResponse = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);
        $putResponse->assertSessionHasErrors('order');
    }

    public function test_order_edit_preserves_immutable_order_no(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        $updatePayload = [
            'order_no'    => 'SO-HACKED-9999',
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [
                ['variant_id' => $this->variant1->id, 'qty_ordered' => 10],
            ],
        ];

        $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);

        $order->refresh();
        $this->assertEquals('SO-202608-0001', $order->order_no);
    }

    public function test_order_creation_fails_when_total_quantity_is_less_than_10(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                ['variant_id' => $this->variant1->id, 'qty_ordered' => 5], // 5 < 10 MOQ
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors('items');
        $this->assertEquals(0, Order::count());
    }

    public function test_order_creation_succeeds_when_total_quantity_is_at_least_10(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-15',
            'items'       => [
                ['variant_id' => $this->variant1->id, 'qty_ordered' => 6],
                ['variant_id' => $this->variant2->id, 'qty_ordered' => 4], // Total = 10 pcs
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, Order::count());
        $order = Order::first();
        $this->assertEquals(10, $order->items->sum('qty_ordered'));
    }

    public function test_order_edit_is_rejected_if_downstream_production_batch_exists(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::DEAL,
        ]);

        $orderItem = $order->items()->create([
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);

        ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-202608-0001',
            'target_qty'    => 5,
            'status'        => BatchStatus::BAHAN,
        ]);

        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-08-16',
            'items'       => [
                ['variant_id' => $this->variant2->id, 'qty_ordered' => 10],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);
        $response->assertSessionHasErrors('order');

        $order->refresh();
        $this->assertEquals(1, $order->items()->count());
        $this->assertEquals($this->variant1->id, $order->items->first()->variant_id);
    }

    public function test_non_admin_cannot_access_or_write_sales_orders(): void
    {
        $gudang = User::factory()->create(['role' => UserRole::GUDANG]);
        $produksi = User::factory()->create(['role' => UserRole::PRODUKSI]);
        $customerUser = User::factory()->create(['role' => UserRole::CUSTOMER]);

        $this->actingAs($gudang)->get(route('admin.orders.index'))->assertStatus(403);
        $this->actingAs($gudang)->post(route('admin.orders.store'), [])->assertStatus(403);

        $this->actingAs($produksi)->get(route('admin.orders.index'))->assertStatus(403);
        $this->actingAs($produksi)->post(route('admin.orders.store'), [])->assertStatus(403);

        $this->actingAs($customerUser)->get(route('admin.orders.index'))->assertStatus(403);
        $this->actingAs($customerUser)->post(route('admin.orders.store'), [])->assertStatus(403);
    }

    public function test_sales_order_destroy_route_does_not_exist(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202608-0001',
            'order_date'  => '2026-08-15',
            'status'      => OrderStatus::KOMUNIKASI,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/orders/{$order->id}");

        $response->assertStatus(405);
    }
}
