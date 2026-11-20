<?php

namespace Tests\Feature\System;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\OrderStatusHistory;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderTenantReadIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected User $adminA;
    protected User $adminB;
    protected Order $orderA;
    protected Order $orderB;
    protected Customer $customerA;
    protected Customer $customerB;
    protected ProductVariant $variantA;
    protected ProductVariant $variantB;

    protected function setUp(): void
    {
        parent::setUp();

        // Activate tenant isolation enforcement by default for security testing
        config(['nayara.tenant_isolation_enabled' => true]);

        // Outlet A & Outlet B setup
        $this->outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $this->outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        // Admin users attached to respective outlets
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-a');
        $this->adminB = $this->createTestUser($this->outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-b');

        // Customers for both outlets
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

        // Products & variants for both outlets
        $productA = Product::create([
            'product_name' => 'Hijab Segiempat Voal A',
            'active'       => true,
            'outlet_id'    => $this->outletA->id,
        ]);

        $this->variantA = ProductVariant::create([
            'product_id'         => $productA->id,
            'variant_name'       => 'Dusty Rose A',
            'sku'                => 'SKU-VOAL-DRA',
            'base_selling_price' => 75000,
            'outlet_id'          => $this->outletA->id,
        ]);

        $productB = Product::create([
            'product_name' => 'Hijab Pashmina Silk B',
            'active'       => true,
            'outlet_id'    => $this->outletB->id,
        ]);

        $this->variantB = ProductVariant::create([
            'product_id'         => $productB->id,
            'variant_name'       => 'Emerald Green B',
            'sku'                => 'SKU-SILK-EGB',
            'base_selling_price' => 95000,
            'outlet_id'          => $this->outletB->id,
        ]);

        // Sales Orders for both outlets
        $this->orderA = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-NAYARA-A-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $this->outletA->id,
            'notes'       => 'Private client note for Outlet A',
        ]);

        OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 75000,
        ]);

        $this->orderB = Order::create([
            'customer_id' => $this->customerB->id,
            'order_no'    => 'SO-NAYARA-B-002',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => $this->outletB->id,
            'notes'       => 'Private client note for Outlet B',
        ]);

        OrderItem::create([
            'order_id'           => $this->orderB->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 8,
            'unit_selling_price' => 95000,
        ]);
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
    // SKENARIO 1 - 4: ISOLASI LISTING SALES ORDER
    // =========================================================================

    /**
     * 1. User A melihat daftar Order A.
     */
    public function test_01_user_a_sees_outlet_a_orders_on_listing(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee($this->orderA->order_no);
        $response->assertSee('Customer Nayara A');
    }

    /**
     * 2. User A tidak melihat Order B.
     */
    public function test_02_user_a_cannot_see_outlet_b_orders_on_listing(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertDontSee($this->orderB->order_no);
        $response->assertDontSee('Customer Nayara B');
    }

    /**
     * 3. User B melihat daftar Order B.
     */
    public function test_03_user_b_sees_outlet_b_orders_on_listing(): void
    {
        $response = $this->actingAs($this->adminB)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee($this->orderB->order_no);
        $response->assertSee('Customer Nayara B');
    }

    /**
     * 4. User B tidak melihat Order A.
     */
    public function test_04_user_b_cannot_see_outlet_a_orders_on_listing(): void
    {
        $response = $this->actingAs($this->adminB)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertDontSee($this->orderA->order_no);
        $response->assertDontSee('Customer Nayara A');
    }

    // =========================================================================
    // SKENARIO 5 - 7: DETAIL ORDER DAN ANTI-IDOR
    // =========================================================================

    /**
     * 5. ID Order A dapat dibuka oleh user berwenang Outlet A.
     */
    public function test_05_authorized_user_a_can_view_outlet_a_order_detail(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.show', $this->orderA));

        $response->assertOk();
        $response->assertSee($this->orderA->order_no);
        $response->assertSee('Customer Nayara A');
        $response->assertSee('Private client note for Outlet A');
    }

    /**
     * 6. ID Order B tidak dapat dibuka oleh User A (HTTP 404 anti-IDOR).
     */
    public function test_06_user_a_cannot_view_outlet_b_order_detail_and_receives_404(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.show', $this->orderB));

        $response->assertNotFound();
    }

    /**
     * 7. Manipulasi ID URL menghasilkan HTTP 404.
     */
    public function test_07_tampered_order_id_returns_http_404(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.show', ['order' => 999999]));

        $response->assertNotFound();
    }

    // =========================================================================
    // SKENARIO 8 - 11: RECORD NULL DAN VALIDASI TENANT / USER STATUS
    // =========================================================================

    /**
     * 8. Record dengan outlet_id NULL tidak terlihat saat enforcement.
     */
    public function test_08_order_with_null_outlet_id_is_hidden_from_listing_and_detail(): void
    {
        $orphanCustomer = Customer::create([
            'customer_name' => 'Orphan Customer',
            'phone'         => '081299990000',
            'outlet_id'     => null,
        ]);

        $orphanOrder = Order::create([
            'customer_id' => $orphanCustomer->id,
            'order_no'    => 'SO-ORPHAN-NULL-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::KOMUNIKASI,
            'outlet_id'   => null,
        ]);

        // Listing hides null outlet records
        $responseListing = $this->actingAs($this->adminA)->get(route('admin.orders.index'));
        $responseListing->assertOk();
        $responseListing->assertDontSee('SO-ORPHAN-NULL-001');

        // Show endpoint returns 404
        $responseShow = $this->actingAs($this->adminA)->get(route('admin.orders.show', $orphanOrder));
        $responseShow->assertNotFound();

        // Edit endpoint returns 404
        $responseEdit = $this->actingAs($this->adminA)->get(route('admin.orders.edit', $orphanOrder));
        $responseEdit->assertNotFound();
    }

    /**
     * 9. User dengan outlet_id NULL ditolak (fail-closed 403).
     */
    public function test_09_user_with_null_outlet_id_is_rejected_fail_closed(): void
    {
        $unboundAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'unbound');

        $responseListing = $this->actingAs($unboundAdmin)->get(route('admin.orders.index'));
        $responseListing->assertForbidden();

        $responseShow = $this->actingAs($unboundAdmin)->get(route('admin.orders.show', $this->orderA));
        $responseShow->assertForbidden();
    }

    /**
     * 10. Outlet INACTIVE ditolak (fail-closed 403).
     */
    public function test_10_user_with_inactive_outlet_is_rejected_fail_closed(): void
    {
        $inactiveOutlet = $this->createTestOutlet('OUT-INACTIVE', 'Inactive Outlet', 'INACTIVE');
        $inactiveOutletUser = $this->createTestUser($inactiveOutlet, UserRole::ADMIN, UserStatus::ACTIVE, 'inact-out');

        $response = $this->actingAs($inactiveOutletUser)->get(route('admin.orders.index'));
        $response->assertForbidden();
    }

    /**
     * 11. User INACTIVE ditolak (fail-closed: redirected & invalidated by EnsureUserIsActive, and rejected by TenantContext).
     */
    public function test_11_inactive_user_is_rejected_fail_closed(): void
    {
        $inactiveUser = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::INACTIVE, 'inact-user');

        $response = $this->actingAs($inactiveUser)->get(route('admin.orders.index'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);

        // TenantContext also throws AuthorizationException for inactive user
        try {
            app(\App\Services\System\TenantContext::class)->outletFor($inactiveUser);
            $this->fail('TenantContext should throw AuthorizationException for inactive user.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertStringContainsString('Akun pengguna sedang tidak aktif', $e->getMessage());
        }
    }

    // =========================================================================
    // SKENARIO 12 - 13: UNTRUSTED CLIENT INPUT IMMUNITY
    // =========================================================================

    /**
     * 12. Parameter outlet_id palsu tidak mengubah tenant context.
     */
    public function test_12_fake_outlet_id_parameter_does_not_override_tenant_context(): void
    {
        // User A attempts to request outlet B's ID via query string
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.index', [
            'outlet_id' => $this->outletB->id,
        ]));

        $response->assertOk();
        $response->assertSee($this->orderA->order_no);
        $response->assertDontSee($this->orderB->order_no);
    }

    /**
     * 13. Header X-Outlet-ID palsu tidak mengubah tenant context.
     */
    public function test_13_fake_outlet_id_header_does_not_override_tenant_context(): void
    {
        // User A attempts to inject header X-Outlet-ID
        $response = $this->actingAs($this->adminA)->withHeaders([
            'X-Outlet-ID' => (string) $this->outletB->id,
        ])->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee($this->orderA->order_no);
        $response->assertDontSee($this->orderB->order_no);
    }

    // =========================================================================
    // SKENARIO 14 - 15: PAGINATION DAN SEARCH/FILTER SCOPING
    // =========================================================================

    /**
     * 14. Pagination tidak mencampurkan data outlet lain.
     */
    public function test_14_pagination_does_not_mix_cross_tenant_records(): void
    {
        // Create 16 additional orders for Outlet A (total 17 for A)
        for ($i = 1; $i <= 16; $i++) {
            Order::create([
                'customer_id' => $this->customerA->id,
                'order_no'    => sprintf('SO-PAGINATE-A-%03d', $i),
                'order_date'  => '2026-10-23',
                'status'      => OrderStatus::KOMUNIKASI,
                'outlet_id'   => $this->outletA->id,
            ]);
        }

        // Create 5 orders for Outlet B
        for ($j = 1; $j <= 5; $j++) {
            Order::create([
                'customer_id' => $this->customerB->id,
                'order_no'    => sprintf('SO-PAGINATE-B-%03d', $j),
                'order_date'  => '2026-10-23',
                'status'      => OrderStatus::KOMUNIKASI,
                'outlet_id'   => $this->outletB->id,
            ]);
        }

        // Page 1 for User A (15 records per page)
        $responsePage1 = $this->actingAs($this->adminA)->get(route('admin.orders.index', ['page' => 1]));
        $responsePage1->assertOk();
        $responsePage1->assertDontSee('SO-PAGINATE-B-');

        // Page 2 for User A (remaining 2 records)
        $responsePage2 = $this->actingAs($this->adminA)->get(route('admin.orders.index', ['page' => 2]));
        $responsePage2->assertOk();
        $responsePage2->assertDontSee('SO-PAGINATE-B-');
    }

    /**
     * 15. Search dan filter status tidak mencampurkan tenant.
     */
    public function test_15_search_and_status_filters_remain_strictly_scoped_to_active_tenant(): void
    {
        // Search for Order B's order_no while authenticated as User A
        $responseSearch = $this->actingAs($this->adminA)->get(route('admin.orders.index', [
            'search' => 'SO-NAYARA-B-002',
        ]));

        $responseSearch->assertOk();
        $responseSearch->assertSee('Belum ada Sales Order');
        $responseSearch->assertDontSee('Customer Nayara B');

        // Filter by status while searching customer B
        $responseFilter = $this->actingAs($this->adminA)->get(route('admin.orders.index', [
            'search' => 'Customer Nayara B',
            'status' => OrderStatus::KOMUNIKASI->value,
        ]));

        $responseFilter->assertOk();
        $responseFilter->assertSee('Belum ada Sales Order');
        $responseFilter->assertDontSee('SO-NAYARA-B-002');
    }

    // =========================================================================
    // SKENARIO 16 - 18: RELASI, DROPDOWN, DAN DATA PENDUKUNG
    // =========================================================================

    /**
     * 16. Data Customer relasi pada create dan edit hanya milik outlet yang sah.
     */
    public function test_16_customer_dropdown_is_scoped_to_active_tenant(): void
    {
        // Create form dropdown
        $responseCreate = $this->actingAs($this->adminA)->get(route('admin.orders.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('Customer Nayara A');
        $responseCreate->assertDontSee('Customer Nayara B');

        // Edit form dropdown
        $responseEdit = $this->actingAs($this->adminA)->get(route('admin.orders.edit', $this->orderA));
        $responseEdit->assertOk();
        $responseEdit->assertSee('Customer Nayara A');
        $responseEdit->assertDontSee('Customer Nayara B');
    }

    /**
     * 17. Data item dan variant tidak berasal dari outlet lain.
     */
    public function test_17_product_variants_are_scoped_to_active_tenant(): void
    {
        $responseCreate = $this->actingAs($this->adminA)->get(route('admin.orders.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('Dusty Rose A');
        $responseCreate->assertDontSee('Emerald Green B');

        $responseEdit = $this->actingAs($this->adminA)->get(route('admin.orders.edit', $this->orderA));
        $responseEdit->assertOk();
        $responseEdit->assertSee('Dusty Rose A');
        $responseEdit->assertDontSee('Emerald Green B');
    }

    /**
     * 18. Perhitungan aggregate listing tetap scoped ke tenant aktif.
     */
    public function test_18_listing_aggregates_and_counts_are_tenant_scoped(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.index'));
        $response->assertOk();

        // Order A has 1 item, 5 pcs, total Rp 375.000 (5 * 75.000)
        $response->assertSee('1 item (5 pcs)');
        $response->assertSee('375.000');

        // Should not see Order B total Rp 760.000 (8 * 95.000)
        $response->assertDontSee('760.000');
    }

    // =========================================================================
    // SKENARIO 19 - 21: OTORISASI ROLE DAN KEAMANAN OPERASI GET
    // =========================================================================

    /**
     * 19. RoleMiddleware existing masih membatasi akses ke admin orders.
     */
    public function test_19_role_middleware_restricts_non_admin_roles_from_admin_orders(): void
    {
        $gudangUser = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang');
        $produksiUser = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'produksi');
        $customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'customer');

        $this->actingAs($gudangUser)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($produksiUser)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($customerUser)->get(route('admin.orders.index'))->assertForbidden();
    }

    /**
     * 20. Hak approval Purchase Order tetap eksklusif ADMIN.
     */
    public function test_20_po_approval_remains_exclusive_to_admin(): void
    {
        // Admin can access PO approvals
        $this->actingAs($this->adminA)->get(route('admin.purchases.approvals.index'))->assertOk();

        // Gudang cannot access PO approvals
        $gudangUser = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang-po');
        $this->actingAs($gudangUser)->get(route('admin.purchases.approvals.index'))->assertForbidden();
    }

    /**
     * 21. Tidak ada mutasi bisnis akibat operasi GET.
     */
    public function test_21_get_endpoints_do_not_mutate_business_state(): void
    {
        $initialHistoryCount = OrderStatusHistory::count();
        $initialOrderCount = Order::count();
        $initialStatus = $this->orderA->fresh()->status;

        $this->actingAs($this->adminA)->get(route('admin.orders.index'));
        $this->actingAs($this->adminA)->get(route('admin.orders.show', $this->orderA));
        $this->actingAs($this->adminA)->get(route('admin.orders.edit', $this->orderA));
        $this->actingAs($this->adminA)->get(route('admin.orders.create'));

        $this->assertEquals($initialHistoryCount, OrderStatusHistory::count());
        $this->assertEquals($initialOrderCount, Order::count());
        $this->assertEquals($initialStatus, $this->orderA->fresh()->status);
    }

    // =========================================================================
    // SKENARIO 22 - 25: MODE FALSE, FAIL-CLOSED, DAN ANTI-LEAKAGE
    // =========================================================================

    /**
     * 22. Mode konfigurasi FALSE mempertahankan perilaku ERP lama secara kompatibel.
     */
    public function test_22_disabled_flag_preserves_legacy_erp_behavior(): void
    {
        // Deactivate tenant isolation enforcement
        config(['nayara.tenant_isolation_enabled' => false]);

        // Legacy admin without outlet can view all orders
        $legacyAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'legacy');

        $responseListing = $this->actingAs($legacyAdmin)->get(route('admin.orders.index'));
        $responseListing->assertOk();
        $responseListing->assertSee($this->orderA->order_no);
        $responseListing->assertSee($this->orderB->order_no);

        // Can view detail without outlet restriction
        $responseShow = $this->actingAs($legacyAdmin)->get(route('admin.orders.show', $this->orderB));
        $responseShow->assertOk();
        $responseShow->assertSee($this->orderB->order_no);
    }

    /**
     * 23. Mode TRUE gagal aman ketika tenant tidak dapat di-resolve.
     */
    public function test_23_isolation_mode_fails_closed_when_tenant_cannot_be_resolved(): void
    {
        // Unauthenticated access
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
        $this->get(route('admin.orders.show', $this->orderA))->assertRedirect(route('login'));

        // User without outlet
        $unboundAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'no-outlet');
        $this->actingAs($unboundAdmin)->get(route('admin.orders.index'))->assertForbidden();
    }

    /**
     * 24. Query helper Order::forOutlet() strictly isolates records at database level.
     */
    public function test_24_order_for_outlet_scope_strictly_isolates_records(): void
    {
        $scopedOrderIdsA = Order::query()->forOutlet($this->outletA->id)->pluck('id')->all();
        $scopedOrderIdsB = Order::query()->forOutlet($this->outletB->id)->pluck('id')->all();

        $this->assertContains($this->orderA->id, $scopedOrderIdsA);
        $this->assertNotContains($this->orderB->id, $scopedOrderIdsA);

        $this->assertContains($this->orderB->id, $scopedOrderIdsB);
        $this->assertNotContains($this->orderA->id, $scopedOrderIdsB);
    }

    /**
     * 25. Respons lintas-outlet tidak membocorkan informasi privat.
     */
    public function test_25_cross_tenant_response_never_leaks_private_information(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.show', $this->orderB));

        $response->assertNotFound();
        $response->assertDontSee($this->orderB->order_no);
        $response->assertDontSee('Customer Nayara B');
        $response->assertDontSee('Private client note for Outlet B');
        $response->assertDontSee('Emerald Green B');
    }
}
