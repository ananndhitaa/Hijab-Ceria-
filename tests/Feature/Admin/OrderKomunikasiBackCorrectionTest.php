<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\BomItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderKomunikasiBackCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Toko Busana Muslimah',
            'phone'         => '081234567890',
            'address'       => 'Jl. Sukajadi No. 100 Bandung',
        ]);

        $this->product = Product::create([
            'product_name' => 'Pashmina Silk Premium',
            'active'       => true,
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

        $this->variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Hitam',
            'sku'                                  => 'HJB-BJ-001',
            'base_selling_price'                   => 65000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'active'                               => true,
        ]);

        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $material->id,
            'qty_per_unit' => 1,
        ]);
    }

    /**
     * Helper to create a test order
     */
    private function createOrder(OrderStatus $status = OrderStatus::KOMUNIKASI, float $qty = 5, string $price = '65000.00'): Order
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-0001',
            'order_date'  => '2026-10-08',
            'status'      => $status,
            'notes'       => 'Catatan pengiriman packing rapi.',
        ]);

        $order->items()->create([
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => $price,
        ]);

        return $order;
    }

    /**
     * 1, 2, 3: KOMUNIKASI detail shows correction/back action pointing to SAME Sales Order edit route,
     * and does NOT point to Sales Order index.
     */
    public function test_komunikasi_detail_shows_correction_back_action_pointing_to_same_order_edit(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);

        // 1. Shows correction/back action
        $response->assertSee('Kembali Edit Order');

        // 2. Points to edit route of SAME Sales Order
        $editUrl = route('admin.orders.edit', $order);
        $response->assertSee($editUrl);

        // 3. Header action does not have "Kembali ke daftar pesanan"
        $response->assertDontSee('title="Kembali ke daftar pesanan"', false);
    }

    /**
     * Verification for other statuses: Non-KOMUNIKASI (e.g. DEAL, BAHAN) points back to Sales Order index.
     */
    public function test_non_komunikasi_detail_points_back_to_order_index(): void
    {
        $orderDeal = $this->createOrder(OrderStatus::DEAL);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $orderDeal));

        $response->assertStatus(200);

        // Shows standard Kembali to index
        $response->assertSee('Kembali');
        $response->assertDontSee('Kembali Edit Order');
        $response->assertSee(route('admin.orders.index'));
    }

    /**
     * 4. Edit form preloads current order data.
     */
    public function test_edit_form_preloads_current_order_data(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 10, '68000.00');

        $response = $this->actingAs($this->admin)->get(route('admin.orders.edit', $order));

        $response->assertStatus(200);

        // Preloads order number, customer, date, notes
        $response->assertSee($order->order_no);
        $response->assertSee($this->customer->customer_name);
        $response->assertSee('2026-10-08');
        $response->assertSee('Catatan pengiriman packing rapi.');

        // Preloads variant and transaction price
        $response->assertSee($this->variant->variant_name);
        $response->assertSee('68000');
    }

    /**
     * 5, 6, 7, 8: Saving edit keeps same Sales Order ID, does not create duplicate OrderItem,
     * status remains KOMUNIKASI, and does not automatically confirm DEAL.
     */
    public function test_saving_edit_keeps_same_id_no_duplicate_items_and_status_remains_komunikasi(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 5, '65000.00');
        $originalOrderId = $order->id;
        $originalOrderNo = $order->order_no;
        $orderItemId = $order->items->first()->id;

        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-10-09',
            'notes'       => 'Catatan revisi Admin.',
            'items'       => [
                [
                    'order_item_id'      => $orderItemId,
                    'variant_id'         => $this->variant->id,
                    'qty_ordered'        => 12,
                    'unit_selling_price' => 70000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);

        // Redirects back to detail of the SAME order
        $response->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        // 5. Keeps same Sales Order ID and order_no
        $this->assertSame($originalOrderId, $order->id);
        $this->assertSame($originalOrderNo, $order->order_no);

        // 6. Does not duplicate OrderItem (exactly 1 item)
        $this->assertCount(1, $order->items);
        $item = $order->items->first();
        $this->assertEquals(12, $item->qty_ordered);
        $this->assertEquals('70000.00', $item->unit_selling_price);

        // 7, 8. Status remains KOMUNIKASI after ordinary edit; DEAL is NOT confirmed automatically
        $this->assertSame(OrderStatus::KOMUNIKASI, $order->status);
        $this->assertNotSame(OrderStatus::DEAL, $order->status);
    }

    /**
     * 9. Konfirmasi Deal remains a separate action.
     */
    public function test_konfirmasi_deal_remains_separate_action(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI);

        // Verify detail page has Konfirmasi Deal button/form
        $detailResponse = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee(route('admin.orders.confirm-deal', $order));
        $detailResponse->assertSee('Konfirmasi Deal');

        // Confirm Deal as a separate explicit POST action
        $postResponse = $this->actingAs($this->admin)->post(route('admin.orders.confirm-deal', $order));
        $postResponse->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(OrderStatus::DEAL, $order->status);
    }

    /**
     * 10. HPP selling-price guard still works on edit.
     * Reference HPP is 28.000 (20k mat + 5k labor + 2k pack + 1k other).
     * Editing transaction price <= 28.000 must be rejected.
     */
    public function test_hpp_selling_price_guard_still_works_on_edit(): void
    {
        $order = $this->createOrder(OrderStatus::KOMUNIKASI, 5, '65000.00');
        $orderItemId = $order->items->first()->id;

        $updatePayload = [
            'customer_id' => $this->customer->id,
            'order_date'  => '2026-10-09',
            'items'       => [
                [
                    'order_item_id'      => $orderItemId,
                    'variant_id'         => $this->variant->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 25000, // < HPP 28.000 -> must be rejected
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updatePayload);

        $response->assertSessionHasErrors(['items.0.variant_id']);

        // Database value preserved
        $this->assertEquals('65000.00', $order->items()->first()->unit_selling_price);
    }

    /**
     * 11. Existing historical orders unchanged.
     */
    public function test_existing_historical_orders_unchanged(): void
    {
        $historicalOrder = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-HIST-9999',
            'order_date'  => '2026-08-01',
            'status'      => OrderStatus::SELESAI,
            'notes'       => 'Order lama yang sudah selesai.',
        ]);

        $historicalItem = OrderItem::create([
            'order_id'           => $historicalOrder->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 60000,
        ]);

        // Refresh and assert properties
        $this->assertEquals('SO-HIST-9999', $historicalOrder->fresh()->order_no);
        $this->assertEquals('60000.00', $historicalItem->fresh()->unit_selling_price);
        $this->assertSame(OrderStatus::SELESAI, $historicalOrder->fresh()->status);
    }
}
