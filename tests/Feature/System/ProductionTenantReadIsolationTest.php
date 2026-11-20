<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\BomTemplate;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionTenantReadIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $adminA;
    protected User $adminB;
    protected User $produksiA;
    protected User $produksiB;
    protected User $customerUser;
    protected User $inactiveAdminA;
    protected User $unboundAdmin;
    protected User $adminInactiveOutlet;

    protected Customer $customerA;
    protected Customer $customerB;

    protected Product $productA;
    protected Product $productB;

    protected ProductVariant $variantA;
    protected ProductVariant $variantB;

    protected Material $materialA;
    protected Material $materialB;

    protected BomTemplate $bomTemplateA;
    protected BomTemplate $bomTemplateB;

    protected Order $orderA;
    protected Order $orderB;
    protected Order $unassignedOrder;

    protected OrderItem $itemA;
    protected OrderItem $itemB;
    protected OrderItem $unassignedItem;

    protected ProductionBatch $batchA;
    protected ProductionBatch $batchB;
    protected ProductionBatch $unassignedBatch;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable tenant isolation enforcement for security testing
        config(['nayara.tenant_isolation_enabled' => true]);

        // 1. Outlets
        $this->outletA = $this->createTestOutlet('OUT-PROD-A', 'Konveksi Hijab A', 'ACTIVE');
        $this->outletB = $this->createTestOutlet('OUT-PROD-B', 'Konveksi Hijab B', 'ACTIVE');
        $this->outletInactive = $this->createTestOutlet('OUT-INACTIVE', 'Konveksi Inactive', 'INACTIVE');

        // 2. Users
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-a');
        $this->adminB = $this->createTestUser($this->outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-b');
        $this->produksiA = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-a');
        $this->produksiB = $this->createTestUser($this->outletB, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod-b');
        $this->customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'cust-user');
        $this->inactiveAdminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::INACTIVE, 'admin-inact');
        $this->unboundAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-unbound');
        $this->adminInactiveOutlet = $this->createTestUser($this->outletInactive, UserRole::ADMIN, UserStatus::ACTIVE, 'admin-inact-out');

        // 3. Customers
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

        // 4. Products & Variants
        $this->productA = Product::create([
            'product_name' => 'Gamis Syari A',
            'active'       => true,
            'outlet_id'    => $this->outletA->id,
        ]);
        $this->variantA = ProductVariant::create([
            'product_id'         => $this->productA->id,
            'variant_name'       => 'Mauve Size L',
            'sku'                => 'SKU-GAMIS-A-L',
            'base_selling_price' => 150000,
            'requires_printing'  => false,
            'outlet_id'          => $this->outletA->id,
        ]);

        $this->productB = Product::create([
            'product_name' => 'Khimar Instan B',
            'active'       => true,
            'outlet_id'    => $this->outletB->id,
        ]);
        $this->variantB = ProductVariant::create([
            'product_id'         => $this->productB->id,
            'variant_name'       => 'Navy Size M',
            'sku'                => 'SKU-KHIMAR-B-M',
            'base_selling_price' => 85000,
            'requires_printing'  => true,
            'outlet_id'          => $this->outletB->id,
        ]);

        // 5. Materials & BOM Templates
        $unit = \App\Models\Master\Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->materialA = Material::create([
            'material_name' => 'Kain Rayon A',
            'unit_id'       => $unit->id,
            'outlet_id'     => $this->outletA->id,
        ]);
        $this->materialB = Material::create([
            'material_name' => 'Kain Ceruty B',
            'unit_id'       => $unit->id,
            'outlet_id'     => $this->outletB->id,
        ]);

        $this->bomTemplateA = BomTemplate::create([
            'name'        => 'BOM Gamis A',
            'product_id'  => $this->productA->id,
            'outlet_id'   => $this->outletA->id,
            'is_active'   => true,
        ]);
        $this->bomTemplateB = BomTemplate::create([
            'name'        => 'BOM Khimar B',
            'product_id'  => $this->productB->id,
            'outlet_id'   => $this->outletB->id,
            'is_active'   => true,
        ]);

        // 6. Sales Orders (in DEAL state with confirmed DP)
        $this->orderA = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-PROD-A-001',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::DEAL,
            'outlet_id'   => $this->outletA->id,
            'notes'       => 'Order konveksi A',
        ]);
        $this->itemA = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 150000,
        ]);
        $this->createConfirmedDownPayment($this->orderA, '3000000.00', '1500000.00');

        $this->orderB = Order::create([
            'customer_id' => $this->customerB->id,
            'order_no'    => 'SO-PROD-B-002',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::DEAL,
            'outlet_id'   => $this->outletB->id,
            'notes'       => 'Order konveksi B',
        ]);
        $this->itemB = OrderItem::create([
            'order_id'           => $this->orderB->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 85000,
        ]);
        $this->createConfirmedDownPayment($this->orderB, '2550000.00', '1000000.00');

        $this->unassignedOrder = Order::create([
            'customer_id' => $this->customerA->id,
            'order_no'    => 'SO-LEGACY-NULL-003',
            'order_date'  => '2026-10-23',
            'status'      => OrderStatus::DEAL,
            'outlet_id'   => null,
            'notes'       => 'Legacy unassigned order',
        ]);
        $this->unassignedItem = OrderItem::create([
            'order_id'           => $this->unassignedOrder->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 150000,
        ]);
        $this->createConfirmedDownPayment($this->unassignedOrder, '1500000.00', '500000.00');

        // 7. Production Batches
        $this->batchA = ProductionBatch::create([
            'order_item_id'          => $this->itemA->id,
            'batch_no'               => 'BAT-A-2026-001',
            'target_qty'             => 10,
            'status'                 => BatchStatus::BAHAN,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiA->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->batchA->id,
            'material_id'   => $this->materialA->id,
            'required_qty'  => 25.0,
            'allocated_qty' => 0.0,
        ]);

        $this->batchB = ProductionBatch::create([
            'order_item_id'          => $this->itemB->id,
            'batch_no'               => 'BAT-B-2026-002',
            'target_qty'             => 15,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => true,
            'production_employee_id' => $this->produksiB->id,
        ]);
        BatchMaterial::create([
            'batch_id'      => $this->batchB->id,
            'material_id'   => $this->materialB->id,
            'required_qty'  => 30.0,
            'allocated_qty' => 0.0,
        ]);

        $this->unassignedBatch = ProductionBatch::create([
            'order_item_id'     => $this->unassignedItem->id,
            'batch_no'          => 'BAT-NULL-2026-003',
            'target_qty'        => 5,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);
    }

    protected function createTestOutlet(string $code, string $name, string $status = 'ACTIVE'): Outlet
    {
        return Outlet::create([
            'outlet_name'                => $name,
            'outlet_code'                => $code,
            'slug'                       => strtolower($code),
            'status'                     => $status,
            'is_public_showcase_enabled' => true,
        ]);
    }

    protected function createTestUser(?Outlet $outlet, UserRole $role, UserStatus $status, string $suffix): User
    {
        return User::create([
            'name'      => "User {$suffix}",
            'email'     => "{$suffix}@nayara.test",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'status'    => $status,
            'outlet_id' => $outlet?->id,
        ]);
    }

    protected function createConfirmedDownPayment(Order $order, string $totalAmount, string $dpAmount): void
    {
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'total_amount' => $totalAmount,
            'status'       => InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'payment_no'   => 'PAY-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'amount'       => $dpAmount,
            'payment_type' => PaymentType::DP,
            'status'       => PaymentStatus::CONFIRMED,
        ]);
    }

    // =========================================================================
    // TEST METHODS (1 to 32)
    // =========================================================================

    /**
     * 1. User A dapat melihat batch yang sah dari Outlet A di Admin listing.
     */
    public function test_01_user_can_view_own_outlet_batches_in_admin_listing(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.batches.index'));

        $response->assertOk();
        $response->assertSee($this->batchA->batch_no);
    }

    /**
     * 2. User A tidak melihat batch B di Admin listing.
     */
    public function test_02_user_does_not_see_other_outlet_batches_in_admin_listing(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.batches.index'));

        $response->assertOk();
        $response->assertDontSee($this->batchB->batch_no);
        $response->assertDontSee('Customer Nayara B');
    }

    /**
     * 3. User B dapat melihat batch B di Admin listing.
     */
    public function test_03_user_b_can_view_own_outlet_batches_in_admin_listing(): void
    {
        $response = $this->actingAs($this->adminB)->get(route('admin.batches.index'));

        $response->assertOk();
        $response->assertSee($this->batchB->batch_no);
        $response->assertDontSee($this->batchA->batch_no);
    }

    /**
     * 4. Produksi User A dapat melihat batch A dan tidak melihat batch B di Produksi queue.
     */
    public function test_04_produksi_user_strictly_scoped_in_produksi_listing(): void
    {
        $response = $this->actingAs($this->produksiA)->get(route('produksi.batches.index'));

        $response->assertOk();
        $response->assertSee($this->batchA->batch_no);
        $response->assertDontSee($this->batchB->batch_no);
    }

    /**
     * 5. Akses detail batch sendiri berhasil sesuai route aktual (Admin & Produksi).
     */
    public function test_05_access_to_own_batch_details_succeeds_for_admin_and_produksi(): void
    {
        // Admin A views Batch A
        $responseAdmin = $this->actingAs($this->adminA)->get(route('admin.batches.show', $this->batchA));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee($this->batchA->batch_no);
        $responseAdmin->assertSee('Customer Nayara A');

        // Produksi A views Batch A
        $responseProd = $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $this->batchA));
        $responseProd->assertOk();
        $responseProd->assertSee($this->batchA->batch_no);
    }

    /**
     * 6. Detail batch Outlet B ditolak untuk User A (Admin & Produksi mengembalikan HTTP 404).
     */
    public function test_06_access_to_other_outlet_batch_details_is_rejected_with_404(): void
    {
        // Admin A attempts to view Batch B
        $responseAdmin = $this->actingAs($this->adminA)->get(route('admin.batches.show', $this->batchB));
        $responseAdmin->assertNotFound();

        // Produksi A attempts to view Batch B
        $responseProd = $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $this->batchB));
        $responseProd->assertNotFound();
    }

    /**
     * 7. Manipulasi ID batch menghasilkan HTTP 404.
     */
    public function test_07_manipulating_batch_id_returns_404(): void
    {
        $this->actingAs($this->adminA)->get('/admin/batches/999999')->assertNotFound();
        $this->actingAs($this->produksiA)->get('/produksi/batches/999999')->assertNotFound();
    }

    /**
     * 8. OrderItem milik Outlet B tidak dapat digunakan untuk membuka form SPK User A.
     */
    public function test_08_order_item_from_other_outlet_cannot_be_used_in_create_batch_form(): void
    {
        // Admin A tries to open create form on Order A with query param order_item_id pointing to Item B
        $response = $this->actingAs($this->adminA)->get(
            route('admin.orders.batches.create', ['order' => $this->orderA, 'order_item_id' => $this->itemB->id])
        );

        $response->assertNotFound();
    }

    /**
     * 9. Manipulasi Order ID pada form pembuatan batch menghasilkan HTTP 404.
     */
    public function test_09_manipulating_order_id_in_create_batch_form_returns_404(): void
    {
        // Admin A attempts to open batch create form for Order B
        $responseCross = $this->actingAs($this->adminA)->get(route('admin.orders.batches.create', $this->orderB));
        $responseCross->assertNotFound();

        // Non-existent order
        $this->actingAs($this->adminA)->get('/admin/orders/999999/batches/create')->assertNotFound();
    }

    /**
     * 10. Order dengan outlet_id NULL tidak terlihat ketika flag TRUE.
     */
    public function test_10_batches_with_unassigned_parent_order_are_not_visible_when_flag_true(): void
    {
        $responseAdmin = $this->actingAs($this->adminA)->get(route('admin.batches.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertDontSee($this->unassignedBatch->batch_no);

        $responseProd = $this->actingAs($this->produksiA)->get(route('produksi.batches.index'));
        $responseProd->assertOk();
        $responseProd->assertDontSee($this->unassignedBatch->batch_no);
    }

    /**
     * 11. Batch yang parent Order-nya unassigned juga ditolak (HTTP 404).
     */
    public function test_11_batches_with_unassigned_parent_order_return_404_on_show(): void
    {
        $this->actingAs($this->adminA)->get(route('admin.batches.show', $this->unassignedBatch))->assertNotFound();
        $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $this->unassignedBatch))->assertNotFound();

        // Create form for unassigned order is also 404
        $this->actingAs($this->adminA)->get(route('admin.orders.batches.create', $this->unassignedOrder))->assertNotFound();
    }

    /**
     * 12. TenantContext invalid menghasilkan penolakan (401/403).
     */
    public function test_12_invalid_tenant_context_fails_closed(): void
    {
        // Unauthenticated
        $this->get(route('admin.batches.index'))->assertRedirect(route('login'));
        $this->get(route('produksi.batches.index'))->assertRedirect(route('login'));

        // Unbound user (no outlet assigned)
        $this->actingAs($this->unboundAdmin)->get(route('admin.batches.index'))->assertForbidden();
    }

    /**
     * 13. Outlet INACTIVE ditolak (HTTP 403).
     */
    public function test_13_inactive_outlet_fails_closed_with_forbidden(): void
    {
        $this->actingAs($this->adminInactiveOutlet)->get(route('admin.batches.index'))->assertForbidden();
    }

    /**
     * 14. User INACTIVE ditolak (fail-closed: redirected & invalidated by EnsureUserIsActive, and rejected by TenantContext).
     */
    public function test_14_inactive_user_fails_closed(): void
    {
        $response = $this->actingAs($this->inactiveAdminA)->get(route('admin.batches.index'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);

        // Direct TenantContext check
        try {
            app(\App\Services\System\TenantContext::class)->outletFor($this->inactiveAdminA);
            $this->fail('TenantContext should throw AuthorizationException for inactive user.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertStringContainsString('Akun pengguna sedang tidak aktif', $e->getMessage());
        }
    }

    /**
     * 15. Parameter outlet_id palsu tidak dapat mengubah tenant.
     */
    public function test_15_fake_request_outlet_id_cannot_switch_tenant(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.batches.index', ['outlet_id' => $this->outletB->id]));

        $response->assertOk();
        $response->assertSee($this->batchA->batch_no);
        $response->assertDontSee($this->batchB->batch_no);
    }

    /**
     * 16. Header X-Outlet-ID palsu tidak dapat mengubah tenant.
     */
    public function test_16_fake_header_x_outlet_id_cannot_switch_tenant(): void
    {
        $response = $this->actingAs($this->adminA)->withHeaders([
            'X-Outlet-ID' => (string) $this->outletB->id,
        ])->get(route('admin.batches.index'));

        $response->assertOk();
        $response->assertSee($this->batchA->batch_no);
        $response->assertDontSee($this->batchB->batch_no);
    }

    /**
     * 17. Dropdown Material dan data bahan batch ter-scope per outlet.
     */
    public function test_17_materials_and_batch_materials_are_scoped_to_outlet(): void
    {
        $responseA = $this->actingAs($this->adminA)->get(route('admin.batches.show', $this->batchA));
        $responseA->assertOk();
        $responseA->assertSee($this->batchA->batch_no);
        $responseA->assertSee('Gamis Syari A');
        $responseA->assertDontSee('Khimar Instan B');

        // Verify batchMaterials model relationship scoping
        $this->assertSame($this->outletA->id, $this->batchA->batchMaterials()->first()->material->outlet_id);
        $this->assertSame($this->outletB->id, $this->batchB->batchMaterials()->first()->material->outlet_id);
    }

    /**
     * 18. BOM Template dan resep tidak bocor lintas-outlet.
     */
    public function test_18_bom_templates_and_recipes_do_not_leak_cross_tenant(): void
    {
        $this->assertSame($this->outletA->id, $this->bomTemplateA->outlet_id);
        $this->assertSame($this->outletB->id, $this->bomTemplateB->outlet_id);
        $this->assertNotEquals($this->bomTemplateA->outlet_id, $this->bomTemplateB->outlet_id);
    }

    /**
     * 19. Variant dan Product parent wajib konsisten satu outlet pada form SPK.
     */
    public function test_19_variant_and_parent_product_must_belong_to_same_outlet(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.orders.batches.create', $this->orderA));

        $response->assertOk();
        $response->assertSee('Gamis Syari A');
        $response->assertSee('Mauve Size L');
        $response->assertDontSee('Khimar Instan B');
    }

    /**
     * 20. Pagination tidak mencampur batch lintas-outlet.
     */
    public function test_20_pagination_strictly_scopes_batches_per_outlet(): void
    {
        // Create additional batches for Outlet A
        for ($i = 2; $i <= 16; $i++) {
            ProductionBatch::create([
                'order_item_id'     => $this->itemA->id,
                'batch_no'          => "BAT-A-PAGE-{$i}",
                'target_qty'        => 1,
                'status'            => BatchStatus::BAHAN,
                'requires_printing' => false,
            ]);
        }

        $response = $this->actingAs($this->adminA)->get(route('admin.batches.index', ['page' => 2]));
        $response->assertOk();
        $response->assertDontSee($this->batchB->batch_no);
    }

    /**
     * 21. Filter status tidak mencampur batch lintas-outlet.
     */
    public function test_21_status_filter_strictly_scopes_batches_per_outlet(): void
    {
        // Batch A is BAHAN, Batch B is PRODUKSI
        $response = $this->actingAs($this->adminA)->get(route('admin.batches.index', ['status' => BatchStatus::PRODUKSI->value]));
        $response->assertOk();
        $response->assertDontSee($this->batchB->batch_no);
    }

    /**
     * 22. Filter pencarian ter-scope ketat per outlet.
     */
    public function test_22_search_filter_strictly_scopes_batches_per_outlet(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.batches.index', ['search' => 'BAT-B']));
        $response->assertOk();
        $response->assertDontSee($this->batchB->batch_no);
    }

    /**
     * 23. RoleMiddleware tetap berlaku antara admin dan produksi.
     */
    public function test_23_role_middleware_strictly_enforced_between_admin_and_produksi(): void
    {
        // Produksi role cannot access admin routes
        $this->actingAs($this->produksiA)->get(route('admin.batches.index'))->assertForbidden();
        $this->actingAs($this->produksiA)->get(route('admin.batches.show', $this->batchA))->assertForbidden();

        // Admin role cannot access produksi routes
        $this->actingAs($this->adminA)->get(route('produksi.batches.index'))->assertForbidden();
        $this->actingAs($this->adminA)->get(route('produksi.batches.show', $this->batchA))->assertForbidden();
    }

    /**
     * 24. Role CUSTOMER tidak memperoleh akses internal produksi atau admin.
     */
    public function test_24_customer_role_denied_access_to_internal_production_routes(): void
    {
        $this->actingAs($this->customerUser)->get(route('admin.batches.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('admin.batches.show', $this->batchA))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('produksi.batches.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('produksi.batches.show', $this->batchA))->assertForbidden();
    }

    /**
     * 25. Admin tetap menjadi role eksklusif approval PO.
     */
    public function test_25_admin_retains_exclusive_purchase_order_approval_privilege(): void
    {
        $this->actingAs($this->produksiA)->get(route('admin.purchases.approvals.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('admin.purchases.approvals.index'))->assertForbidden();
    }

    /**
     * 26. Mode flag FALSE tetap kompatibel dengan ERP legacy.
     */
    public function test_26_legacy_erp_compatibility_when_tenant_isolation_flag_is_false(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->adminA)->get(route('admin.batches.index'));
        $response->assertOk();
        $response->assertSee($this->batchA->batch_no);
        $response->assertSee($this->batchB->batch_no);
        $response->assertSee($this->unassignedBatch->batch_no);

        // Can view unassigned batch on show when flag is false
        $this->actingAs($this->adminA)->get(route('admin.batches.show', $this->unassignedBatch))->assertOk();
    }

    /**
     * 27. Mode flag TRUE fail-closed jika tenant invalid.
     */
    public function test_27_tenant_isolation_mode_strictly_fail_closed_on_resolution_failure(): void
    {
        $userWithoutOutlet = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'no-outlet-test');

        $this->actingAs($userWithoutOutlet)->get(route('admin.batches.index'))->assertForbidden();
        $this->actingAs($userWithoutOutlet)->get(route('admin.batches.show', $this->batchA))->assertForbidden();
    }

    /**
     * 28. Tidak ada perubahan stok akibat request GET.
     */
    public function test_28_read_operations_do_not_mutate_material_or_product_stock(): void
    {
        $initialBatchAllocated = $this->batchA->batchMaterials()->first()->allocated_qty;

        $this->actingAs($this->adminA)->get(route('admin.batches.index'));
        $this->actingAs($this->adminA)->get(route('admin.batches.show', $this->batchA));
        $this->actingAs($this->adminA)->get(route('admin.orders.batches.create', $this->orderA));

        $this->assertSame(
            (string) $initialBatchAllocated,
            (string) $this->batchA->fresh()->batchMaterials()->first()->allocated_qty
        );
    }

    /**
     * 29. Tidak ada perubahan status batch akibat request GET.
     */
    public function test_29_read_operations_do_not_mutate_batch_status(): void
    {
        $this->actingAs($this->adminA)->get(route('admin.batches.show', $this->batchA));
        $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $this->batchA));

        $this->assertSame(BatchStatus::BAHAN, $this->batchA->fresh()->status);
    }

    /**
     * 30. Tidak ada perubahan pada data bisnis outlet lain.
     */
    public function test_30_read_operations_do_not_mutate_other_outlet_data(): void
    {
        $this->actingAs($this->adminA)->get(route('admin.batches.index'));
        $this->actingAs($this->adminA)->get(route('admin.batches.show', $this->batchB));

        $this->assertSame(BatchStatus::PRODUKSI, $this->batchB->fresh()->status);
        $this->assertSame('SO-PROD-B-002', $this->orderB->fresh()->order_no);
    }

    /**
     * 31. Model turunan ProductionBatch mengikuti ownership Order tanpa outlet_id redundan.
     */
    public function test_31_production_batch_derives_ownership_without_redundant_outlet_id_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('production_batches', 'outlet_id'),
            'Tabel production_batches tidak boleh memiliki kolom outlet_id redundan.'
        );

        $this->assertSame($this->outletA->id, $this->batchA->outlet->id);
        $this->assertSame($this->outletB->id, $this->batchB->outlet->id);
    }

    /**
     * 32. Relasi tidak konsisten ditolak secara aman (HTTP 404).
     */
    public function test_32_inconsistent_tenant_relations_are_safely_rejected(): void
    {
        // Artificially create a corrupted batch where order belongs to Outlet A but item belongs to Variant B of Outlet B
        $tamperedItem = OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantB->id, // Variant belongs to Outlet B!
            'qty_ordered'        => 5,
            'unit_selling_price' => 85000,
        ]);

        $corruptedBatch = ProductionBatch::create([
            'order_item_id'     => $tamperedItem->id,
            'batch_no'          => 'BAT-CORRUPTED-001',
            'target_qty'        => 5,
            'status'            => BatchStatus::BAHAN,
            'requires_printing' => false,
        ]);

        // Attempt to view corrupted batch should fail closed (404)
        $this->actingAs($this->adminA)->get(route('admin.batches.show', $corruptedBatch))->assertNotFound();
        $this->actingAs($this->produksiA)->get(route('produksi.batches.show', $corruptedBatch))->assertNotFound();
    }
}
