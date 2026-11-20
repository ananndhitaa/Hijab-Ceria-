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
use App\Services\Finance\OrderProfitabilityService;
use App\Services\Finance\VariantCostCalculationService;
use App\Support\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderUiSimplificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductVariant $variantWithBom;
    private ProductVariant $variantNoBom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Partner Test',
            'phone'         => '081234567890',
            'address'       => 'Jl. Buah Batu Bandung',
        ]);

        $unitM = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $mat = Material::create([
            'material_name' => 'Kain Voal',
            'unit_id'       => $unitM->id,
            'cost_per_unit' => 30000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $mat->id,
            'qty_on_hand'   => 100,
            'avg_unit_cost' => 30000,
        ]);

        $prod = Product::create([
            'product_name' => 'Pashmina Silk',
            'code'         => 'PS',
            'active'       => true,
        ]);

        // Variant with BOM: Reference HPP = 1.5 * 30.000 + 5.000 = 50.000
        $this->variantWithBom = ProductVariant::create([
            'product_id'                           => $prod->id,
            'variant_name'                         => 'Silk Rose Gold',
            'sku'                                  => 'HJB-PS-RG-001',
            'base_selling_price'                   => 75000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 0,
            'other_direct_cost_per_unit'           => 0,
            'active'                               => true,
        ]);
        BomItem::create([
            'variant_id'   => $this->variantWithBom->id,
            'material_id'  => $mat->id,
            'qty_per_unit' => 1.5,
        ]);

        // Variant without BOM
        $this->variantNoBom = ProductVariant::create([
            'product_id'         => $prod->id,
            'variant_name'       => 'Silk Dusty Pink',
            'sku'                => 'HJB-PS-DP-002',
            'base_selling_price' => 75000,
            'requires_printing'  => false,
            'active'             => true,
        ]);
    }

    /**
     * 1. Create Order no longer displays HPP/Margin.
     */
    public function test_1_create_order_no_longer_displays_hpp_or_margin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.create'));

        $response->assertOk();
        $response->assertSee('Varian Produk');
        $response->assertSee('Qty (pcs)');
        $response->assertSee('Harga Jual');
        $response->assertSee('Subtotal');

        // Financial analysis / reference row elements removed
        $response->assertDontSee('HPP Acuan:');
        $response->assertDontSee('Harga Master:');
        $response->assertDontSee('Margin:');
        $response->assertDontSee('/ pcs');
    }

    /**
     * 2. Edit Order no longer displays HPP/Margin.
     */
    public function test_2_edit_order_no_longer_displays_hpp_or_margin(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-202610-EDIT-SIMPLIFY',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::KOMUNIKASI,
            'total_qty'    => 10,
            'total_amount' => 750000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantWithBom->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 75000,
            'subtotal'           => 750000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.edit', $order));

        $response->assertOk();
        $response->assertSee('Varian Produk');
        $response->assertSee('Qty (pcs)');
        $response->assertSee('Harga Jual');
        $response->assertSee('Subtotal');

        // Financial analysis / reference row elements removed
        $response->assertDontSee('HPP Acuan:');
        $response->assertDontSee('Harga Master:');
        $response->assertDontSee('Margin:');
        $response->assertDontSee('/ pcs');
    }

    /**
     * 3. Detail Order no longer displays HPP/Margin secondary text in item table.
     */
    public function test_3_detail_order_no_longer_displays_hpp_or_margin(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-202610-SHOW-SIMPLIFY',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::KOMUNIKASI,
            'total_qty'    => 10,
            'total_amount' => 750000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantWithBom->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 75000,
            'subtotal'           => 750000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Produk &amp; Varian', false);
        $response->assertSee('SKU');
        $response->assertSee('Rute Produksi');
        $response->assertSee('Qty');
        $response->assertSee('Harga Jual');
        $response->assertSee('Subtotal');

        // Secondary text under unit price removed
        $response->assertDontSee('HPP Acuan');
        $response->assertDontSee('Margin');
    }

    /**
     * 4. Profitability summary removed from Sales Order UI.
     */
    public function test_4_profitability_summary_removed_from_sales_order_ui(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-202610-SHOW-NO-PROF',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::KOMUNIKASI,
            'total_qty'    => 20,
            'total_amount' => 1500000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantWithBom->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 75000,
            'subtotal'           => 1500000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('RINGKASAN NILAI PESANAN');
        $response->assertDontSee('Estimasi Sebelum Produksi');
        $response->assertDontSee('Acuan Variant');
        $response->assertDontSee('Estimasi HPP');
        $response->assertDontSee('Estimasi Laba');
        $response->assertDontSee('REALISASI PRODUKSI');
        $response->assertDontSee('HPP Aktual');
        $response->assertDontSee('Laba Aktual');
        $response->assertDontSee('Selisih HPP');
    }

    /**
     * 5. Total order still correct on UI.
     */
    public function test_5_total_order_still_correct(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-202610-TOTAL-VERIFY',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::KOMUNIKASI,
            'total_qty'    => 15,
            'total_amount' => 1125000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantWithBom->id,
            'qty_ordered'        => 15,
            'unit_selling_price' => 75000,
            'subtotal'           => 1125000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Total Keseluruhan');
        $response->assertSee(MoneyFormatter::rupiah($order->total_amount));
        $response->assertSee('15 pcs');
    }

    /**
     * 6. Price > HPP accepted.
     * HPP = 50.000, Price = 70.000 -> accepted
     */
    public function test_6_price_greater_than_hpp_accepted(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'items'       => [
                [
                    'variant_id'         => $this->variantWithBom->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 70000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);

        $response->assertRedirect();
        $order = Order::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(10, $order->total_qty);
        $this->assertEquals(700000, (float) $order->total_amount);
    }

    /**
     * 7. Price <= HPP still rejected server-side.
     * HPP = 50.000, Price = 50.000 -> rejected
     * HPP = 50.000, Price = 45.000 -> rejected
     */
    public function test_7_price_less_than_or_equal_to_hpp_still_rejected_server_side(): void
    {
        // Price equal to HPP (50.000)
        $payloadEqual = [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'items'       => [
                [
                    'variant_id'         => $this->variantWithBom->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 50000,
                ],
            ],
        ];
        $resEqual = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payloadEqual);
        $resEqual->assertSessionHasErrors(['items.0.variant_id']);

        // Price lower than HPP (45.000)
        $payloadLower = [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'items'       => [
                [
                    'variant_id'         => $this->variantWithBom->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 45000,
                ],
            ],
        ];
        $resLower = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payloadLower);
        $resLower->assertSessionHasErrors(['items.0.variant_id']);
    }

    /**
     * 8. HPP unavailable still blocks new order.
     */
    public function test_8_hpp_unavailable_still_blocks_new_order(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'items'       => [
                [
                    'variant_id'         => $this->variantNoBom->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 75000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $payload);
        $response->assertSessionHasErrors(['items.0.variant_id']);
    }

    /**
     * 9. Variant HPP page unchanged.
     */
    public function test_9_variant_hpp_page_unchanged(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.variants.show', $this->variantWithBom));

        $response->assertOk();
        // HPP Acuan and related cost details remain visible on Product Variant page
        $response->assertSee('Harga Jual Master');
        $response->assertSee('Analisis HPP');
    }

    /**
     * 10. Finance / profitability services unchanged.
     */
    public function test_10_finance_profitability_services_unchanged(): void
    {
        $costService = app(VariantCostCalculationService::class);
        $refCost = $costService->getReferenceCost($this->variantWithBom);
        $this->assertTrue($refCost['has_reference']);
        $this->assertEquals('50000.00', $refCost['reference_cost']);

        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-202610-SVC-TEST',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::KOMUNIKASI,
            'total_qty'    => 10,
            'total_amount' => 700000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantWithBom->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 70000,
            'subtotal'           => 700000,
        ]);

        $profitService = app(OrderProfitabilityService::class);
        $profit = $profitService->calculateOrderProfitability($order);

        $this->assertNotNull($profit);
        $this->assertEquals('700000.00', $profit['revenue_amount']);
    }

    /**
     * 11. Historical data unchanged.
     */
    public function test_11_historical_data_unchanged(): void
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'SO-202610-HISTORICAL-IMMUTABLE',
            'order_date'   => '2026-01-15',
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 100,
            'total_amount' => 7200000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantWithBom->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 72000,
            'subtotal'           => 7200000,
        ]);

        $order->refresh();
        $this->assertEquals('7200000.00', $order->total_amount);
        $this->assertEquals(100, $order->total_qty);
        $this->assertEquals('72000.00', $item->unit_selling_price);
        $this->assertEquals('7200000.00', $item->subtotal);
    }
}
