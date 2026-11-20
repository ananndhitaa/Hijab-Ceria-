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
use App\Services\Finance\VariantCostCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreUatHppPriceRuleAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Unit $meter;
    private Material $kain;
    private Product $product;
    private Customer $customer;
    private VariantCostCalculationService $costService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->costService = app(VariantCostCalculationService::class);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Toko NAYARA Muslimah',
            'phone'         => '081234567890',
            'address'       => 'Jl. Riau No. 10 Bandung',
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        // Material with unit cost 30.000
        $this->kain = Material::create([
            'material_name' => 'Kain Premium Silk',
            'unit_id'       => $this->meter->id,
            'cost_per_unit' => 30000,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 30000,
        ]);

        $this->product = Product::create([
            'product_name' => 'Pashmina Silk Exclusive',
            'active'       => true,
        ]);
    }

    /**
     * Helper to create a variant with exact 42.000 HPP
     * Material: 1m * 30.000 = 30.000
     * Direct Labor: 7.000
     * Packaging: 3.000
     * Other Direct: 2.000
     * Total HPP = 42.000
     */
    private function createVariantWithHpp42k(string $name = 'Silk Navy', string $sku = 'SKU-42K', ?float $price = 69000): ProductVariant
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => $name,
            'sku'                                  => $sku,
            'base_selling_price'                   => $price,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 7000,
            'packaging_process_cost_per_good_unit' => 3000,
            'other_direct_cost_per_unit'           => 2000,
            'active'                               => true,
        ]);

        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $this->kain->id,
            'qty_per_unit' => 1,
        ]);

        return $variant;
    }

    // ==========================================
    // 1-5: EXISTING VARIANT PRICE STATUS AUDIT
    // ==========================================

    /**
     * 1. Existing price > HPP -> VALID
     */
    public function test_1_existing_price_greater_than_hpp_is_valid(): void
    {
        $variant = $this->createVariantWithHpp42k('Navy Valid', 'SKU-VAL-1', 69000);

        $eval = $this->costService->evaluatePriceMargin($variant, (string) $variant->base_selling_price);

        $this->assertSame('VALID', $eval['price_status']);
        $this->assertSame('Harga jual di atas HPP', $eval['status_message']);
        $this->assertTrue($eval['is_valid']);
        $this->assertFalse($eval['needs_revision']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.variants.show', $variant));
        $response->assertStatus(200);
        $response->assertSee('VALID');
        $response->assertSee('Harga jual di atas HPP');
    }

    /**
     * 2. Existing price == HPP -> PERLU REVISI
     */
    public function test_2_existing_price_equal_to_hpp_needs_revision(): void
    {
        $variant = $this->createVariantWithHpp42k('Navy Equal', 'SKU-EQ-2', 42000);

        $eval = $this->costService->evaluatePriceMargin($variant, (string) $variant->base_selling_price);

        $this->assertSame('PERLU REVISI', $eval['price_status']);
        $this->assertStringContainsString('Harga jual saat ini sama dengan atau lebih rendah dari HPP', $eval['status_message']);
        $this->assertFalse($eval['is_valid']);
        $this->assertTrue($eval['needs_revision']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.variants.show', $variant));
        $response->assertStatus(200);
        $response->assertSee('PERLU REVISI');
        $response->assertSee('Perbarui harga jual sebelum Variant digunakan untuk order baru');
    }

    /**
     * 3. Existing price < HPP -> PERLU REVISI
     */
    public function test_3_existing_price_less_than_hpp_needs_revision(): void
    {
        $variant = $this->createVariantWithHpp42k('Navy Low', 'SKU-LOW-3', 38000);

        $eval = $this->costService->evaluatePriceMargin($variant, (string) $variant->base_selling_price);

        $this->assertSame('PERLU REVISI', $eval['price_status']);
        $this->assertStringContainsString('Harga jual saat ini sama dengan atau lebih rendah dari HPP', $eval['status_message']);
        $this->assertFalse($eval['is_valid']);
        $this->assertTrue($eval['needs_revision']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.variants.show', $variant));
        $response->assertStatus(200);
        $response->assertSee('PERLU REVISI');
        $response->assertSee('Perbarui harga jual sebelum Variant digunakan untuk order baru');
    }

    /**
     * 4. HPP unavailable -> BELUM DAPAT DIVERIFIKASI
     */
    public function test_4_hpp_unavailable_is_unverified(): void
    {
        // Variant without direct costs and no BOM
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Incomplete Variant',
            'sku'                                  => 'SKU-INC-4',
            'base_selling_price'                   => 50000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => null,
            'direct_labor_cost_per_unit'           => null,
            'packaging_process_cost_per_good_unit' => null,
            'other_direct_cost_per_unit'           => null,
            'active'                               => true,
        ]);

        $eval = $this->costService->evaluatePriceMargin($variant, (string) $variant->base_selling_price);

        $this->assertSame('BELUM DAPAT DIVERIFIKASI', $eval['price_status']);
        $this->assertSame('HPP belum tersedia sehingga harga jual belum dapat diverifikasi.', $eval['status_message']);
        $this->assertFalse($eval['is_valid']);
        $this->assertFalse($eval['needs_revision']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.variants.show', $variant));
        $response->assertStatus(200);
        $response->assertSee('BELUM DAPAT DIVERIFIKASI');
        $response->assertSee('HPP belum tersedia sehingga harga jual belum dapat diverifikasi.');
    }

    /**
     * 5. Price null -> HARGA BELUM DITETAPKAN
     */
    public function test_5_price_null_is_not_set(): void
    {
        $variant = $this->createVariantWithHpp42k('Navy Unpriced', 'SKU-NULL-5', null);

        $eval = $this->costService->evaluatePriceMargin($variant, null);

        $this->assertSame('HARGA BELUM DITETAPKAN', $eval['price_status']);
        $this->assertSame('Harga jual belum ditetapkan.', $eval['status_message']);
        $this->assertFalse($eval['is_valid']);
        $this->assertFalse($eval['needs_revision']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.variants.show', $variant));
        $response->assertStatus(200);
        $response->assertSee('HARGA BELUM DITETAPKAN');
        $response->assertSee('Harga jual belum ditetapkan.');
    }

    // ==========================================
    // 6-8: EDIT VARIANT PRICE
    // ==========================================

    /**
     * 6. Edit price > HPP accepted
     */
    public function test_6_edit_price_greater_than_hpp_accepted(): void
    {
        $variant = $this->createVariantWithHpp42k('Edit Test', 'SKU-EDIT-6', 60000);

        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.variants.edit', $variant))
            ->put(route('admin.variants.update', $variant), [
                'variant_name'                         => 'Edit Test',
                'base_selling_price'                   => 45000, // > 42.000 HPP
                'requires_printing'                    => 0,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 7000,
                'packaging_process_cost_per_good_unit' => 3000,
                'other_direct_cost_per_unit'           => 2000,
                'active'                               => 1,
            ]);

        $response->assertRedirect(route('admin.variants.show', $variant));
        $response->assertSessionHas('success');
        $this->assertEquals(45000, $variant->fresh()->base_selling_price);
    }

    /**
     * 7. Edit price == HPP rejected
     */
    public function test_7_edit_price_equal_to_hpp_rejected(): void
    {
        $variant = $this->createVariantWithHpp42k('Edit Eq Test', 'SKU-EDIT-7', 60000);

        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.variants.edit', $variant))
            ->put(route('admin.variants.update', $variant), [
                'variant_name'                         => 'Edit Eq Test',
                'base_selling_price'                   => 42000, // == 42.000 HPP
                'requires_printing'                    => 0,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 7000,
                'packaging_process_cost_per_good_unit' => 3000,
                'other_direct_cost_per_unit'           => 2000,
                'active'                               => 1,
            ]);

        $response->assertRedirect(route('admin.variants.edit', $variant));
        $response->assertSessionHasErrors(['base_selling_price']);
        $this->assertEquals(60000, $variant->fresh()->base_selling_price); // Price unchanged
    }

    /**
     * 8. Edit price < HPP rejected
     */
    public function test_8_edit_price_less_than_hpp_rejected(): void
    {
        $variant = $this->createVariantWithHpp42k('Edit Low Test', 'SKU-EDIT-8', 60000);

        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.variants.edit', $variant))
            ->put(route('admin.variants.update', $variant), [
                'variant_name'                         => 'Edit Low Test',
                'base_selling_price'                   => 40000, // < 42.000 HPP
                'requires_printing'                    => 0,
                'printing_cost_per_unit'               => 0,
                'direct_labor_cost_per_unit'           => 7000,
                'packaging_process_cost_per_good_unit' => 3000,
                'other_direct_cost_per_unit'           => 2000,
                'active'                               => 1,
            ]);

        $response->assertRedirect(route('admin.variants.edit', $variant));
        $response->assertSessionHasErrors(['base_selling_price']);
        $this->assertEquals(60000, $variant->fresh()->base_selling_price); // Price unchanged
    }

    // ==========================================
    // 9-16: SALES ORDER SERVER GUARD & MASTER VS TRANSACTION PRICE
    // ==========================================

    /**
     * 9. Sales Order with HPP unavailable rejected
     */
    public function test_9_sales_order_with_hpp_unavailable_rejected(): void
    {
        // Variant without BOM and incomplete direct costing
        $variantNoHpp = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Tanpa BOM',
            'sku'                                  => 'SKU-NO-BOM',
            'base_selling_price'                   => 60000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => null,
            'direct_labor_cost_per_unit'           => null,
            'packaging_process_cost_per_good_unit' => null,
            'other_direct_cost_per_unit'           => null,
            'active'                               => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date'  => '2026-10-08',
                'notes'       => 'Order test HPP unavailable',
                'items'       => [
                    [
                        'variant_id'         => $variantNoHpp->id,
                        'qty_ordered'        => 10,
                        'unit_selling_price' => 60000,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.orders.create'));
        $response->assertSessionHasErrors(['items.0.variant_id']);
        $this->assertDatabaseMissing('orders', ['notes' => 'Order test HPP unavailable']);
    }

    /**
     * 10. HPP 42k / Master 69k / Transaction 69k accepted
     */
    public function test_10_hpp_42k_master_69k_transaction_69k_accepted(): void
    {
        $variant = $this->createVariantWithHpp42k('HPP 42k Variant', 'SKU-42K-SO', 69000);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date'  => '2026-10-08',
                'notes'       => 'Test 10 Order',
                'items'       => [
                    [
                        'variant_id'         => $variant->id,
                        'qty_ordered'        => 5,
                        'unit_selling_price' => 69000,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('order_items', [
            'variant_id'         => $variant->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 69000,
        ]);
    }

    /**
     * 11. HPP 42k / Master 69k / Transaction 65k accepted
     */
    public function test_11_hpp_42k_master_69k_transaction_65k_accepted(): void
    {
        $variant = $this->createVariantWithHpp42k('HPP 42k Variant 2', 'SKU-42K-SO2', 69000);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date'  => '2026-10-08',
                'notes'       => 'Test 11 Order',
                'items'       => [
                    [
                        'variant_id'         => $variant->id,
                        'qty_ordered'        => 5,
                        'unit_selling_price' => 65000,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('order_items', [
            'variant_id'         => $variant->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);
    }

    /**
     * 12. HPP 42k / Master 69k / Transaction 75k accepted
     */
    public function test_12_hpp_42k_master_69k_transaction_75k_accepted(): void
    {
        $variant = $this->createVariantWithHpp42k('HPP 42k Variant 3', 'SKU-42K-SO3', 69000);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date'  => '2026-10-08',
                'notes'       => 'Test 12 Order',
                'items'       => [
                    [
                        'variant_id'         => $variant->id,
                        'qty_ordered'        => 5,
                        'unit_selling_price' => 75000,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('order_items', [
            'variant_id'         => $variant->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 75000,
        ]);
    }

    /**
     * 13. HPP 42k / Transaction 42k rejected
     */
    public function test_13_hpp_42k_transaction_42k_rejected(): void
    {
        $variant = $this->createVariantWithHpp42k('HPP 42k Variant 4', 'SKU-42K-SO4', 69000);

        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date'  => '2026-10-08',
                'notes'       => 'Test 13 Order',
                'items'       => [
                    [
                        'variant_id'         => $variant->id,
                        'qty_ordered'        => 5,
                        'unit_selling_price' => 42000, // == HPP floor
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.orders.create'));
        $response->assertSessionHasErrors(['items.0.variant_id']);
        $this->assertDatabaseMissing('orders', ['notes' => 'Test 13 Order']);
    }

    /**
     * 14. HPP 42k / Transaction 40k rejected
     */
    public function test_14_hpp_42k_transaction_40k_rejected(): void
    {
        $variant = $this->createVariantWithHpp42k('HPP 42k Variant 5', 'SKU-42K-SO5', 69000);

        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date'  => '2026-10-08',
                'notes'       => 'Test 14 Order',
                'items'       => [
                    [
                        'variant_id'         => $variant->id,
                        'qty_ordered'        => 5,
                        'unit_selling_price' => 40000, // < HPP floor
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.orders.create'));
        $response->assertSessionHasErrors(['items.0.variant_id']);
        $this->assertDatabaseMissing('orders', ['notes' => 'Test 14 Order']);
    }

    /**
     * 15. Transaction price does NOT need to equal master price
     */
    public function test_15_transaction_price_does_not_need_to_equal_master_price(): void
    {
        $variant = $this->createVariantWithHpp42k('HPP 42k Variant 6', 'SKU-42K-SO6', 69000);

        // Transaction price 65.000 differs from master 69.000, but is > HPP 42.000
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date'  => '2026-10-08',
                'notes'       => 'Test 15 Order',
                'items'       => [
                    [
                        'variant_id'         => $variant->id,
                        'qty_ordered'        => 10,
                        'unit_selling_price' => 65000,
                    ],
                ],
            ]);

        $response->assertRedirect();

        // Transaction price recorded as 65.000
        $this->assertDatabaseHas('order_items', [
            'variant_id'         => $variant->id,
            'unit_selling_price' => 65000,
        ]);

        // Master price remains unchanged at 69.000
        $this->assertEquals(69000, $variant->fresh()->base_selling_price);
    }

    /**
     * 16. Crafted POST below HPP rejected (Anti-tampering test)
     */
    public function test_16_crafted_post_below_hpp_rejected(): void
    {
        $variant = $this->createVariantWithHpp42k('HPP 42k Variant 7', 'SKU-42K-SO7', 69000);

        // Attacker crafts POST payload with price 20.000 or negative or below HPP
        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'customer_id' => $this->customer->id,
                'order_date'  => '2026-10-08',
                'notes'       => 'Crafted Tamper Attack',
                'items'       => [
                    [
                        'variant_id'         => $variant->id,
                        'qty_ordered'        => 5,
                        'unit_selling_price' => 20000, // Way below HPP 42.000
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.orders.create'));
        $response->assertSessionHasErrors(['items.0.variant_id']);
        $this->assertDatabaseMissing('orders', ['notes' => 'Crafted Tamper Attack']);
    }

    /**
     * 17. Historical order price unchanged when master price or HPP changes
     */
    public function test_17_historical_order_price_unchanged(): void
    {
        $variant = $this->createVariantWithHpp42k('Historical Variant', 'SKU-HIST-17', 69000);

        // Historical order placed at 65.000
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-HIST-001',
            'order_date'  => '2026-09-01',
            'status'      => OrderStatus::SELESAI,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 65000,
        ]);

        // Now Master price is updated to 75.000
        $variant->update(['base_selling_price' => 75000]);

        // And Material cost increases to 40.000, changing HPP to 52.000
        $this->kain->update(['cost_per_unit' => 40000]);
        $this->kain->stock->update(['avg_unit_cost' => 40000]);

        // Verify historical order item price is completely unchanged
        $this->assertEquals(65000, $orderItem->fresh()->unit_selling_price);

        // Check order detail page renders original price
        $response = $this->actingAs($this->adminUser)->get(route('admin.orders.show', $order));
        $response->assertStatus(200);
        $response->assertSee('Rp 65.000');
    }
}
