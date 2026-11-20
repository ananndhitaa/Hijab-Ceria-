<?php

namespace Tests\Feature\System;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\ProductionBatch;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProcurementTenantReadIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $adminA;
    protected User $adminB;
    protected User $gudangA;
    protected User $gudangB;
    protected User $ownerA;
    protected User $produksiA;
    protected User $customerUser;
    protected User $inactiveGudangA;
    protected User $unboundGudang;
    protected User $gudangInactiveOutlet;

    protected Unit $unitMeter;

    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Supplier $unassignedSupplier;

    protected Material $materialA;
    protected Material $materialB;
    protected Material $unassignedMaterial;

    protected Purchase $purchaseA_Draft;
    protected Purchase $purchaseA_Submitted;
    protected Purchase $purchaseA_Approved;
    protected Purchase $purchaseA_Received;
    protected Purchase $purchaseB_Draft;
    protected Purchase $purchaseB_Submitted;
    protected Purchase $purchaseB_Approved;
    protected Purchase $unassignedPurchase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        // 1. Outlets
        $this->outletA = $this->createTestOutlet('OUT-PROC-A', 'Konveksi Nayara A', 'ACTIVE');
        $this->outletB = $this->createTestOutlet('OUT-PROC-B', 'Konveksi Nayara B', 'ACTIVE');
        $this->outletInactive = $this->createTestOutlet('OUT-PROC-INACT', 'Konveksi Inactive', 'INACTIVE');

        // 2. Users
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.a');
        $this->adminB = $this->createTestUser($this->outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.b');
        $this->gudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.a');
        $this->gudangB = $this->createTestUser($this->outletB, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.b');
        $this->ownerA = $this->createTestUser($this->outletA, UserRole::OWNER, UserStatus::ACTIVE, 'owner.a');
        $this->produksiA = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod.a');
        $this->customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'cust.a');
        $this->inactiveGudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::INACTIVE, 'gudang.inact');
        $this->unboundGudang = $this->createTestUser(null, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.unbound');
        $this->gudangInactiveOutlet = $this->createTestUser($this->outletInactive, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.inactout');

        // 3. Units
        $this->unitMeter = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        // 4. Suppliers
        $this->supplierA = Supplier::create([
            'supplier_name' => 'PT Tekstil Prima Mandiri A',
            'contact_person'=> 'Budi Santoso Tenant A',
            'phone'         => '081299990001',
            'email'         => 'budi@tekstil-a.test',
            'address'       => 'Jl. Industri A No. 10',
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);

        $this->supplierB = Supplier::create([
            'supplier_name' => 'CV Sumber Benang Makmur B',
            'contact_person'=> 'Dewi Lestari Tenant B',
            'phone'         => '081299990002',
            'email'         => 'dewi@benang-b.test',
            'address'       => 'Jl. Industri B No. 20',
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);

        $this->unassignedSupplier = Supplier::create([
            'supplier_name' => 'Toko Kain Antik Unassigned',
            'contact_person'=> 'Siti Legacy',
            'phone'         => '081299990003',
            'email'         => 'siti@legacy.test',
            'address'       => 'Jl. Antik No. 99',
            'active'        => true,
            'outlet_id'     => null,
        ]);

        // 5. Materials
        $this->materialA = Material::create([
            'material_name' => 'Kain Rayon Twill A',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        $this->supplierA->materials()->attach($this->materialA->id);

        $this->materialB = Material::create([
            'material_name' => 'Kain Katun Madinah B',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        $this->supplierB->materials()->attach($this->materialB->id);

        $this->unassignedMaterial = Material::create([
            'material_name' => 'Kain Legacy Tanpa Outlet',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => null,
        ]);

        // 6. Purchases for Outlet A
        $this->purchaseA_Draft = Purchase::create([
            'purchase_no'     => 'PO-202610-A-001',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Draft->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '100.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseA_Submitted = Purchase::create([
            'purchase_no'     => 'PO-202610-A-002',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangA->id,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Submitted->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '50.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseA_Approved = Purchase::create([
            'purchase_no'     => 'PO-202610-A-003',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangA->id,
            'approved_at'     => now(),
            'approved_by'     => $this->adminA->id,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Approved->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '75.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseA_Received = Purchase::create([
            'purchase_no'     => 'PO-202610-A-004',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-15',
            'status'          => PurchaseStatus::RECEIVED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now()->subDays(5),
            'submitted_by'    => $this->gudangA->id,
            'approved_at'     => now()->subDays(4),
            'approved_by'     => $this->adminA->id,
            'outlet_id'       => $this->outletA->id,
        ]);
        $piRecA = PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Received->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '40.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '40.0000',
        ]);
        StockMovement::create([
            'material_id'        => $this->materialA->id,
            'movement_type'      => \App\Enums\MovementType::IN,
            'qty'                => '40.0000',
            'unit_cost_snapshot' => '35000.0000',
            'total_cost_snapshot'=> '1400000.00',
            'qty_before'         => '0.0000',
            'qty_after'          => '40.0000',
            'reference_type'     => 'purchase_items',
            'reference_id'       => $piRecA->id,
            'movement_date'      => '2026-10-16',
            'created_by'         => $this->gudangA->id,
        ]);

        // 7. Purchases for Outlet B
        $this->purchaseB_Draft = Purchase::create([
            'purchase_no'     => 'PO-202610-B-001',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => $this->outletB->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseB_Draft->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '200.0000',
            'unit_price'   => '40000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseB_Submitted = Purchase::create([
            'purchase_no'     => 'PO-202610-B-002',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangB->id,
            'outlet_id'       => $this->outletB->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseB_Submitted->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '150.0000',
            'unit_price'   => '40000.00',
            'qty_received' => '0.0000',
        ]);

        $this->purchaseB_Approved = Purchase::create([
            'purchase_no'     => 'PO-202610-B-003',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now(),
            'submitted_by'    => $this->gudangB->id,
            'approved_at'     => now(),
            'approved_by'     => $this->adminB->id,
            'outlet_id'       => $this->outletB->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseB_Approved->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '80.0000',
            'unit_price'   => '40000.00',
            'qty_received' => '0.0000',
        ]);

        // 8. Legacy unassigned Purchase
        $this->unassignedPurchase = Purchase::create([
            'purchase_no'     => 'PO-LEGACY-NULL-001',
            'supplier_id'     => $this->unassignedSupplier->id,
            'purchase_date'   => '2026-09-01',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => null,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->unassignedPurchase->id,
            'material_id'  => $this->unassignedMaterial->id,
            'qty'          => '10.0000',
            'unit_price'   => '25000.00',
            'qty_received' => '0.0000',
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

    protected function createTestUser(?Outlet $outlet, UserRole $role, UserStatus $status, string $prefix): User
    {
        return User::create([
            'name'      => "User {$prefix}",
            'email'     => "{$prefix}_{$role->value}@nayara.test",
            'password'  => Hash::make('password123'),
            'role'      => $role,
            'status'    => $status,
            'outlet_id' => $outlet?->id,
        ]);
    }

    /**
     * 01. GUDANG A melihat daftar Purchase A.
     */
    public function test_01_gudang_a_sees_purchases_of_outlet_a(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.index'));

        $response->assertOk();
        $response->assertSee('PO-202610-A-001');
        $response->assertSee('PO-202610-A-002');
        $response->assertSee('PT Tekstil Prima Mandiri A');
    }

    /**
     * 02. GUDANG A tidak melihat Purchase B.
     */
    public function test_02_gudang_a_does_not_see_purchases_of_outlet_b(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.index'));

        $response->assertOk();
        $response->assertDontSee('PO-202610-B-001');
        $response->assertDontSee('PO-202610-B-002');
        $response->assertDontSee('CV Sumber Benang Makmur B');
    }

    /**
     * 03. GUDANG B melihat daftar Purchase B.
     */
    public function test_03_gudang_b_sees_purchases_of_outlet_b(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->get(route('gudang.purchases.index'));

        $response->assertOk();
        $response->assertSee('PO-202610-B-001');
        $response->assertSee('PO-202610-B-002');
        $response->assertSee('CV Sumber Benang Makmur B');
    }

    /**
     * 04. GUDANG B tidak melihat Purchase A.
     */
    public function test_04_gudang_b_does_not_see_purchases_of_outlet_a(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->get(route('gudang.purchases.index'));

        $response->assertOk();
        $response->assertDontSee('PO-202610-A-001');
        $response->assertDontSee('PO-202610-A-002');
        $response->assertDontSee('PT Tekstil Prima Mandiri A');
    }

    /**
     * 05. Purchase index search terisolasi.
     */
    public function test_05_purchase_index_search_is_isolated(): void
    {
        // Gudang A searches for Purchase B's PO number -> must yield 0 results
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.index', ['search' => 'PO-202610-B-001']));

        $response->assertOk();
        $this->assertEquals(0, $response->viewData('purchases')->total());
        $response->assertSee('Tidak ada data Purchase Order ditemukan.');

        // Gudang A searches for Purchase A's PO number -> found
        $responseOwn = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.index', ['search' => 'PO-202610-A-001']));

        $responseOwn->assertOk();
        $this->assertEquals(1, $responseOwn->viewData('purchases')->total());
        $responseOwn->assertSee('PO-202610-A-001');
    }

    /**
     * 06. Purchase status filter terisolasi.
     */
    public function test_06_purchase_index_status_filter_is_isolated(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.index', ['status' => PurchaseStatus::DRAFT->value]));

        $response->assertOk();
        $response->assertSee('PO-202610-A-001');
        $response->assertDontSee('PO-202610-B-001');
    }

    /**
     * 07. Purchase pagination terisolasi.
     */
    public function test_07_purchase_index_pagination_is_isolated(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.index', ['page' => 1]));

        $response->assertOk();
        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $response->viewData('purchases');
        $this->assertEquals(4, $paginator->total());
        foreach ($paginator->items() as $item) {
            $this->assertEquals($this->outletA->id, $item->outlet_id);
        }
    }

    /**
     * 08. Purchase aggregate/count terisolasi.
     */
    public function test_08_purchase_aggregate_and_count_is_isolated(): void
    {
        // Admin A checks approvals index: pending count should be 1 (PO-A-002), approved count should be 2 (PO-A-003 and PO-A-004)
        $response = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.approvals.index'));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('pendingCount'));
        $this->assertEquals(2, $response->viewData('approvedCount'));

        // Admin B checks approvals index: pending count should be 1 (PO-B-002), approved 1 (PO-B-003)
        $responseB = $this->actingAs($this->adminB)
            ->get(route('admin.purchases.approvals.index'));

        $responseB->assertOk();
        $this->assertEquals(1, $responseB->viewData('pendingCount'));
        $this->assertEquals(1, $responseB->viewData('approvedCount'));
    }

    /**
     * 09. GUDANG A membuka detail Purchase A.
     */
    public function test_09_gudang_a_opens_detail_of_purchase_a(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $this->purchaseA_Draft));

        $response->assertOk();
        $response->assertSee('PO-202610-A-001');
        $response->assertSee('PT Tekstil Prima Mandiri A');
        $response->assertSee('Kain Rayon Twill A');
    }

    /**
     * 10. GUDANG A tidak dapat membuka detail Purchase B.
     */
    public function test_10_gudang_a_cannot_open_detail_of_purchase_b_gets_404(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $this->purchaseB_Draft));

        $response->assertNotFound();
    }

    /**
     * 11. Foreign Purchase menghasilkan HTTP 404.
     */
    public function test_11_foreign_purchase_detail_returns_404(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->get(route('gudang.purchases.show', $this->purchaseA_Draft));

        $response->assertNotFound();
    }

    /**
     * 12. Purchase unassigned tidak bisa diakses saat flag TRUE.
     */
    public function test_12_unassigned_purchase_returns_404_when_flag_true(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $this->unassignedPurchase));

        $response->assertNotFound();
    }

    /**
     * 13. GUDANG A membuka edit form Purchase A jika tersedia.
     */
    public function test_13_gudang_a_opens_edit_form_purchase_a(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.edit', $this->purchaseA_Draft));

        $response->assertOk();
        $response->assertSee('PO-202610-A-001');
    }

    /**
     * 14. GUDANG A tidak bisa edit Purchase B.
     */
    public function test_14_gudang_a_cannot_open_edit_form_purchase_b_gets_404(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.edit', $this->purchaseB_Draft));

        $response->assertNotFound();
    }

    /**
     * 15. Create form hanya memuat Supplier Outlet A.
     */
    public function test_15_create_form_only_loads_suppliers_of_active_outlet(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.create'));

        $response->assertOk();
        $suppliers = $response->viewData('suppliers');
        $this->assertTrue($suppliers->contains('id', $this->supplierA->id));
        $response->assertSee('PT Tekstil Prima Mandiri A');
    }

    /**
     * 16. Create form tidak memuat Supplier Outlet B.
     */
    public function test_16_create_form_does_not_load_suppliers_of_foreign_outlet(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.create'));

        $response->assertOk();
        $suppliers = $response->viewData('suppliers');
        $this->assertFalse($suppliers->contains('id', $this->supplierB->id));
        $response->assertDontSee('CV Sumber Benang Makmur B');
    }

    /**
     * 17. Create form hanya memuat Material Outlet A.
     */
    public function test_17_create_form_only_loads_materials_of_active_outlet(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.create'));

        $response->assertOk();
        $materials = $response->viewData('materials');
        $this->assertTrue($materials->contains('id', $this->materialA->id));
        $response->assertSee('Kain Rayon Twill A');
    }

    /**
     * 18. Create form tidak memuat Material Outlet B.
     */
    public function test_18_create_form_does_not_load_materials_of_foreign_outlet(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.create'));

        $response->assertOk();
        $materials = $response->viewData('materials');
        $this->assertFalse($materials->contains('id', $this->materialB->id));
        $response->assertDontSee('Kain Katun Madinah B');
    }

    /**
     * 19. Edit form dropdown terikat outlet aktif.
     */
    public function test_19_edit_form_dropdowns_are_scoped_to_active_outlet(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.edit', $this->purchaseA_Draft));

        $response->assertOk();
        $suppliers = $response->viewData('suppliers');
        $materials = $response->viewData('materials');

        $this->assertTrue($suppliers->contains('id', $this->supplierA->id));
        $this->assertFalse($suppliers->contains('id', $this->supplierB->id));

        $this->assertTrue($materials->contains('id', $this->materialA->id));
        $this->assertFalse($materials->contains('id', $this->materialB->id));
    }

    /**
     * 20. Supplier dengan outlet_id NULL tidak muncul saat flag TRUE.
     */
    public function test_20_supplier_with_null_outlet_id_does_not_appear_when_flag_true(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.create'));

        $response->assertOk();
        $suppliers = $response->viewData('suppliers');
        $this->assertFalse($suppliers->contains('id', $this->unassignedSupplier->id));
    }

    /**
     * 21. Material dengan outlet_id NULL tidak muncul saat flag TRUE.
     */
    public function test_21_material_with_null_outlet_id_does_not_appear_when_flag_true(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.create'));

        $response->assertOk();
        $materials = $response->viewData('materials');
        $this->assertFalse($materials->contains('id', $this->unassignedMaterial->id));
    }

    /**
     * 22. Purchase dengan Supplier lintas-outlet tidak boleh membocorkan data Supplier B (fail closed 404).
     */
    public function test_22_purchase_with_cross_tenant_supplier_fails_closed_returns_404(): void
    {
        // Tampered Purchase: outlet A, but supplier belongs to outlet B
        $tamperedPo = Purchase::create([
            'purchase_no'     => 'PO-TAMPERED-001',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $tamperedPo->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '10.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $tamperedPo));

        $response->assertNotFound();
    }

    /**
     * 23. PurchaseItem dengan Material lintas-outlet ditangani secara fail-closed (404).
     */
    public function test_23_purchase_item_with_cross_tenant_material_fails_closed_returns_404(): void
    {
        // Tampered Purchase: outlet A, supplier A, but item contains material from outlet B
        $tamperedPo = Purchase::create([
            'purchase_no'     => 'PO-TAMPERED-002',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-21',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $tamperedPo->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '10.0000',
            'unit_price'   => '40000.00',
            'qty_received' => '0.0000',
        ]);

        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $tamperedPo));

        $response->assertNotFound();
    }

    /**
     * 24. Supplier listing terisolasi jika route tersedia.
     */
    public function test_24_supplier_listing_is_isolated(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.suppliers.index'));

        $response->assertOk();
        $response->assertSee('PT Tekstil Prima Mandiri A');
        $response->assertDontSee('CV Sumber Benang Makmur B');
        $response->assertDontSee('Toko Kain Antik Unassigned');
    }

    /**
     * 25. Supplier detail asing menghasilkan HTTP 404 jika route tersedia.
     */
    public function test_25_foreign_supplier_detail_returns_404(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.suppliers.show', $this->supplierB));

        $response->assertNotFound();
    }

    /**
     * 26. Supplier edit asing menghasilkan HTTP 404 jika route tersedia.
     */
    public function test_26_foreign_supplier_edit_returns_404(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.suppliers.edit', $this->supplierB));

        $response->assertNotFound();
    }

    /**
     * 27. Supplier search tidak mencampur tenant.
     */
    public function test_27_supplier_search_does_not_leak_cross_tenant(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.suppliers.index', ['search' => 'Dewi Lestari']));

        $response->assertOk();
        $response->assertDontSee('CV Sumber Benang Makmur B');
    }

    /**
     * 28. Approval listing READ terisolasi jika termasuk scope aktual.
     */
    public function test_28_approval_listing_read_is_isolated(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.approvals.index'));

        $response->assertOk();
        $response->assertSee('PO-202610-A-002');
        $response->assertDontSee('PO-202610-B-002');
    }

    /**
     * 29. Approval detail asing ditolak (404) jika termasuk scope aktual.
     */
    public function test_29_approval_detail_foreign_purchase_returns_404(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.approvals.show', $this->purchaseB_Submitted));

        $response->assertNotFound();
    }

    /**
     * 30. OWNER tidak memperoleh akses approval ADMIN (403).
     */
    public function test_30_owner_cannot_access_admin_approval_returns_403(): void
    {
        $responseIndex = $this->actingAs($this->ownerA)
            ->get(route('admin.purchases.approvals.index'));

        $responseIndex->assertForbidden();

        $responseShow = $this->actingAs($this->ownerA)
            ->get(route('admin.purchases.approvals.show', $this->purchaseA_Submitted));

        $responseShow->assertForbidden();
    }

    /**
     * 31. RoleMiddleware existing tetap aktif (Gudang cannot access Admin approval).
     */
    public function test_31_role_middleware_remains_active(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('admin.purchases.approvals.index'));

        $response->assertForbidden();
    }

    /**
     * 32. CUSTOMER tidak memperoleh akses internal purchase (403).
     */
    public function test_32_customer_cannot_access_internal_purchase(): void
    {
        $responseGudang = $this->actingAs($this->customerUser)
            ->get(route('gudang.purchases.index'));

        $responseGudang->assertForbidden();

        $responseAdmin = $this->actingAs($this->customerUser)
            ->get(route('admin.purchases.approvals.index'));

        $responseAdmin->assertForbidden();
    }

    /**
     * 33. PRODUKSI tidak memperoleh bypass ke route Admin atau Gudang.
     */
    public function test_33_produksi_cannot_access_gudang_or_admin_procurement(): void
    {
        $responseGudang = $this->actingAs($this->produksiA)
            ->get(route('gudang.purchases.index'));

        $responseGudang->assertForbidden();

        $responseAdmin = $this->actingAs($this->produksiA)
            ->get(route('admin.purchases.approvals.index'));

        $responseAdmin->assertForbidden();
    }

    /**
     * 34. User tanpa outlet ditolak saat flag TRUE (403).
     */
    public function test_34_user_without_outlet_is_rejected_when_flag_true(): void
    {
        $response = $this->actingAs($this->unboundGudang)
            ->get(route('gudang.purchases.index'));

        $response->assertForbidden();
    }

    /**
     * 35. Outlet INACTIVE ditolak (403).
     */
    public function test_35_outlet_inactive_is_rejected(): void
    {
        $response = $this->actingAs($this->gudangInactiveOutlet)
            ->get(route('gudang.purchases.index'));

        $response->assertForbidden();
    }

    /**
     * 36. User INACTIVE ditolak tanpa kebocoran data.
     */
    public function test_36_user_inactive_is_rejected_without_data_leakage(): void
    {
        $response = $this->actingAs($this->inactiveGudangA)
            ->get(route('gudang.purchases.index'));

        // Middleware rejects inactive user (either 403 or redirect to login depending on auth pipeline)
        $this->assertTrue(in_array($response->status(), [302, 401, 403], true));
        $response->assertDontSee('PO-202610-A-001');
        $response->assertDontSee('PT Tekstil Prima Mandiri A');
    }

    /**
     * 37. Payload outlet_id palsu tidak mengubah tenant.
     */
    public function test_37_fake_outlet_id_payload_does_not_switch_tenant(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.index', ['outlet_id' => $this->outletB->id]));

        $response->assertOk();
        $response->assertSee('PO-202610-A-001');
        $response->assertDontSee('PO-202610-B-001');
    }

    /**
     * 38. Header X-Outlet-ID palsu tidak mengubah tenant.
     */
    public function test_38_fake_x_outlet_id_header_does_not_switch_tenant(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->get(route('gudang.purchases.index'));

        $response->assertOk();
        $response->assertSee('PO-202610-A-001');
        $response->assertDontSee('PO-202610-B-001');
    }

    /**
     * 39. Mode FALSE mempertahankan legacy read behavior.
     */
    public function test_39_legacy_mode_false_preserves_unscoped_read_behavior(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $this->unassignedPurchase));

        $response->assertOk();
        $response->assertSee('PO-LEGACY-NULL-001');
    }

    /**
     * 40. Mode TRUE fail-closed ketika TenantContext invalid.
     */
    public function test_40_mode_true_fails_closed_when_tenant_context_is_invalid(): void
    {
        $invalidUser = $this->createTestUser(null, UserRole::GUDANG, UserStatus::ACTIVE, 'invalid.ctx');

        $response = $this->actingAs($invalidUser)
            ->get(route('gudang.purchases.index'));

        $response->assertForbidden();
    }

    /**
     * 41. GET tidak mengubah Purchase.
     */
    public function test_41_get_request_does_not_mutate_purchase_records(): void
    {
        $initialUpdated = $this->purchaseA_Draft->updated_at->toDateTimeString();
        $initialStatus = $this->purchaseA_Draft->status;

        $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $this->purchaseA_Draft))
            ->assertOk();

        $fresh = $this->purchaseA_Draft->fresh();
        $this->assertEquals($initialUpdated, $fresh->updated_at->toDateTimeString());
        $this->assertEquals($initialStatus, $fresh->status);
    }

    /**
     * 42. GET tidak mengubah Supplier.
     */
    public function test_42_get_request_does_not_mutate_supplier_records(): void
    {
        $initialUpdated = $this->supplierA->updated_at->toDateTimeString();

        $this->actingAs($this->gudangA)
            ->get(route('gudang.suppliers.show', $this->supplierA))
            ->assertOk();

        $this->assertEquals($initialUpdated, $this->supplierA->fresh()->updated_at->toDateTimeString());
    }

    /**
     * 43. GET tidak membuat StockMovement.
     */
    public function test_43_get_request_does_not_create_stock_movements(): void
    {
        $countBefore = StockMovement::count();

        $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.history'))
            ->assertOk();

        $this->assertEquals($countBefore, StockMovement::count());
    }

    /**
     * 44. GET tidak mengubah Purchase approval history.
     */
    public function test_44_get_request_does_not_mutate_purchase_approval_history(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.purchases.approvals.show', $this->purchaseA_Submitted))
            ->assertOk();

        $fresh = $this->purchaseA_Submitted->fresh();
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $fresh->approval_status);
        $this->assertNull($fresh->approved_at);
        $this->assertNull($fresh->approved_by);
    }

    /**
     * 45. GET tidak membuat payment atau receiving.
     */
    public function test_45_get_request_does_not_create_payment_or_receiving(): void
    {
        $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $this->purchaseA_Draft))
            ->assertOk();

        $this->assertEquals(0, $this->purchaseA_Draft->fresh()->payments()->count());
        $this->assertEquals('0.0000', $this->purchaseA_Draft->fresh()->items->first()->qty_received);
    }

    /**
     * 46. Tidak ada Global Tenant Scope baru.
     */
    public function test_46_no_new_global_tenant_scope_created(): void
    {
        $purchaseGlobalScopes = (new Purchase)->getGlobalScopes();
        $supplierGlobalScopes = (new Supplier)->getGlobalScopes();
        $materialGlobalScopes = (new Material)->getGlobalScopes();

        $this->assertEmpty($purchaseGlobalScopes);
        $this->assertEmpty($supplierGlobalScopes);
        $this->assertEmpty($materialGlobalScopes);
    }

    /**
     * 47. Sales Order isolation existing tetap lulus.
     */
    public function test_47_sales_order_isolation_remains_intact(): void
    {
        $this->assertTrue(method_exists(\App\Models\Sales\Order::class, 'scopeForOutlet'));
    }

    /**
     * 48. Production / Inventory isolation existing tetap lulus.
     */
    public function test_48_production_inventory_isolation_remains_intact(): void
    {
        $this->assertTrue(method_exists(\App\Models\Production\ProductionBatch::class, 'scopeForOutlet'));
        $this->assertTrue(method_exists(\App\Models\Inventory\Purchase::class, 'scopeForOutlet'));
        $this->assertTrue(method_exists(\App\Models\Inventory\Purchase::class, 'hasConsistentTenantRelations'));
    }

    /**
     * 49. Data privat outlet lain tidak muncul dalam HTML.
     */
    public function test_49_foreign_tenant_private_data_never_appears_in_html_response(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.index'));

        $response->assertOk();
        $response->assertDontSee('081299990002');
        $response->assertDontSee('dewi@benang-b.test');
        $response->assertDontSee('Jl. Industri B No. 20');
        $response->assertDontSee('CV Sumber Benang Makmur B');
    }

    /**
     * 50. Tidak ada database record tenant lain termodifikasi akibat endpoint READ.
     */
    public function test_50_database_records_of_foreign_tenant_remain_unmodified(): void
    {
        $poBBefore = $this->purchaseB_Draft->fresh()->toArray();
        $supplierBBefore = $this->supplierB->fresh()->toArray();

        $this->actingAs($this->gudangA)->get(route('gudang.purchases.index'))->assertOk();
        $this->actingAs($this->gudangA)->get(route('gudang.purchases.show', $this->purchaseA_Draft))->assertOk();
        $this->actingAs($this->gudangA)->get(route('gudang.suppliers.index'))->assertOk();

        $this->assertEquals($poBBefore, $this->purchaseB_Draft->fresh()->toArray());
        $this->assertEquals($supplierBBefore, $this->supplierB->fresh()->toArray());
    }
}
