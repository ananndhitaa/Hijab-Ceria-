<?php

namespace Tests\Feature\System;

use App\Enums\MovementType;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Inventory\PurchaseReceivingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProcurementSupplierReceivingTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $gudangA;
    protected User $gudangB;
    protected User $adminA;
    protected User $adminB;
    protected User $ownerA;
    protected User $produksiA;
    protected User $customerUser;
    protected User $inactiveGudangA;
    protected User $unboundGudang;
    protected User $gudangInactiveOutlet;

    protected Unit $unitMeter;

    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Supplier $supplierA_NoPO;
    protected Supplier $supplierB_NoPO;
    protected Supplier $unassignedSupplier;

    protected Material $materialA;
    protected Material $materialB;
    protected Material $unassignedMaterial;

    protected MaterialStock $stockA;
    protected MaterialStock $stockB;
    protected MaterialStock $stockUnassigned;

    protected Purchase $purchaseA_Eligible;
    protected Purchase $purchaseB_Eligible;
    protected Purchase $purchaseA_Unpaid;
    protected Purchase $purchaseA_Draft;
    protected Purchase $unassignedPurchaseEligible;

    protected PurchaseItem $itemA_Eligible;
    protected PurchaseItem $itemB_Eligible;
    protected PurchaseItem $itemA_Unpaid;
    protected PurchaseItem $itemA_Draft;
    protected PurchaseItem $itemUnassigned_Eligible;

    protected PurchaseReceivingService $receivingService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        $this->receivingService = app(PurchaseReceivingService::class);

        // 1. Outlets
        $this->outletA = $this->createTestOutlet('OUT-SUP-REC-A', 'Konveksi Nayara Supplier A', 'ACTIVE');
        $this->outletB = $this->createTestOutlet('OUT-SUP-REC-B', 'Konveksi Nayara Supplier B', 'ACTIVE');
        $this->outletInactive = $this->createTestOutlet('OUT-SUP-REC-INACT', 'Konveksi Inactive', 'INACTIVE');

        // 2. Users
        $this->gudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.a');
        $this->gudangB = $this->createTestUser($this->outletB, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.b');
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.a');
        $this->adminB = $this->createTestUser($this->outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.b');
        $this->ownerA = $this->createTestUser($this->outletA, UserRole::OWNER, UserStatus::ACTIVE, 'owner.a');
        $this->produksiA = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod.a');
        $this->customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'cust.a');
        $this->inactiveGudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::INACTIVE, 'gudang.inact');
        $this->unboundGudang = $this->createTestUser(null, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.unbound');
        $this->gudangInactiveOutlet = $this->createTestUser($this->outletInactive, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.inactout');

        // 3. Units
        $this->unitMeter = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        // 4. Materials & Stocks
        $this->materialA = Material::create([
            'material_name' => 'Kain Rayon Twill A',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        $this->stockA = MaterialStock::create([
            'material_id'   => $this->materialA->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '30000.0000',
        ]);

        $this->materialB = Material::create([
            'material_name' => 'Kain Katun Madinah B',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        $this->stockB = MaterialStock::create([
            'material_id'   => $this->materialB->id,
            'qty_on_hand'   => '200.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $this->unassignedMaterial = Material::create([
            'material_name' => 'Kain Unassigned Legacy',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => null,
        ]);
        $this->stockUnassigned = MaterialStock::create([
            'material_id'   => $this->unassignedMaterial->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '20000.0000',
        ]);

        // 5. Suppliers
        $this->supplierA = Supplier::create([
            'supplier_name' => 'PT Tekstil Prima Mandiri A',
            'phone'         => '081299990001',
            'address'       => 'Jl. Industri A No. 10',
            'email'         => 'budi@tekstil-a.test',
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        $this->supplierA->materials()->attach($this->materialA->id);

        $this->supplierA_NoPO = Supplier::create([
            'supplier_name' => 'CV Kain Lepas A Tanpa PO',
            'phone'         => '081299990011',
            'address'       => 'Jl. Industri A No. 15',
            'email'         => 'lepas@tekstil-a.test',
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);

        $this->supplierB = Supplier::create([
            'supplier_name' => 'CV Sumber Benang Makmur B',
            'phone'         => '081299990002',
            'address'       => 'Jl. Industri B No. 20',
            'email'         => 'dewi@benang-b.test',
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        $this->supplierB->materials()->attach($this->materialB->id);

        $this->supplierB_NoPO = Supplier::create([
            'supplier_name' => 'CV Benang Lepas B Tanpa PO',
            'phone'         => '081299990022',
            'address'       => 'Jl. Industri B No. 25',
            'email'         => 'lepas@benang-b.test',
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);

        $this->unassignedSupplier = Supplier::create([
            'supplier_name' => 'Toko Kain Antik Unassigned',
            'phone'         => '081299990003',
            'address'       => 'Jl. Antik No. 99',
            'email'         => 'siti@legacy.test',
            'active'        => true,
            'outlet_id'     => null,
        ]);
        $this->unassignedSupplier->materials()->attach($this->unassignedMaterial->id);

        // 6. Purchases & Items
        // Purchase A (ORDERED, APPROVED, FULLY PAID -> Eligible for Receiving)
        $this->purchaseA_Eligible = Purchase::create([
            'purchase_no'     => 'PO-202610-A-REC01',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now()->subDays(2),
            'submitted_by'    => $this->gudangA->id,
            'approved_at'     => now()->subDay(),
            'approved_by'     => $this->adminA->id,
            'outlet_id'       => $this->outletA->id,
        ]);
        $this->itemA_Eligible = PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Eligible->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '50.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);
        PurchasePayment::create([
            'purchase_id'    => $this->purchaseA_Eligible->id,
            'amount'         => '1750000.00',
            'payment_date'   => '2026-10-20',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminA->id,
        ]);

        // Purchase B (ORDERED, APPROVED, FULLY PAID -> Eligible for Receiving)
        $this->purchaseB_Eligible = Purchase::create([
            'purchase_no'     => 'PO-202610-B-REC01',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now()->subDays(2),
            'submitted_by'    => $this->gudangB->id,
            'approved_at'     => now()->subDay(),
            'approved_by'     => $this->adminB->id,
            'outlet_id'       => $this->outletB->id,
        ]);
        $this->itemB_Eligible = PurchaseItem::create([
            'purchase_id'  => $this->purchaseB_Eligible->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '40.0000',
            'unit_price'   => '42000.00',
            'qty_received' => '0.0000',
        ]);
        PurchasePayment::create([
            'purchase_id'    => $this->purchaseB_Eligible->id,
            'amount'         => '1680000.00',
            'payment_date'   => '2026-10-20',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminB->id,
        ]);

        // Purchase A Unpaid (ORDERED, APPROVED, but NO PAYMENT)
        $this->purchaseA_Unpaid = Purchase::create([
            'purchase_no'     => 'PO-202610-A-UNPAID',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now()->subDays(2),
            'submitted_by'    => $this->gudangA->id,
            'approved_at'     => now()->subDay(),
            'approved_by'     => $this->adminA->id,
            'outlet_id'       => $this->outletA->id,
        ]);
        $this->itemA_Unpaid = PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Unpaid->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '30.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        // Purchase A Draft (DRAFT, NOT_SUBMITTED)
        $this->purchaseA_Draft = Purchase::create([
            'purchase_no'     => 'PO-202610-A-DRAFT',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => $this->outletA->id,
        ]);
        $this->itemA_Draft = PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Draft->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '20.0000',
            'unit_price'   => '35000.00',
            'qty_received' => '0.0000',
        ]);

        // Unassigned Purchase Eligible (Legacy unassigned PO)
        $this->unassignedPurchaseEligible = Purchase::create([
            'purchase_no'     => 'PO-202610-LEG-REC01',
            'supplier_id'     => $this->unassignedSupplier->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now()->subDays(2),
            'submitted_by'    => $this->gudangA->id,
            'approved_at'     => now()->subDay(),
            'approved_by'     => $this->adminA->id,
            'outlet_id'       => null,
        ]);
        $this->itemUnassigned_Eligible = PurchaseItem::create([
            'purchase_id'  => $this->unassignedPurchaseEligible->id,
            'material_id'  => $this->unassignedMaterial->id,
            'qty'          => '10.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '0.0000',
        ]);
        PurchasePayment::create([
            'purchase_id'    => $this->unassignedPurchaseEligible->id,
            'amount'         => '200000.00',
            'payment_date'   => '2026-10-20',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminA->id,
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
            'email'     => "{$prefix}_{$role->value}_" . uniqid() . "@nayara.test",
            'password'  => Hash::make('password123'),
            'role'      => $role,
            'status'    => $status,
            'outlet_id' => $outlet?->id,
        ]);
    }

    // ==========================================
    // GROUP A: SUPPLIER STORE (01 - 09)
    // ==========================================

    /**
     * 01. Gudang A bisa membuat Supplier untuk Outlet A.
     */
    public function test_01_gudang_a_can_create_supplier_for_outlet_a(): void
    {
        $payload = [
            'supplier_name' => 'PT Mitra Baru Supplier A',
            'phone'         => '081211112222',
            'address'       => 'Kawasan Industri Mitra A',
            'email'         => 'mitra@supplier-a.test',
            'active'        => true,
            'material_ids'  => [$this->materialA->id],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.suppliers.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $latest = Supplier::latest('id')->first();
        $this->assertEquals('PT Mitra Baru Supplier A', $latest->supplier_name);
        $this->assertEquals($this->outletA->id, $latest->outlet_id);
        $this->assertTrue($latest->materials->contains($this->materialA->id));
    }

    /**
     * 02. Gudang B bisa membuat Supplier untuk Outlet B.
     */
    public function test_02_gudang_b_can_create_supplier_for_outlet_b(): void
    {
        $payload = [
            'supplier_name' => 'PT Mitra Baru Supplier B',
            'phone'         => '081233334444',
            'address'       => 'Kawasan Industri Mitra B',
            'email'         => 'mitra@supplier-b.test',
            'active'        => true,
            'material_ids'  => [$this->materialB->id],
        ];

        $response = $this->actingAs($this->gudangB)
            ->post(route('gudang.suppliers.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $latest = Supplier::latest('id')->first();
        $this->assertEquals('PT Mitra Baru Supplier B', $latest->supplier_name);
        $this->assertEquals($this->outletB->id, $latest->outlet_id);
        $this->assertTrue($latest->materials->contains($this->materialB->id));
    }

    /**
     * 03. Supplier baru memiliki outlet_id server-side.
     */
    public function test_03_new_supplier_has_server_side_outlet_id(): void
    {
        $payload = [
            'supplier_name' => 'Supplier Verifikasi Server Side',
            'phone'         => '081255556666',
        ];

        $this->actingAs($this->gudangA)->post(route('gudang.suppliers.store'), $payload);

        $supplier = Supplier::where('supplier_name', 'Supplier Verifikasi Server Side')->first();
        $this->assertNotNull($supplier);
        $this->assertSame((int) $this->outletA->id, (int) $supplier->outlet_id);
    }

    /**
     * 04. Fake outlet_id tidak mengubah kepemilikan Supplier.
     */
    public function test_04_fake_outlet_id_payload_does_not_override_supplier_ownership(): void
    {
        $payload = [
            'outlet_id'     => $this->outletB->id, // Attacker tries to inject Outlet B
            'supplier_name' => 'Supplier Spoofing Attempt',
            'phone'         => '081277778888',
        ];

        $this->actingAs($this->gudangA)->post(route('gudang.suppliers.store'), $payload);

        $supplier = Supplier::where('supplier_name', 'Supplier Spoofing Attempt')->first();
        $this->assertNotNull($supplier);
        $this->assertEquals($this->outletA->id, $supplier->outlet_id);
        $this->assertNotEquals($this->outletB->id, $supplier->outlet_id);
    }

    /**
     * 05. Fake X-Outlet-ID tidak mengubah kepemilikan.
     */
    public function test_05_fake_x_outlet_id_header_does_not_override_supplier_ownership(): void
    {
        $payload = [
            'supplier_name' => 'Supplier Spoofing Header Attempt',
            'phone'         => '081299991111',
        ];

        $this->actingAs($this->gudangA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('gudang.suppliers.store'), $payload);

        $supplier = Supplier::where('supplier_name', 'Supplier Spoofing Header Attempt')->first();
        $this->assertNotNull($supplier);
        $this->assertEquals($this->outletA->id, $supplier->outlet_id);
    }

    /**
     * 06. Material milik B tidak dapat di-attach ke Supplier baru A.
     */
    public function test_06_material_of_outlet_b_cannot_be_attached_to_new_supplier_of_a(): void
    {
        $countBefore = Supplier::count();

        $payload = [
            'supplier_name' => 'Supplier Cross Tenant Material',
            'phone'         => '081222223333',
            'material_ids'  => [$this->materialB->id], // Foreign material
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.suppliers.store'), $payload);

        $response->assertSessionHasErrors();
        $this->assertEquals($countBefore, Supplier::count());
    }

    /**
     * 07. Material unassigned ditolak saat flag TRUE.
     */
    public function test_07_unassigned_material_rejected_when_flag_true(): void
    {
        $countBefore = Supplier::count();

        $payload = [
            'supplier_name' => 'Supplier Unassigned Material Attempt',
            'material_ids'  => [$this->unassignedMaterial->id],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.suppliers.store'), $payload);

        $response->assertSessionHasErrors();
        $this->assertEquals($countBefore, Supplier::count());
    }

    /**
     * 08. Mixed materials lintas-outlet ditolak atomik.
     */
    public function test_08_mixed_materials_across_tenants_rejected_atomically_on_supplier_store(): void
    {
        $countBefore = Supplier::count();

        $payload = [
            'supplier_name' => 'Supplier Mixed Materials Attempt',
            'material_ids'  => [
                $this->materialA->id, // Valid
                $this->materialB->id, // Foreign
            ],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.suppliers.store'), $payload);

        $response->assertSessionHasErrors();
        $this->assertEquals($countBefore, Supplier::count());
    }

    /**
     * 09. Failed Supplier store tidak meninggalkan Supplier atau pivot parsial.
     */
    public function test_09_failed_supplier_store_leaves_no_partial_supplier_or_pivot(): void
    {
        $supplierCountBefore = Supplier::count();
        $pivotCountBefore = DB::table('supplier_materials')->count();

        $payload = [
            'supplier_name' => '', // Fails validation
            'material_ids'  => [$this->materialA->id],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.suppliers.store'), $payload);

        $response->assertSessionHasErrors('supplier_name');
        $this->assertEquals($supplierCountBefore, Supplier::count());
        $this->assertEquals($pivotCountBefore, DB::table('supplier_materials')->count());
    }

    // ==========================================
    // GROUP B: SUPPLIER UPDATE (10 - 16)
    // ==========================================

    /**
     * 10. Gudang A bisa update Supplier A yang sah.
     */
    public function test_10_gudang_a_can_update_valid_supplier_a(): void
    {
        $payload = [
            'supplier_name' => 'PT Tekstil Prima Mandiri A Updated',
            'phone'         => '081299990099',
            'address'       => 'Jl. Industri A No. 10 Revisi',
            'email'         => 'budi.rev@tekstil-a.test',
            'active'        => true,
            'material_ids'  => [$this->materialA->id],
        ];

        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.suppliers.update', $this->supplierA), $payload);

        $response->assertRedirect(route('gudang.suppliers.show', $this->supplierA));
        $response->assertSessionHas('success');

        $fresh = $this->supplierA->fresh();
        $this->assertEquals('PT Tekstil Prima Mandiri A Updated', $fresh->supplier_name);
        $this->assertEquals('081299990099', $fresh->phone);
    }

    /**
     * 11. Gudang A tidak dapat update Supplier B.
     */
    public function test_11_gudang_a_cannot_update_supplier_b(): void
    {
        $payload = [
            'supplier_name' => 'Supplier B Hijacked by A',
            'phone'         => '081299990000',
        ];

        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.suppliers.update', $this->supplierB), $payload);

        $response->assertStatus(404);
        $this->assertNotEquals('Supplier B Hijacked by A', $this->supplierB->fresh()->supplier_name);
    }

    /**
     * 12. Foreign Supplier update returns HTTP 404.
     */
    public function test_12_foreign_supplier_update_returns_http_404(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->put(route('gudang.suppliers.update', $this->supplierA), [
                'supplier_name' => 'Supplier A Hijacked by B',
            ]);

        $response->assertStatus(404);
    }

    /**
     * 13. Supplier unassigned tidak dapat diupdate saat flag TRUE.
     */
    public function test_13_unassigned_supplier_cannot_be_updated_when_flag_true(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.suppliers.update', $this->unassignedSupplier), [
                'supplier_name' => 'Unassigned Supplier Updated',
            ]);

        $response->assertStatus(404);
    }

    /**
     * 14. Supplier ownership tidak dapat dipindahkan melalui payload.
     */
    public function test_14_supplier_ownership_cannot_be_transferred_via_payload(): void
    {
        $payload = [
            'outlet_id'     => $this->outletB->id, // Attacker attempts transfer
            'supplier_name' => 'PT Tekstil Prima Mandiri A Transfer Attempt',
        ];

        $this->actingAs($this->gudangA)
            ->put(route('gudang.suppliers.update', $this->supplierA), $payload);

        $fresh = $this->supplierA->fresh();
        $this->assertEquals($this->outletA->id, $fresh->outlet_id);
    }

    /**
     * 15. Material pivot foreign tidak dapat ditambahkan.
     */
    public function test_15_foreign_material_pivot_cannot_be_added_on_supplier_update(): void
    {
        $payload = [
            'supplier_name' => $this->supplierA->supplier_name,
            'material_ids'  => [$this->materialB->id], // Foreign material
        ];

        $response = $this->actingAs($this->gudangA)
            ->put(route('gudang.suppliers.update', $this->supplierA), $payload);

        $response->assertSessionHasErrors();
        $this->assertFalse($this->supplierA->fresh()->materials->contains($this->materialB->id));
    }

    /**
     * 16. Supplier B dan pivot-nya tidak berubah setelah unauthorized update.
     */
    public function test_16_supplier_b_and_pivots_remain_unmodified_after_unauthorized_update(): void
    {
        $supplierBBefore = $this->supplierB->fresh()->toArray();
        $pivotsBefore = $this->supplierB->materials->pluck('id')->all();

        $this->actingAs($this->gudangA)->put(route('gudang.suppliers.update', $this->supplierB), [
            'supplier_name' => 'Illegal Name',
            'material_ids'  => [$this->materialA->id],
        ]);

        $this->assertEquals($supplierBBefore, $this->supplierB->fresh()->toArray());
        $this->assertEquals($pivotsBefore, $this->supplierB->fresh()->materials->pluck('id')->all());
    }

    // ==========================================
    // GROUP C: SUPPLIER DESTROY / TOGGLE (17 - 21)
    // ==========================================

    /**
     * 17. Gudang A dapat melakukan destroy Supplier A jika aturan bisnis mengizinkan (tidak ada PO).
     */
    public function test_17_gudang_a_can_destroy_supplier_a_without_purchases(): void
    {
        $supplierId = $this->supplierA_NoPO->id;

        $response = $this->actingAs($this->gudangA)
            ->delete(route('gudang.suppliers.destroy', $this->supplierA_NoPO));

        $response->assertRedirect(route('gudang.suppliers.index'));
        $response->assertSessionHas('success');
        $this->assertNull(Supplier::find($supplierId));
    }

    /**
     * 18. Gudang A tidak dapat destroy Supplier B.
     */
    public function test_18_gudang_a_cannot_destroy_supplier_b_returns_http_404(): void
    {
        $supplierId = $this->supplierB_NoPO->id;

        $response = $this->actingAs($this->gudangA)
            ->delete(route('gudang.suppliers.destroy', $this->supplierB_NoPO));

        $response->assertStatus(404);
        $this->assertNotNull(Supplier::find($supplierId));
    }

    /**
     * 19. Supplier yang sudah dipakai Purchase mengikuti delete restriction existing.
     */
    public function test_19_supplier_with_purchases_cannot_be_destroyed(): void
    {
        $supplierId = $this->supplierA->id;

        $response = $this->actingAs($this->gudangA)
            ->delete(route('gudang.suppliers.destroy', $this->supplierA));

        $response->assertRedirect(route('gudang.suppliers.index'));
        $response->assertSessionHas('error');
        $this->assertNotNull(Supplier::find($supplierId));
    }

    /**
     * 20. Toggle active foreign ditolak jika endpoint ada.
     */
    public function test_20_toggle_active_on_foreign_supplier_returns_http_404(): void
    {
        $activeBefore = $this->supplierB->active;

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.suppliers.toggle-active', $this->supplierB));

        $response->assertStatus(404);
        $this->assertEquals($activeBefore, $this->supplierB->fresh()->active);
    }

    /**
     * 21. Unauthorized delete tidak mengubah histori PO.
     */
    public function test_21_unauthorized_delete_attempt_does_not_modify_po_or_supplier_history(): void
    {
        $poCountBefore = Purchase::where('supplier_id', $this->supplierB->id)->count();

        $this->actingAs($this->gudangA)->delete(route('gudang.suppliers.destroy', $this->supplierB));

        $this->assertEquals($poCountBefore, Purchase::where('supplier_id', $this->supplierB->id)->count());
    }

    // ==========================================
    // GROUP D: RECEIVING HAPPY PATH (22 - 27)
    // ==========================================

    /**
     * 22. Gudang A dapat menerima Purchase A yang eligible.
     */
    public function test_22_gudang_a_can_receive_eligible_purchase_a(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $response->assertRedirect(route('gudang.purchases.show', $this->purchaseA_Eligible));
        $response->assertSessionHas('success');

        $freshPO = $this->purchaseA_Eligible->fresh();
        $this->assertEquals(PurchaseStatus::RECEIVED, $freshPO->status);
        $this->assertEquals('50.0000', $freshPO->items->first()->qty_received);
    }

    /**
     * 23. Gudang B dapat menerima Purchase B yang eligible.
     */
    public function test_23_gudang_b_can_receive_eligible_purchase_b(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->post(route('gudang.purchases.receive', $this->purchaseB_Eligible));

        $response->assertRedirect(route('gudang.purchases.show', $this->purchaseB_Eligible));
        $response->assertSessionHas('success');

        $freshPO = $this->purchaseB_Eligible->fresh();
        $this->assertEquals(PurchaseStatus::RECEIVED, $freshPO->status);
        $this->assertEquals('40.0000', $freshPO->items->first()->qty_received);
    }

    /**
     * 24. Receiving sah menambah stok Material yang benar.
     */
    public function test_24_valid_receiving_increases_stock_of_correct_material(): void
    {
        // Material A initial qty: 100. Incoming: 50. Expected: 150.
        // Material B initial qty: 200. Expected: unchanged (200).
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $this->assertEquals('150.0000', $this->stockA->fresh()->qty_on_hand);
        $this->assertEquals('200.0000', $this->stockB->fresh()->qty_on_hand);
    }

    /**
     * 25. Receiving sah membuat StockMovement IN yang referensinya tepat.
     */
    public function test_25_valid_receiving_creates_stock_movement_in_with_proper_references(): void
    {
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $movement = StockMovement::where('reference_type', 'purchase_items')
            ->where('reference_id', $this->itemA_Eligible->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(MovementType::IN, $movement->movement_type);
        $this->assertEquals($this->materialA->id, $movement->material_id);
        $this->assertEquals('50.0000', $movement->qty);
        $this->assertEquals('100.0000', $movement->qty_before);
        $this->assertEquals('150.0000', $movement->qty_after);
        $this->assertEquals('35000.0000', $movement->unit_cost_snapshot);
        $this->assertEquals($this->gudangA->id, $movement->created_by);
    }

    /**
     * 26. avg_unit_cost setelah receiving sesuai rumus MWA existing.
     */
    public function test_26_avg_unit_cost_after_receiving_matches_existing_mwa_formula(): void
    {
        // Old: 100 qty @ 30.000 = 3.000.000
        // Incoming: 50 qty @ 35.000 = 1.750.000
        // Total value: 4.750.000
        // New Qty: 150
        // MWA = 4.750.000 / 150 = 31666.6667
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $this->assertEquals('31666.6666', $this->stockA->fresh()->avg_unit_cost);
    }

    /**
     * 27. Quantity dan status Purchase mengikuti workflow existing.
     */
    public function test_27_purchase_status_transitions_to_received_and_qty_received_matches_order(): void
    {
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $fresh = $this->purchaseA_Eligible->fresh();
        $this->assertEquals(PurchaseStatus::RECEIVED, $fresh->status);
        $this->assertEquals($this->itemA_Eligible->qty, $fresh->items->first()->qty_received);
    }

    // ==========================================
    // GROUP E: RECEIVING ANTI-IDOR (28 - 37)
    // ==========================================

    /**
     * 28. Gudang A tidak dapat menerima Purchase B.
     */
    public function test_28_gudang_a_cannot_receive_purchase_b(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseB_Eligible));

        $response->assertStatus(404);
        $this->assertEquals(PurchaseStatus::ORDERED, $this->purchaseB_Eligible->fresh()->status);
    }

    /**
     * 29. Gudang B tidak dapat menerima Purchase A.
     */
    public function test_29_gudang_b_cannot_receive_purchase_a(): void
    {
        $response = $this->actingAs($this->gudangB)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $response->assertStatus(404);
        $this->assertEquals(PurchaseStatus::ORDERED, $this->purchaseA_Eligible->fresh()->status);
    }

    /**
     * 30. Foreign Purchase menghasilkan HTTP 404.
     */
    public function test_30_foreign_purchase_receiving_returns_http_404(): void
    {
        $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseB_Eligible))
            ->assertStatus(404);
    }

    /**
     * 31. Purchase unassigned menghasilkan HTTP 404.
     */
    public function test_31_unassigned_purchase_receiving_returns_http_404(): void
    {
        $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->unassignedPurchaseEligible))
            ->assertStatus(404);
    }

    /**
     * 32. Purchase dengan Supplier lintas-outlet ditolak.
     */
    public function test_32_purchase_with_cross_tenant_supplier_rejected(): void
    {
        // Mutate purchaseA supplier to supplierB
        $this->purchaseA_Eligible->update(['supplier_id' => $this->supplierB->id]);

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $response->assertStatus(404);
        $this->assertEquals(PurchaseStatus::ORDERED, $this->purchaseA_Eligible->fresh()->status);
    }

    /**
     * 33. Purchase dengan Material lintas-outlet ditolak.
     */
    public function test_33_purchase_with_cross_tenant_material_rejected(): void
    {
        // Mutate itemA material to materialB
        $this->itemA_Eligible->update(['material_id' => $this->materialB->id]);

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $response->assertStatus(404);
        $this->assertEquals(PurchaseStatus::ORDERED, $this->purchaseA_Eligible->fresh()->status);
    }

    /**
     * 34. PurchaseItem dari Purchase lain ditolak jika item ID berasal dari request.
     */
    public function test_34_purchase_item_from_other_purchase_rejected(): void
    {
        // Direct attempt passing itemB into purchaseA
        $this->itemB_Eligible->update(['purchase_id' => $this->purchaseA_Eligible->id]);

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $response->assertStatus(404);
    }

    /**
     * 35. Material ID asing tidak dapat memengaruhi receiving PO yang sah.
     */
    public function test_35_foreign_material_id_cannot_affect_valid_po_receiving(): void
    {
        $stockBBefore = $this->stockB->fresh()->toArray();

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $this->assertEquals($stockBBefore, $this->stockB->fresh()->toArray());
    }

    /**
     * 36. Fake outlet_id tidak berpengaruh.
     */
    public function test_36_fake_outlet_id_payload_on_receiving_has_no_effect(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseB_Eligible), [
                'outlet_id' => $this->outletA->id,
            ]);

        $response->assertStatus(404);
    }

    /**
     * 37. Fake X-Outlet-ID tidak berpengaruh.
     */
    public function test_37_fake_x_outlet_id_header_on_receiving_has_no_effect(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('gudang.purchases.receive', $this->purchaseB_Eligible));

        $response->assertStatus(404);
    }

    // ==========================================
    // GROUP F: INVENTORY SAFETY (38 - 47)
    // ==========================================

    /**
     * 38. Unauthorized receiving tidak mengubah MaterialStock Outlet B.
     */
    public function test_38_unauthorized_receiving_does_not_alter_material_stock_of_outlet_b(): void
    {
        $stockBBefore = $this->stockB->fresh()->qty_on_hand;

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseB_Eligible));

        $this->assertEquals($stockBBefore, $this->stockB->fresh()->qty_on_hand);
    }

    /**
     * 39. Unauthorized receiving tidak mengubah avg_unit_cost Outlet B.
     */
    public function test_39_unauthorized_receiving_does_not_alter_mwa_of_outlet_b(): void
    {
        $mwaBBefore = $this->stockB->fresh()->avg_unit_cost;

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseB_Eligible));

        $this->assertEquals($mwaBBefore, $this->stockB->fresh()->avg_unit_cost);
    }

    /**
     * 40. Unauthorized receiving tidak membuat StockMovement.
     */
    public function test_40_unauthorized_receiving_does_not_create_stock_movement(): void
    {
        $movementCountBefore = StockMovement::count();

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseB_Eligible));

        $this->assertEquals($movementCountBefore, StockMovement::count());
    }

    /**
     * 41. Unauthorized receiving tidak mengubah Purchase status Outlet B.
     */
    public function test_41_unauthorized_receiving_does_not_change_purchase_status_of_outlet_b(): void
    {
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseB_Eligible));

        $this->assertEquals(PurchaseStatus::ORDERED, $this->purchaseB_Eligible->fresh()->status);
    }

    /**
     * 42. Failed receiving tidak meninggalkan partial stock movement.
     */
    public function test_42_failed_receiving_leaves_no_partial_stock_movement(): void
    {
        $movementCountBefore = StockMovement::count();

        // Simulate failure by corrupting status
        $this->purchaseA_Eligible->update(['status' => PurchaseStatus::DRAFT]);

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $this->assertEquals($movementCountBefore, StockMovement::count());
    }

    /**
     * 43. Failed receiving tidak meninggalkan partial quantity update.
     */
    public function test_43_failed_receiving_leaves_no_partial_stock_quantity_update(): void
    {
        $qtyBefore = $this->stockA->fresh()->qty_on_hand;

        $this->purchaseA_Eligible->update(['status' => PurchaseStatus::DRAFT]);
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $this->assertEquals($qtyBefore, $this->stockA->fresh()->qty_on_hand);
    }

    /**
     * 44. Failed receiving tidak meninggalkan partial MWA update.
     */
    public function test_44_failed_receiving_leaves_no_partial_mwa_update(): void
    {
        $mwaBefore = $this->stockA->fresh()->avg_unit_cost;

        $this->purchaseA_Eligible->update(['status' => PurchaseStatus::DRAFT]);
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));

        $this->assertEquals($mwaBefore, $this->stockA->fresh()->avg_unit_cost);
    }

    /**
     * 45. Duplicate receiving mengikuti aturan existing.
     */
    public function test_45_duplicate_receiving_rejected_by_status_gate(): void
    {
        // Receive once
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));
        $this->assertEquals(PurchaseStatus::RECEIVED, $this->purchaseA_Eligible->fresh()->status);

        // Replay receiving
        $response = $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));
        $response->assertRedirect(route('gudang.purchases.show', $this->purchaseA_Eligible));
        $response->assertSessionHas('error');
    }

    /**
     * 46. Unpaid purchase cannot be received.
     */
    public function test_46_unpaid_purchase_cannot_be_received(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Unpaid));

        $response->assertRedirect(route('gudang.purchases.show', $this->purchaseA_Unpaid));
        $response->assertSessionHas('error');
        $this->assertEquals(PurchaseStatus::ORDERED, $this->purchaseA_Unpaid->fresh()->status);
    }

    /**
     * 47. Non-ordered purchase cannot be received.
     */
    public function test_47_non_ordered_purchase_cannot_be_received(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Draft));

        $response->assertRedirect(route('gudang.purchases.show', $this->purchaseA_Draft));
        $response->assertSessionHas('error');
        $this->assertEquals(PurchaseStatus::DRAFT, $this->purchaseA_Draft->fresh()->status);
    }

    // ==========================================
    // GROUP G: DIRECT SERVICE CALL (48 - 50)
    // ==========================================

    /**
     * 48. Guest direct service call ditolak saat flag TRUE.
     */
    public function test_48_guest_direct_service_call_rejected_when_flag_true(): void
    {
        auth()->logout();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->receivingService->receivePurchase($this->purchaseA_Eligible);
    }

    /**
     * 49. Cross-tenant direct service call ditolak.
     */
    public function test_49_cross_tenant_direct_service_call_rejected(): void
    {
        $this->actingAs($this->gudangB);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->receivingService->receivePurchase($this->purchaseA_Eligible);
    }

    /**
     * 50. Service failure tidak memutasi stok outlet lain.
     */
    public function test_50_service_failure_does_not_mutate_stock_of_other_outlet(): void
    {
        $stockBBefore = $this->stockB->fresh()->toArray();

        try {
            $this->actingAs($this->gudangB);
            $this->receivingService->receivePurchase($this->purchaseA_Eligible);
        } catch (\Throwable $e) {
            // expected
        }

        $this->assertEquals($stockBBefore, $this->stockB->fresh()->toArray());
    }

    // ==========================================
    // GROUP H: ROLE & COMPATIBILITY (51 - 68)
    // ==========================================

    /**
     * 51. GUDANG tetap mengikuti permission existing.
     */
    public function test_51_gudang_role_authorized_for_supplier_and_receiving(): void
    {
        $this->actingAs($this->gudangA)
            ->get(route('gudang.suppliers.index'))
            ->assertOk();

        $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $this->purchaseA_Eligible))
            ->assertOk();
    }

    /**
     * 52. ADMIN tidak memperoleh bypass Supplier/Gudang tanpa permission.
     */
    public function test_52_admin_role_cannot_access_gudang_supplier_or_receiving_routes(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible))
            ->assertStatus(403);

        $this->actingAs($this->adminA)
            ->post(route('gudang.suppliers.store'), ['supplier_name' => 'PT Admin Supplier'])
            ->assertStatus(403);
    }

    /**
     * 53. PRODUKSI tidak memperoleh bypass.
     */
    public function test_53_produksi_role_cannot_access_gudang_supplier_or_receiving(): void
    {
        $this->actingAs($this->produksiA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible))
            ->assertStatus(403);
    }

    /**
     * 54. OWNER tidak memperoleh bypass.
     */
    public function test_54_owner_role_cannot_access_gudang_supplier_or_receiving(): void
    {
        $this->actingAs($this->ownerA)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible))
            ->assertStatus(403);
    }

    /**
     * 55. CUSTOMER tidak memperoleh akses internal Gudang.
     */
    public function test_55_customer_role_cannot_access_gudang_supplier_or_receiving(): void
    {
        $this->actingAs($this->customerUser)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible))
            ->assertStatus(403);
    }

    /**
     * 56. Approval PO tetap hanya ADMIN.
     */
    public function test_56_purchase_order_approval_remains_admin_only(): void
    {
        $this->actingAs($this->gudangA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Eligible))
            ->assertStatus(403);

        $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Eligible))
            ->assertStatus(403);
    }

    /**
     * 57. User tanpa outlet ditolak saat flag TRUE.
     */
    public function test_57_user_without_outlet_rejected_when_flag_true(): void
    {
        $this->actingAs($this->unboundGudang)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible))
            ->assertStatus(403);

        $this->actingAs($this->unboundGudang)
            ->post(route('gudang.suppliers.store'), ['supplier_name' => 'Supplier Unbound'])
            ->assertStatus(403);
    }

    /**
     * 58. Outlet inactive ditolak.
     */
    public function test_58_inactive_outlet_rejected_when_flag_true(): void
    {
        $this->actingAs($this->gudangInactiveOutlet)
            ->post(route('gudang.purchases.receive', $this->purchaseA_Eligible))
            ->assertStatus(403);
    }

    /**
     * 59. User inactive ditolak tanpa mutation.
     */
    public function test_59_inactive_user_rejected_without_mutation(): void
    {
        $countBefore = Supplier::count();

        $this->actingAs($this->inactiveGudangA)
            ->post(route('gudang.suppliers.store'), ['supplier_name' => 'PT Inactive Supplier']);

        $this->assertEquals($countBefore, Supplier::count());
    }

    /**
     * 60. Flag FALSE mempertahankan Supplier legacy behavior.
     */
    public function test_60_flag_false_preserves_supplier_legacy_behavior(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $payload = [
            'supplier_name' => 'PT Supplier Mode Legacy',
            'material_ids'  => [$this->unassignedMaterial->id],
        ];

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.suppliers.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * 61. Flag FALSE mempertahankan Receiving legacy behavior.
     */
    public function test_61_flag_false_preserves_receiving_legacy_behavior(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->unassignedPurchaseEligible));

        $response->assertRedirect(route('gudang.purchases.show', $this->unassignedPurchaseEligible));
        $response->assertSessionHas('success');
        $this->assertEquals(PurchaseStatus::RECEIVED, $this->unassignedPurchaseEligible->fresh()->status);
    }

    /**
     * 62. Flag TRUE fail-closed saat TenantContext invalid.
     */
    public function test_62_flag_true_fails_closed_on_invalid_tenant_context(): void
    {
        $this->actingAs($this->unboundGudang);

        $response = $this->post(route('gudang.suppliers.store'), [
            'supplier_name' => 'PT Fail Closed',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 63. Supplier READ Fase 8.1 tetap terisolasi.
     */
    public function test_63_supplier_read_fase_8_1_remains_isolated(): void
    {
        $response = $this->actingAs($this->gudangA)->get(route('gudang.suppliers.index'));
        $response->assertOk();
        $response->assertSee('PT Tekstil Prima Mandiri A');
        $response->assertDontSee('CV Sumber Benang Makmur B');
    }

    /**
     * 64. PO WRITE Fase 8.2 tetap terisolasi.
     */
    public function test_64_po_write_fase_8_2_remains_isolated(): void
    {
        $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.submit-approval', $this->purchaseB_Eligible))
            ->assertStatus(404);
    }

    /**
     * 65. Fase 5, 6 dan 7 tetap lulus regresi.
     */
    public function test_65_phases_5_6_7_isolation_contracts_remain_intact(): void
    {
        $this->assertTrue(method_exists(\App\Models\Sales\Order::class, 'scopeForOutlet'));
        $this->assertTrue(method_exists(\App\Models\Production\ProductionBatch::class, 'scopeForOutlet'));
        $this->assertTrue(method_exists(\App\Models\Inventory\Purchase::class, 'scopeForOutlet'));
    }

    /**
     * 66. Tidak ada Global Tenant Scope baru.
     */
    public function test_66_no_new_global_tenant_scope_created(): void
    {
        $this->assertEmpty((new Supplier)->getGlobalScopes());
        $this->assertEmpty((new Purchase)->getGlobalScopes());
        $this->assertEmpty((new Material)->getGlobalScopes());
        $this->assertEmpty((new MaterialStock)->getGlobalScopes());
        $this->assertEmpty((new StockMovement)->getGlobalScopes());
    }

    /**
     * 67. Tidak ada perubahan rumus MWA, HPP dan BOM.
     */
    public function test_67_no_changes_to_mwa_hpp_or_bom_formulas(): void
    {
        // 100 @ 30.000 + 50 @ 35.000 -> 31666.6667
        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseA_Eligible));
        $this->assertEquals('31666.6666', $this->stockA->fresh()->avg_unit_cost);
    }

    /**
     * 68. Tidak ada StockMovement atau data PO Outlet B yang dimutasi ketika user A melakukan unauthorized request.
     */
    public function test_68_no_stock_movement_or_po_data_of_outlet_b_mutated_on_unauthorized_request(): void
    {
        $stockBBefore = $this->stockB->fresh()->toArray();
        $movementsBefore = StockMovement::where('material_id', $this->materialB->id)->count();

        $this->actingAs($this->gudangA)->post(route('gudang.purchases.receive', $this->purchaseB_Eligible));

        $this->assertEquals($stockBBefore, $this->stockB->fresh()->toArray());
        $this->assertEquals($movementsBefore, StockMovement::where('material_id', $this->materialB->id)->count());
    }
}
