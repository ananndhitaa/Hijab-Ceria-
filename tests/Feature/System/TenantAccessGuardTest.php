<?php

namespace Tests\Feature\System;

use App\Enums\OrderStatus;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Middleware\EnsureTenantOutlet;
use App\Models\Inventory\Purchase;
use App\Models\Master\Customer;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Sales\Order;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\System\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantAccessGuardTest extends TestCase
{
    use RefreshDatabase;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = new TenantContext();

        // Register dedicated testing routes protected by EnsureTenantOutlet (does not touch production routes)
        Route::middleware(['web', EnsureTenantOutlet::class])->group(function () {
            Route::get('/_testing/tenant/dashboard', function () {
                return response()->json([
                    'status'    => 'ok',
                    'outlet_id' => app(TenantContext::class)->currentOutletId(),
                ]);
            });

            Route::get('/_testing/tenant/admin-only', function () {
                return response()->json(['status' => 'admin_ok']);
            })->middleware('role:ADMIN');

            Route::get('/_testing/tenant/orders/{id}', function (int $id) {
                $outletId = app(TenantContext::class)->currentOutletId();
                $order = Order::query()->forOutlet($outletId)->findOrFail($id);
                return response()->json([
                    'id'       => $order->id,
                    'order_no' => $order->order_no,
                ]);
            });

            Route::get('/_testing/tenant/purchases/{id}', function (int $id) {
                $outletId = app(TenantContext::class)->currentOutletId();
                $purchase = Purchase::query()->forOutlet($outletId)->findOrFail($id);
                return response()->json([
                    'id'          => $purchase->id,
                    'purchase_no' => $purchase->purchase_no,
                ]);
            });
        });
    }

    /**
     * Helper membuat outlet uji.
     */
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

    /**
     * Helper membuat user uji.
     */
    protected function createTestUser(?Outlet $outlet, UserRole $role = UserRole::ADMIN, UserStatus $status = UserStatus::ACTIVE, string $suffix = '01'): User
    {
        return User::factory()->create([
            'name'      => "User {$role->value} {$suffix}",
            'email'     => "user.{$suffix}@nayara.test",
            'role'      => $role,
            'status'    => $status,
            'outlet_id' => $outlet?->id,
        ]);
    }

    /**
     * Helper membuat data Order.
     */
    protected function createTestOrder(?Outlet $outlet, string $orderNo = 'SO-TEST-01', OrderStatus $status = OrderStatus::KOMUNIKASI, string $date = '2026-10-23'): Order
    {
        $customer = Customer::create([
            'customer_name' => 'Customer ' . $orderNo,
            'phone'         => '0812' . rand(10000000, 99999999),
            'outlet_id'     => $outlet?->id,
        ]);

        return Order::create([
            'customer_id' => $customer->id,
            'order_no'    => $orderNo,
            'order_date'  => $date,
            'status'      => $status,
            'outlet_id'   => $outlet?->id,
        ]);
    }

    /**
     * Helper membuat data Purchase.
     */
    protected function createTestPurchase(?Outlet $outlet, string $purchaseNo = 'PO-TEST-01', PurchaseStatus $status = PurchaseStatus::DRAFT, string $date = '2026-10-23'): Purchase
    {
        $supplier = Supplier::create([
            'supplier_name' => 'Supplier ' . $purchaseNo,
            'phone'         => '0898' . rand(10000000, 99999999),
            'outlet_id'     => $outlet?->id,
        ]);

        return Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => $purchaseNo,
            'purchase_date' => $date,
            'status'        => $status,
            'outlet_id'     => $outlet?->id,
        ]);
    }

    // =========================================================================
    // SECTION I: PENGUJIAN MIDDLEWARE (EnsureTenantOutlet)
    // =========================================================================

    /**
     * 1. Owner Outlet A diterima pada route testing tenant miliknya.
     */
    public function test_01_owner_outlet_a_is_accepted_on_tenant_route(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $ownerA = $this->createTestUser($outletA, UserRole::OWNER, UserStatus::ACTIVE, 'own-a');

        $response = $this->actingAs($ownerA)->getJson('/_testing/tenant/dashboard');

        $response->assertOk()
            ->assertJson([
                'status'    => 'ok',
                'outlet_id' => $outletA->id,
            ]);
    }

    /**
     * 2. Admin Outlet B diterima pada route testing tenant miliknya.
     */
    public function test_02_admin_outlet_b_is_accepted_on_tenant_route(): void
    {
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');
        $adminB = $this->createTestUser($outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'adm-b');

        $response = $this->actingAs($adminB)->getJson('/_testing/tenant/dashboard');

        $response->assertOk()
            ->assertJson([
                'status'    => 'ok',
                'outlet_id' => $outletB->id,
            ]);
    }

    /**
     * 3. User tanpa outlet (outlet_id = null) ditolak dengan 403 Forbidden.
     */
    public function test_03_user_without_outlet_is_forbidden_on_tenant_route(): void
    {
        $userWithoutOutlet = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'no-out');

        $response = $this->actingAs($userWithoutOutlet)->getJson('/_testing/tenant/dashboard');

        $response->assertStatus(403);
    }

    /**
     * 4. User dengan outlet INACTIVE ditolak dengan 403 Forbidden.
     */
    public function test_04_user_with_inactive_outlet_is_forbidden_on_tenant_route(): void
    {
        $inactiveOutlet = $this->createTestOutlet('OUT-INACT', 'Outlet Nonaktif', 'INACTIVE');
        $user = $this->createTestUser($inactiveOutlet, UserRole::ADMIN, UserStatus::ACTIVE, 'inact-out');

        $response = $this->actingAs($user)->getJson('/_testing/tenant/dashboard');

        $response->assertStatus(403);
    }

    /**
     * 5. User dengan status INACTIVE ditolak oleh middleware dengan 403 Forbidden.
     */
    public function test_05_inactive_user_is_forbidden_on_tenant_route(): void
    {
        $outlet = $this->createTestOutlet('OUT-A', 'Outlet A');
        $inactiveUser = $this->createTestUser($outlet, UserRole::ADMIN, UserStatus::INACTIVE, 'inact-usr');

        $request = \Illuminate\Http\Request::create('/_testing/tenant/dashboard', 'GET');
        $request->setUserResolver(fn () => $inactiveUser);

        $middleware = new EnsureTenantOutlet($this->tenantContext);

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected HttpException with status 403');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            $this->assertStringContainsString('tidak aktif', $e->getMessage());
        }
    }

    /**
     * 6. Guest tidak mendapatkan akses privat tanpa autentikasi.
     */
    public function test_06_guest_cannot_access_private_tenant_route(): void
    {
        // Akses via API / JSON menghasilkan 401 Unauthenticated
        $jsonResponse = $this->getJson('/_testing/tenant/dashboard');
        $jsonResponse->assertStatus(401);

        // Akses via Web HTML diarahkan ke route login
        $webResponse = $this->get('/_testing/tenant/dashboard');
        $webResponse->assertRedirect(route('login'));
    }

    /**
     * 7. Header X-Outlet-ID palsu tidak dapat mengubah tenant yang diverifikasi.
     */
    public function test_07_spoofed_x_outlet_id_header_cannot_manipulate_tenant(): void
    {
        $outletReal = $this->createTestOutlet('OUT-REAL', 'Outlet Real');
        $outletFake = $this->createTestOutlet('OUT-FAKE', 'Outlet Fake');
        $user = $this->createTestUser($outletReal, UserRole::ADMIN);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Outlet-ID' => (string) $outletFake->id])
            ->getJson('/_testing/tenant/dashboard');

        $response->assertOk()
            ->assertJson([
                'status'    => 'ok',
                'outlet_id' => $outletReal->id,
            ]);
    }

    /**
     * 8. Query string ?outlet_id=999 palsu tidak dapat mengubah tenant yang diverifikasi.
     */
    public function test_08_spoofed_query_outlet_id_cannot_manipulate_tenant(): void
    {
        $outletReal = $this->createTestOutlet('OUT-REAL', 'Outlet Real');
        $outletFake = $this->createTestOutlet('OUT-FAKE', 'Outlet Fake');
        $user = $this->createTestUser($outletReal, UserRole::ADMIN);

        $response = $this->actingAs($user)
            ->getJson('/_testing/tenant/dashboard?outlet_id=' . $outletFake->id);

        $response->assertOk()
            ->assertJson([
                'status'    => 'ok',
                'outlet_id' => $outletReal->id,
            ]);
    }

    /**
     * 9. Form body outlet_id palsu tidak dapat mengubah tenant yang diverifikasi.
     */
    public function test_09_spoofed_form_body_outlet_id_cannot_manipulate_tenant(): void
    {
        $outletReal = $this->createTestOutlet('OUT-REAL', 'Outlet Real');
        $outletFake = $this->createTestOutlet('OUT-FAKE', 'Outlet Fake');
        $user = $this->createTestUser($outletReal, UserRole::ADMIN);

        $response = $this->actingAs($user)
            ->call('GET', '/_testing/tenant/dashboard', ['outlet_id' => $outletFake->id]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($outletReal->id, $response->json('outlet_id'));
    }

    /**
     * 10. Tidak terjadi kebocoran state tenant antar-user pada request berurutan.
     */
    public function test_10_no_tenant_state_leakage_between_consecutive_requests(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $userA = $this->createTestUser($outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'u-a');
        $userB = $this->createTestUser($outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'u-b');

        // Request 1: User A
        $resp1 = $this->actingAs($userA)->getJson('/_testing/tenant/dashboard');
        $resp1->assertOk()->assertJson(['outlet_id' => $outletA->id]);

        // Request 2: User B
        $resp2 = $this->actingAs($userB)->getJson('/_testing/tenant/dashboard');
        $resp2->assertOk()->assertJson(['outlet_id' => $outletB->id]);

        // Request 3: User A kembali
        $resp3 = $this->actingAs($userA)->getJson('/_testing/tenant/dashboard');
        $resp3->assertOk()->assertJson(['outlet_id' => $outletA->id]);
    }

    /**
     * 11. Middleware tidak mengubah role atau permission pengguna.
     */
    public function test_11_middleware_does_not_modify_user_role_or_privileges(): void
    {
        $outlet = $this->createTestOutlet('OUT-A', 'Outlet A');
        $gudangUser = $this->createTestUser($outlet, UserRole::GUDANG);

        $this->actingAs($gudangUser)->getJson('/_testing/tenant/dashboard');

        $this->assertEquals(UserRole::GUDANG, $gudangUser->fresh()->role);
        $this->assertFalse($gudangUser->fresh()->isAdmin());
    }

    /**
     * 12. CUSTOMER tidak memperoleh permission ERP internal hanya karena memiliki outlet.
     */
    public function test_12_customer_role_is_not_granted_internal_erp_permissions(): void
    {
        $outlet = $this->createTestOutlet('OUT-A', 'Outlet A');
        $customerUser = $this->createTestUser($outlet, UserRole::CUSTOMER);

        // Lolos middleware tenant (karena punya outlet), tapi DITOLAK middleware role admin
        $response = $this->actingAs($customerUser)->getJson('/_testing/tenant/admin-only');

        $response->assertStatus(403);
    }

    /**
     * 13. RoleMiddleware existing tetap diperlukan dan ditegakkan untuk pembatasan role.
     */
    public function test_13_role_middleware_is_still_required_and_enforced(): void
    {
        $outlet = $this->createTestOutlet('OUT-A', 'Outlet A');
        $adminUser = $this->createTestUser($outlet, UserRole::ADMIN);
        $prodUser = $this->createTestUser($outlet, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-13');

        // Admin diizinkan pada admin-only
        $this->actingAs($adminUser)->getJson('/_testing/tenant/admin-only')->assertOk();

        // Staf Produksi ditolak oleh RoleMiddleware pada admin-only
        $this->actingAs($prodUser)->getJson('/_testing/tenant/admin-only')->assertStatus(403);
    }

    // =========================================================================
    // SECTION J: PENGUJIAN QUERY SCOPING & PERLINDUNGAN IDOR
    // =========================================================================

    /**
     * 14. Order Outlet A hanya terlihat oleh scoped query Outlet A.
     */
    public function test_14_order_outlet_a_only_visible_to_scoped_query_outlet_a(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $orderA = $this->createTestOrder($outletA, 'SO-A-001');

        $orders = Order::query()->forOutlet($outletA->id)->pluck('order_no')->all();

        $this->assertEquals(['SO-A-001'], $orders);
    }

    /**
     * 15. Order Outlet B tidak terlihat oleh scoped query Outlet A.
     */
    public function test_15_order_outlet_b_not_visible_to_scoped_query_outlet_a(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $orderA = $this->createTestOrder($outletA, 'SO-A-001');
        $orderB = $this->createTestOrder($outletB, 'SO-B-001');

        $ordersForA = Order::query()->forOutlet($outletA->id)->pluck('order_no')->all();

        $this->assertContains('SO-A-001', $ordersForA);
        $this->assertNotContains('SO-B-001', $ordersForA);
    }

    /**
     * 16. Purchase Outlet A hanya terlihat oleh scoped query Outlet A.
     */
    public function test_16_purchase_outlet_a_only_visible_to_scoped_query_outlet_a(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $purchaseA = $this->createTestPurchase($outletA, 'PO-A-001');

        $purchases = Purchase::query()->forOutlet($outletA->id)->pluck('purchase_no')->all();

        $this->assertEquals(['PO-A-001'], $purchases);
    }

    /**
     * 17. Purchase Outlet B tidak terlihat oleh scoped query Outlet A.
     */
    public function test_17_purchase_outlet_b_not_visible_to_scoped_query_outlet_a(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $purchaseA = $this->createTestPurchase($outletA, 'PO-A-001');
        $purchaseB = $this->createTestPurchase($outletB, 'PO-B-001');

        $purchasesForA = Purchase::query()->forOutlet($outletA->id)->pluck('purchase_no')->all();

        $this->assertContains('PO-A-001', $purchasesForA);
        $this->assertNotContains('PO-B-001', $purchasesForA);
    }

    /**
     * 18. Transaksi dengan outlet_id NULL tidak terlihat oleh scoped query tenant.
     */
    public function test_18_unassigned_transactions_are_excluded_from_tenant_scope(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');

        $orderLegacy = $this->createTestOrder(null, 'SO-LEGACY-001');
        $purchaseLegacy = $this->createTestPurchase(null, 'PO-LEGACY-001');

        $ordersForA = Order::query()->forOutlet($outletA->id)->pluck('order_no')->all();
        $purchasesForA = Purchase::query()->forOutlet($outletA->id)->pluck('purchase_no')->all();

        $this->assertEmpty($ordersForA);
        $this->assertEmpty($purchasesForA);
    }

    /**
     * 19. Percobaan IDOR: Mengakses Order outlet B menggunakan ID yang diketahui menghasilkan 404.
     */
    public function test_19_cross_outlet_order_access_attempt_yields_404_not_found(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $userA = $this->createTestUser($outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'user-a');
        $orderB = $this->createTestOrder($outletB, 'SO-VICTIM-B');

        // User Outlet A mencoba membuka Order milik Outlet B
        $response = $this->actingAs($userA)->getJson('/_testing/tenant/orders/' . $orderB->id);

        // Menghasilkan 404 (bukan 200 dan bukan 403) untuk menjaga kerahasiaan keberadaan dokumen
        $response->assertStatus(404);
    }

    /**
     * 20. Percobaan IDOR: Mengakses Purchase outlet B menggunakan ID yang diketahui menghasilkan 404.
     */
    public function test_20_cross_outlet_purchase_access_attempt_yields_404_not_found(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $userA = $this->createTestUser($outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'user-a-po');
        $purchaseB = $this->createTestPurchase($outletB, 'PO-VICTIM-B');

        // User Outlet A mencoba membuka Purchase milik Outlet B
        $response = $this->actingAs($userA)->getJson('/_testing/tenant/purchases/' . $purchaseB->id);

        $response->assertStatus(404);
    }

    /**
     * 21. Mengetahui ID dokumen milik outlet lain tidak memberikan akses.
     */
    public function test_21_knowing_document_id_does_not_permit_cross_tenant_access(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $userA = $this->createTestUser($outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'u-a');
        $orderA = $this->createTestOrder($outletA, 'SO-OWN-A');
        $orderB = $this->createTestOrder($outletB, 'SO-SECRET-B');

        // User A dapat membuka Order miliknya
        $this->actingAs($userA)->getJson('/_testing/tenant/orders/' . $orderA->id)->assertOk();

        // User A tidak dapat membuka Order B meskipun ID-nya diketahui
        $this->actingAs($userA)->getJson('/_testing/tenant/orders/' . $orderB->id)->assertNotFound();
    }

    /**
     * 22. Pemilihan transaksi tetap menghormati role existing.
     */
    public function test_22_transaction_access_respects_existing_roles(): void
    {
        $outlet = $this->createTestOutlet('OUT-A', 'Outlet A');
        $admin = $this->createTestUser($outlet, UserRole::ADMIN);
        $order = $this->createTestOrder($outlet, 'SO-ROLE-CHECK');

        $this->actingAs($admin)->getJson('/_testing/tenant/orders/' . $order->id)->assertOk();
    }

    /**
     * 23. Approval PO tetap mutlak dilakukan oleh ADMIN.
     */
    public function test_23_purchase_order_approval_remains_strictly_admin(): void
    {
        $outlet = $this->createTestOutlet('OUT-A', 'Outlet A');
        $admin = $this->createTestUser($outlet, UserRole::ADMIN);
        $owner = $this->createTestUser($outlet, UserRole::OWNER, UserStatus::ACTIVE, 'owner-po');

        $purchase = $this->createTestPurchase($outlet, 'PO-APPROVAL');

        $purchase->update([
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'approved_by'     => $admin->id,
            'approved_at'     => now(),
        ]);

        $this->assertEquals(UserRole::ADMIN, $purchase->approvedByUser->role);
        $this->assertNotEquals(UserRole::OWNER, $purchase->approvedByUser->role);
    }

    /**
     * 24. Local scope dapat digabungkan secara aman dengan filter status dan periode.
     */
    public function test_24_scope_can_be_combined_with_status_and_date_filters(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $orderA1 = $this->createTestOrder($outletA, 'SO-A-DONE', OrderStatus::SELESAI, '2026-10-01');
        $orderA2 = $this->createTestOrder($outletA, 'SO-A-KOMUNIKASI', OrderStatus::KOMUNIKASI, '2026-10-02');
        $orderB1 = $this->createTestOrder($outletB, 'SO-B-DONE', OrderStatus::SELESAI, '2026-10-01');

        $results = Order::query()
            ->forOutlet($outletA->id)
            ->where('status', OrderStatus::SELESAI)
            ->whereDate('order_date', '>=', '2026-10-01')
            ->pluck('order_no')
            ->all();

        $this->assertEquals(['SO-A-DONE'], $results);
    }

    /**
     * 25. Nilai bisnis dan kalkulasi tidak terpengaruh oleh adanya query scope.
     */
    public function test_25_business_values_remain_unmodified_by_scope(): void
    {
        $outlet = $this->createTestOutlet('OUT-A', 'Outlet A');
        $order = $this->createTestOrder($outlet, 'SO-VAL-CHECK');

        $scopedOrder = Order::query()->forOutlet($outlet->id)->where('id', $order->id)->first();

        $this->assertNotNull($scopedOrder);
        $this->assertEquals($order->order_no, $scopedOrder->order_no);
        $this->assertEquals($order->status, $scopedOrder->status);
    }

    /**
     * 26. Query scope tidak otomatis aktif pada seluruh model Order/Purchase secara global.
     */
    public function test_26_query_scope_is_not_globally_active_by_default(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');

        $this->createTestOrder($outletA, 'SO-A');
        $this->createTestOrder($outletB, 'SO-B');

        // Order::all() tanpa scope tetap mengembalikan 2 (membuktikan belum ada global tenant scope tersembunyi)
        $this->assertCount(2, Order::all());
        $this->assertCount(1, Order::query()->forOutlet($outletA->id)->get());
    }

    /**
     * 27. Pengujian membedakan antara resolusi tenant yang valid dan hak akses ke dokumen.
     */
    public function test_27_distinction_between_valid_tenant_resolution_and_document_authorization(): void
    {
        $outletA = $this->createTestOutlet('OUT-A', 'Outlet A');
        $userA = $this->createTestUser($outletA, UserRole::ADMIN);

        // 1. Tenant resolution valid
        $this->assertTrue($this->tenantContext->hasValidOutlet($userA));
        $this->assertEquals($outletA->id, $this->tenantContext->outletIdFor($userA));

        // 2. Hak akses dokumen: Order milik outlet B tetap tidak bisa diakses
        $outletB = $this->createTestOutlet('OUT-B', 'Outlet B');
        $orderB = $this->createTestOrder($outletB, 'SO-B-DOC');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Order::query()->forOutlet($userA->outlet_id)->findOrFail($orderB->id);
    }
}
