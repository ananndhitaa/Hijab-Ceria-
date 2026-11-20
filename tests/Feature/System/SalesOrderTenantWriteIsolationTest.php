<?php

namespace Tests\Feature\System;

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
use App\Models\System\OrderStatusHistory;
use App\Models\System\OrderTrackingToken;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Tracking\TrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderTenantWriteIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected User $adminA;
    protected User $adminB;
    protected Customer $customerA;
    protected Customer $customerB;
    protected Product $productA;
    protected Product $productB;
    protected ProductVariant $variantA;
    protected ProductVariant $variantB;
    protected ProductVariant $brokenVariant;
    protected Order $orderA;
    protected Order $orderB;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable tenant isolation enforcement for security testing
        config(['nayara.tenant_isolation_enabled' => true]);

        // Setup Outlets
        $this->outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $this->outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        // Setup Admins
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'adm-a');
        $this->adminB = $this->createTestUser($this->outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'adm-b');

        // Setup Customers
        $this->customerA = Customer::create([
            'customer_name' => 'Customer Nayara A',
            'phone'         => '081211112222',
            'outlet_id'     => $this->outletA->id,
        ]);

        $this->customerB = Customer::create([
            'customer_name' => 'Customer Nayara B',
            'phone'         => '081233334444',
            'outlet_id'     => $this->outletB->id,
        ]);

        // Setup Materials, Unit, and Stocks for BOM Reference HPP Calculation
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        $materialA = Material::create([
            'material_name' => 'Kain Ceruty A',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        MaterialStock::create([
            'material_id'   => $materialA->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 20000,
        ]);

        $materialB = Material::create([
            'material_name' => 'Kain Silk B',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 30000,
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        MaterialStock::create([
            'material_id'   => $materialB->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 30000,
        ]);

        // Setup Products & Variants
        $this->productA = Product::create([
            'product_name' => 'Hijab Ceruty A',
            'active'       => true,
            'outlet_id'    => $this->outletA->id,
        ]);

        $this->variantA = ProductVariant::create([
            'product_id'                           => $this->productA->id,
            'variant_name'                         => 'Navy A',
            'sku'                                  => 'SKU-CRT-NV-A',
            'base_selling_price'                   => 65000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'outlet_id'                            => $this->outletA->id,
        ]);
        BomItem::create([
            'variant_id'   => $this->variantA->id,
            'material_id'  => $materialA->id,
            'qty_per_unit' => 1,
        ]);

        $this->productB = Product::create([
            'product_name' => 'Hijab Silk B',
            'active'       => true,
            'outlet_id'    => $this->outletB->id,
        ]);

        $this->variantB = ProductVariant::create([
            'product_id'                           => $this->productB->id,
            'variant_name'                         => 'Gold B',
            'sku'                                  => 'SKU-SLK-GL-B',
            'base_selling_price'                   => 95000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 8000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'outlet_id'                            => $this->outletB->id,
        ]);
        BomItem::create([
            'variant_id'   => $this->variantB->id,
            'material_id'  => $materialB->id,
            'qty_per_unit' => 1,
        ]);

        // Broken relation variant: Variant linked to Outlet A, but parent Product linked to Outlet B
        $this->brokenVariant = ProductVariant::create([
            'product_id'                           => $this->productB->id, // belongs to Outlet B!
            'variant_name'                         => 'Broken Variant',
            'sku'                                  => 'SKU-BRK-001',
            'base_selling_price'                   => 80000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 2000,
            'other_direct_cost_per_unit'           => 1000,
            'outlet_id'                            => $this->outletA->id, // claims Outlet A!
        ]);
        BomItem::create([
            'variant_id'   => $this->brokenVariant->id,
            'material_id'  => $materialA->id,
            'qty_per_unit' => 1,
        ]);

        // Base Orders
        $this->orderA = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-NAYARA-A-BASE',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'notes'       => 'Base note A',
            'outlet_id'   => $this->outletA->id,
        ]);
        OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 65000,
        ]);
        app(TrackingTokenService::class)->createForOrder($this->orderA);

        $this->orderB = Order::create([
            'customer_id' => $this->customerB->id,
            'order_no'    => 'SO-NAYARA-B-BASE',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'notes'       => 'Base note B',
            'outlet_id'   => $this->outletB->id,
        ]);
        OrderItem::create([
            'order_id'           => $this->orderB->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 3,
            'unit_selling_price' => 95000,
        ]);
        app(TrackingTokenService::class)->createForOrder($this->orderB);
    }

    protected function createTestOutlet(string $code = 'OUT-A', string $name = 'Outlet A', string $status = 'ACTIVE'): Outlet
    {
        return Outlet::create([
            'outlet_name'                => $name,
            'outlet_code'                => $code,
            'slug'                       => strtolower($code),
            'status'                     => $status,
            'is_public_showcase_enabled' => true,
        ]);
    }

    protected function createTestUser(
        ?Outlet $outlet,
        UserRole $role = UserRole::ADMIN,
        UserStatus $status = UserStatus::ACTIVE,
        string $suffix = '01'
    ): User {
        return User::factory()->create([
            'name'      => "User {$role->value} {$suffix}",
            'email'     => "user.{$suffix}." . uniqid() . "@nayara.test",
            'role'      => $role,
            'status'    => $status,
            'outlet_id' => $outlet?->id,
        ]);
    }

    // =========================================================================
    // SKENARIO 1 - 8: STORE (PEMBUATAN SALES ORDER & VALIDASI ISOLASI)
    // =========================================================================

    /**
     * 1. User Outlet A dapat membuat Order dengan Customer dan Variant milik Outlet A.
     */
    public function test_01_user_outlet_a_can_create_order_with_own_customer_and_variant(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'notes'       => 'Order baru Outlet A',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customerA->id,
            'notes'       => 'Order baru Outlet A',
            'outlet_id'   => $this->outletA->id,
        ]);
    }

    /**
     * 2. Order baru tersimpan dengan outlet_id milik Outlet A secara server-side.
     */
    public function test_02_newly_created_order_is_saved_with_outlet_a_id(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'notes'       => 'Verifikasi outlet_id server-side',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 4,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);

        $order = Order::where('notes', 'Verifikasi outlet_id server-side')->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->outletA->id, $order->outlet_id);
    }

    /**
     * 3. User Outlet B dapat membuat Order untuk Outlet B.
     */
    public function test_03_user_outlet_b_can_create_order_for_outlet_b(): void
    {
        $payload = [
            'customer_id' => $this->customerB->id,
            'order_date'  => '2026-10-24',
            'notes'       => 'Order resmi Outlet B',
            'items'       => [
                [
                    'variant_id'         => $this->variantB->id,
                    'qty_ordered'        => 2,
                    'unit_selling_price' => 95000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminB)->post(route('admin.orders.store'), $payload);

        $response->assertRedirect();
        $order = Order::where('notes', 'Order resmi Outlet B')->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->outletB->id, $order->outlet_id);
    }

    /**
     * 4. outlet_id palsu pada request tidak mengubah kepemilikan Order yang baru dibuat.
     */
    public function test_04_fake_outlet_id_in_request_does_not_override_ownership(): void
    {
        $payload = [
            'outlet_id'   => $this->outletB->id, // Injected fake outlet ID
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'notes'       => 'Anti tampering test',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);

        $order = Order::where('notes', 'Anti tampering test')->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->outletA->id, $order->outlet_id);
        $this->assertNotEquals($this->outletB->id, $order->outlet_id);
    }

    /**
     * 5. Customer Outlet B ditolak ketika dipilih oleh User A.
     */
    public function test_05_customer_of_outlet_b_is_rejected_when_selected_by_user_a(): void
    {
        $payload = [
            'customer_id' => $this->customerB->id, // Belongs to Outlet B!
            'order_date'  => '2026-10-24',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 1,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors(['customer_id']);
        $this->assertDatabaseMissing('orders', ['notes' => 'Cross tenant customer']);
    }

    /**
     * 6. Variant Outlet B ditolak ketika dipilih oleh User A.
     */
    public function test_06_variant_of_outlet_b_is_rejected_when_selected_by_user_a(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'items'       => [
                [
                    'variant_id'         => $this->variantB->id, // Belongs to Outlet B!
                    'qty_ordered'        => 1,
                    'unit_selling_price' => 95000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors(['items.0.variant_id']);
    }

    /**
     * 7. Variant Outlet A dengan Parent Product Outlet B ditolak sebagai relasi rusak.
     */
    public function test_07_variant_with_cross_outlet_parent_product_is_rejected_as_broken_relation(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'items'       => [
                [
                    'variant_id'         => $this->brokenVariant->id, // parent product is Outlet B!
                    'qty_ordered'        => 1,
                    'unit_selling_price' => 80000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);

        $response->assertSessionHasErrors(['items.0.variant_id']);
    }

    /**
     * 8. Tidak ada Order atau item parsial setelah validation error.
     */
    public function test_08_no_partial_order_or_order_items_remain_after_validation_error(): void
    {
        $initialOrderCount = Order::count();
        $initialItemCount = OrderItem::count();

        $invalidPayload = [
            'customer_id' => $this->customerB->id, // Invalid cross tenant customer
            'order_date'  => '2026-10-24',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $this->actingAs($this->adminA)->post(route('admin.orders.store'), $invalidPayload);

        $this->assertEquals($initialOrderCount, Order::count());
        $this->assertEquals($initialItemCount, OrderItem::count());
    }

    // =========================================================================
    // SKENARIO 9 - 15: UPDATE (PERUBAHAN SALES ORDER & ANTI-IDOR)
    // =========================================================================

    /**
     * 9. User Outlet A dapat update Order milik Outlet A sesuai aturan bisnis existing.
     */
    public function test_09_user_outlet_a_can_update_own_order(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-25',
            'notes'       => 'Catatan diperbarui secara sah',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 8,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->put(route('admin.orders.update', $this->orderA), $payload);

        $response->assertRedirect(route('admin.orders.show', $this->orderA));
        $this->assertEquals('Catatan diperbarui secara sah', $this->orderA->fresh()->notes);
    }

    /**
     * 10. User Outlet A tidak dapat update Order Outlet B.
     */
    public function test_10_user_outlet_a_cannot_update_outlet_b_order(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-25',
            'notes'       => 'Illegal modification attempt',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 1,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->put(route('admin.orders.update', $this->orderB), $payload);

        $response->assertNotFound();
        $this->assertEquals('Base note B', $this->orderB->fresh()->notes);
    }

    /**
     * 11. Update Order lintas-outlet menghasilkan HTTP 404.
     */
    public function test_11_cross_outlet_order_update_returns_http_404(): void
    {
        $response = $this->actingAs($this->adminA)->put(route('admin.orders.update', $this->orderB), [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-25',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 1,
                    'unit_selling_price' => 65000,
                ],
            ],
        ]);

        $response->assertNotFound();
    }

    /**
     * 12. Update Order dengan outlet_id NULL menghasilkan 404.
     */
    public function test_12_update_order_with_null_outlet_id_returns_404(): void
    {
        $unassignedOrder = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-NULL-OUTLET-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => null,
        ]);

        $response = $this->actingAs($this->adminA)->put(route('admin.orders.update', $unassignedOrder), [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-25',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 1,
                    'unit_selling_price' => 65000,
                ],
            ],
        ]);

        $response->assertNotFound();
    }

    /**
     * 13. Customer lintas-outlet ditolak saat update.
     */
    public function test_13_cross_outlet_customer_is_rejected_on_update(): void
    {
        $payload = [
            'customer_id' => $this->customerB->id, // Outlet B customer!
            'order_date'  => '2026-10-25',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->put(route('admin.orders.update', $this->orderA), $payload);

        $response->assertSessionHasErrors(['customer_id']);
        $this->assertEquals($this->customerA->id, $this->orderA->fresh()->customer_id);
    }

    /**
     * 14. Variant lintas-outlet ditolak saat update.
     */
    public function test_14_cross_outlet_variant_is_rejected_on_update(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-25',
            'items'       => [
                [
                    'variant_id'         => $this->variantB->id, // Outlet B variant!
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 95000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->put(route('admin.orders.update', $this->orderA), $payload);

        $response->assertSessionHasErrors(['items.0.variant_id']);
    }

    /**
     * 15. Upaya manipulasi outlet_id saat update tidak berhasil.
     */
    public function test_15_tampered_outlet_id_on_update_does_not_change_order_ownership(): void
    {
        $payload = [
            'outlet_id'   => $this->outletB->id, // Attempt to move order to outlet B
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-25',
            'notes'       => 'Attempting outlet migration',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $this->actingAs($this->adminA)->put(route('admin.orders.update', $this->orderA), $payload);

        $this->assertEquals($this->outletA->id, $this->orderA->fresh()->outlet_id);
    }

    // =========================================================================
    // SKENARIO 16 - 19: CONFIRM DEAL & ATOMIC INVOICE CREATION
    // =========================================================================

    /**
     * 16. ConfirmDeal Order sendiri mengikuti aturan bisnis existing.
     */
    public function test_16_confirm_deal_on_own_order_succeeds(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.orders.confirm-deal', $this->orderA), [
            'notes' => 'Konfirmasi deal sah Outlet A',
        ]);

        $response->assertRedirect(route('admin.orders.show', $this->orderA));
        $this->assertEquals(OrderStatus::DEAL, $this->orderA->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $this->orderA->id,
            'status'   => OrderStatus::DEAL->value,
            'notes'    => 'Konfirmasi deal sah Outlet A',
        ]);
    }

    /**
     * 17. ConfirmDeal Order outlet lain ditolak sebelum mutasi (HTTP 404).
     */
    public function test_17_confirm_deal_on_other_outlet_order_returns_404_and_no_mutation(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.orders.confirm-deal', $this->orderB));

        $response->assertNotFound();
        $this->assertEquals(OrderStatus::KOMUNIKASI, $this->orderB->fresh()->status);
    }

    /**
     * 18. ConfirmDeal Order unassigned ditolak saat mode TRUE (HTTP 404).
     */
    public function test_18_confirm_deal_on_unassigned_order_returns_404(): void
    {
        $unassignedOrder = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-NULL-OUTLET-002',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => null,
        ]);

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.confirm-deal', $unassignedOrder));

        $response->assertNotFound();
        $this->assertEquals(OrderStatus::KOMUNIKASI, $unassignedOrder->fresh()->status);
    }

    /**
     * 19. Jika pembuatan order disertai DP, Invoice tetap terikat ke Order sah dari outlet benar.
     */
    public function test_19_invoice_created_during_store_strictly_binds_to_legitimate_order_and_tenant(): void
    {
        $payload = [
            'customer_id'    => $this->customerA->id,
            'order_date'     => '2026-10-24',
            'dp_amount'      => 65000,
            'payment_date'   => '2026-10-24',
            'payment_method' => \App\Enums\PaymentMethod::TRANSFER_BANK->value,
            'items'          => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 2,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payload);
        $response->assertRedirect();

        $newOrder = Order::where('customer_id', $this->customerA->id)->latest('id')->first();
        $this->assertNotNull($newOrder);
        $this->assertEquals(OrderStatus::DEAL, $newOrder->status);
        $this->assertEquals($this->outletA->id, $newOrder->outlet_id);

        // Verify Invoice bound to this order and derives outlet A
        $this->assertNotNull($newOrder->invoice);
        $this->assertEquals($this->outletA->id, $newOrder->invoice->outlet?->id);
    }

    // =========================================================================
    // SKENARIO 20 - 22: REGENERATE TRACKING TOKEN
    // =========================================================================

    /**
     * 20. Regenerate token Order sendiri tetap bekerja sesuai workflow.
     */
    public function test_20_regenerate_token_on_own_order_succeeds(): void
    {
        $oldToken = $this->orderA->trackingTokens()->valid()->first()?->token_hash;

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.regenerate-tracking-token', $this->orderA));

        $response->assertRedirect(route('admin.orders.show', $this->orderA));
        $newToken = $this->orderA->fresh()->trackingTokens()->valid()->first()?->token_hash;
        $this->assertNotEquals($oldToken, $newToken);
    }

    /**
     * 21. Regenerate token Order outlet lain menghasilkan 404.
     */
    public function test_21_regenerate_token_on_other_outlet_order_returns_404(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.orders.regenerate-tracking-token', $this->orderB));

        $response->assertNotFound();
    }

    /**
     * 22. Tracking token Order lain tidak berubah setelah unauthorized request.
     */
    public function test_22_tracking_token_of_other_outlet_order_is_unchanged_after_unauthorized_attempt(): void
    {
        $tokenBefore = $this->orderB->trackingTokens()->valid()->first()?->token_hash;

        $this->actingAs($this->adminA)->post(route('admin.orders.regenerate-tracking-token', $this->orderB));

        $tokenAfter = $this->orderB->fresh()->trackingTokens()->valid()->first()?->token_hash;
        $this->assertEquals($tokenBefore, $tokenAfter);
    }

    // =========================================================================
    // SKENARIO 23 - 26: VALIDASI USER / TENANT SEBELUM WRITE
    // =========================================================================

    /**
     * 23. User tanpa outlet ditolak sebelum proses write (HTTP 403).
     */
    public function test_23_user_with_null_outlet_is_rejected_before_write(): void
    {
        $unboundAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'unbound-wr');

        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 1,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $this->actingAs($unboundAdmin)->post(route('admin.orders.store'), $payload)->assertForbidden();
        $this->actingAs($unboundAdmin)->put(route('admin.orders.update', $this->orderA), $payload)->assertForbidden();
        $this->actingAs($unboundAdmin)->post(route('admin.orders.confirm-deal', $this->orderA))->assertForbidden();
        $this->actingAs($unboundAdmin)->post(route('admin.orders.regenerate-tracking-token', $this->orderA))->assertForbidden();
    }

    /**
     * 24. User dengan outlet INACTIVE ditolak sebelum proses write (HTTP 403).
     */
    public function test_24_user_with_inactive_outlet_is_rejected_before_write(): void
    {
        $inactiveOutlet = $this->createTestOutlet('OUT-INACT', 'Inactive Outlet', 'INACTIVE');
        $inactiveOutletAdmin = $this->createTestUser($inactiveOutlet, UserRole::ADMIN, UserStatus::ACTIVE, 'inact-adm');

        $this->actingAs($inactiveOutletAdmin)->post(route('admin.orders.store'), [])->assertForbidden();
        $this->actingAs($inactiveOutletAdmin)->put(route('admin.orders.update', $this->orderA), [])->assertForbidden();
    }

    /**
     * 25. User INACTIVE ditolak.
     */
    public function test_25_inactive_user_is_rejected_on_write(): void
    {
        $inactiveUser = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::INACTIVE, 'inact-usr');

        $response = $this->actingAs($inactiveUser)->post(route('admin.orders.store'), []);
        $response->assertRedirect(route('login'));
    }

    /**
     * 26. Parameter/header outlet_id palsu tidak mengubah tenant pada seluruh operasi write.
     */
    public function test_26_fake_outlet_headers_and_parameters_do_not_override_tenant(): void
    {
        $payload = [
            'outlet_id'   => $this->outletB->id,
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'notes'       => 'Header injection test',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 3,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $this->actingAs($this->adminA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('admin.orders.store'), $payload);

        $order = Order::where('notes', 'Header injection test')->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->outletA->id, $order->outlet_id);
    }

    // =========================================================================
    // SKENARIO 27 - 29: OTORISASI ROLE
    // =========================================================================

    /**
     * 27. RoleMiddleware existing tetap membatasi akses (Gudang, Produksi, Customer).
     */
    public function test_27_role_middleware_restricts_gudang_produksi_customer_from_write_routes(): void
    {
        $gudang = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang-wr');
        $produksi = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-wr');

        $this->actingAs($gudang)->post(route('admin.orders.store'), [])->assertForbidden();
        $this->actingAs($produksi)->post(route('admin.orders.store'), [])->assertForbidden();
    }

    /**
     * 28. CUSTOMER tidak bisa membuat Order melalui Admin route.
     */
    public function test_28_customer_cannot_create_order_via_admin_route(): void
    {
        $customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'cust-wr');

        $this->actingAs($customerUser)->post(route('admin.orders.store'), [])->assertForbidden();
    }

    /**
     * 29. Hak approval PO tetap ADMIN.
     */
    public function test_29_po_approval_remains_exclusive_to_admin(): void
    {
        $gudang = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang-po2');

        $this->actingAs($this->adminA)->get(route('admin.purchases.approvals.index'))->assertOk();
        $this->actingAs($gudang)->get(route('admin.purchases.approvals.index'))->assertForbidden();
    }

    // =========================================================================
    // SKENARIO 30 - 36: INTEGRITAS BISNIS, ROLLBACK & NON-INTERFERENCE
    // =========================================================================

    /**
     * 30. Mode flag FALSE mempertahankan perilaku legacy existing.
     */
    public function test_30_disabled_flag_preserves_legacy_erp_behavior(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $legacyAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'leg-wr');

        $payload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'notes'       => 'Legacy order without outlet',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 2,
                    'unit_selling_price' => 65000,
                ],
            ],
        ];

        $response = $this->actingAs($legacyAdmin)->post(route('admin.orders.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('orders', ['notes' => 'Legacy order without outlet']);
    }

    /**
     * 31. Mode flag TRUE gagal aman ketika tenant tidak valid.
     */
    public function test_31_isolation_mode_fails_closed_when_tenant_is_invalid(): void
    {
        $this->post(route('admin.orders.store'), [])->assertRedirect(route('login'));
    }

    /**
     * 32. Nilai nominal dan perhitungan bisnis tetap mengikuti logic ERP existing (price floor HPP).
     */
    public function test_32_nominal_and_business_calculations_follow_existing_erp_logic(): void
    {
        // Selling price is equal to or lower than HPP (material cost 20.000 + direct labor 5.000 = 25.000)
        $payloadLowPrice = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 1,
                    'unit_selling_price' => 20000, // lower than HPP!
                ],
            ],
        ];

        $response = $this->actingAs($this->adminA)->post(route('admin.orders.store'), $payloadLowPrice);
        $response->assertSessionHasErrors(['items.0.variant_id']);
    }

    /**
     * 33. Ketika write transaction gagal, database tidak menyisakan partial mutation.
     */
    public function test_33_when_write_transaction_fails_database_rolls_back_cleanly(): void
    {
        $orderCountBefore = Order::count();
        $itemCountBefore = OrderItem::count();
        $historyCountBefore = OrderStatusHistory::count();

        // Valid customer and variant, but price violates floor
        $invalidPayload = [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-24',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 5,
                    'unit_selling_price' => 10000, // below HPP
                ],
            ],
        ];

        $this->actingAs($this->adminA)->post(route('admin.orders.store'), $invalidPayload);

        $this->assertEquals($orderCountBefore, Order::count());
        $this->assertEquals($itemCountBefore, OrderItem::count());
        $this->assertEquals($historyCountBefore, OrderStatusHistory::count());
    }

    /**
     * 34. Tidak ada Global Tenant Scope yang aktif diam-diam.
     */
    public function test_34_no_global_tenant_scope_active_quietly(): void
    {
        $totalOrdersUnscoped = Order::withoutGlobalScopes()->count();
        $totalOrdersStandard = Order::count();

        $this->assertEquals($totalOrdersUnscoped, $totalOrdersStandard);
    }

    /**
     * 35. Existing Sales Order GET tetap terisolasi pada flag TRUE.
     */
    public function test_35_existing_sales_order_get_remains_isolated(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('SO-NAYARA-A-BASE');
        $response->assertDontSee('SO-NAYARA-B-BASE');
    }

    /**
     * 36. Percobaan write lintas-outlet tidak memengaruhi data tenant lain.
     */
    public function test_36_cross_tenant_write_attempts_never_affect_other_tenant_data(): void
    {
        $orderBOriginal = $this->orderB->fresh()->toArray();

        // Admin A attempts to hijack Order B
        $this->actingAs($this->adminA)->put(route('admin.orders.update', $this->orderB), [
            'customer_id' => $this->customerA->id,
            'order_date'  => '2026-10-30',
            'notes'       => 'Hijacked notes',
            'items'       => [
                [
                    'variant_id'         => $this->variantA->id,
                    'qty_ordered'        => 99,
                    'unit_selling_price' => 65000,
                ],
            ],
        ])->assertNotFound();

        $orderBAfter = $this->orderB->fresh()->toArray();

        $this->assertEquals($orderBOriginal['notes'], $orderBAfter['notes']);
        $this->assertEquals($orderBOriginal['customer_id'], $orderBAfter['customer_id']);
        $this->assertEquals($orderBOriginal['status'], $orderBAfter['status']);
    }
}
