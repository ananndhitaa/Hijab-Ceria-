<?php

namespace Tests\Feature\System;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcurementPaymentTenantIsolationTest extends TestCase
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
    protected User $inactiveAdminA;
    protected User $unboundAdmin;
    protected User $adminInactiveOutlet;

    protected Unit $unitMeter;

    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Supplier $unassignedSupplier;

    protected Material $materialA;
    protected Material $materialB;
    protected Material $unassignedMaterial;

    protected Purchase $purchaseA_Approved;
    protected Purchase $purchaseB_Approved;
    protected Purchase $purchaseA_Draft;
    protected Purchase $purchaseA_Cancelled;
    protected Purchase $unassignedPurchase;

    protected PurchaseItem $itemA;
    protected PurchaseItem $itemB;
    protected PurchaseItem $itemUnassigned;

    protected OpeningCashBalance $openingRecord;
    protected FinancialRecordService $financeService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);
        Storage::fake('local');

        $this->financeService = app(FinancialRecordService::class);

        // 1. Outlets
        $this->outletA = $this->createTestOutlet('OUT-PAY-A', 'Konveksi Nayara Payment A', 'ACTIVE');
        $this->outletB = $this->createTestOutlet('OUT-PAY-B', 'Konveksi Nayara Payment B', 'ACTIVE');
        $this->outletInactive = $this->createTestOutlet('OUT-PAY-INACT', 'Konveksi Inactive Payment', 'INACTIVE');

        // 2. Users
        $this->adminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.a');
        $this->adminB = $this->createTestUser($this->outletB, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.b');
        $this->gudangA = $this->createTestUser($this->outletA, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.a');
        $this->gudangB = $this->createTestUser($this->outletB, UserRole::GUDANG, UserStatus::ACTIVE, 'gudang.b');
        $this->ownerA = $this->createTestUser($this->outletA, UserRole::OWNER, UserStatus::ACTIVE, 'owner.a');
        $this->produksiA = $this->createTestUser($this->outletA, UserRole::PRODUKSI, UserStatus::ACTIVE, 'prod.a');
        $this->customerUser = $this->createTestUser($this->outletA, UserRole::CUSTOMER, UserStatus::ACTIVE, 'cust.a');
        $this->inactiveAdminA = $this->createTestUser($this->outletA, UserRole::ADMIN, UserStatus::INACTIVE, 'admin.inact');
        $this->unboundAdmin = $this->createTestUser(null, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.unbound');
        $this->adminInactiveOutlet = $this->createTestUser($this->outletInactive, UserRole::ADMIN, UserStatus::ACTIVE, 'admin.inactout');

        // 3. Units & Materials
        $this->unitMeter = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->materialA = Material::create([
            'material_name' => 'Kain Rayon Payment A',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialA->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '30000.0000',
        ]);

        $this->materialB = Material::create([
            'material_name' => 'Kain Katun Payment B',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        MaterialStock::create([
            'material_id'   => $this->materialB->id,
            'qty_on_hand'   => '150.0000',
            'avg_unit_cost' => '40000.0000',
        ]);

        $this->unassignedMaterial = Material::create([
            'material_name' => 'Kain Unassigned Legacy',
            'unit_id'       => $this->unitMeter->id,
            'active'        => true,
            'outlet_id'     => null,
        ]);
        MaterialStock::create([
            'material_id'   => $this->unassignedMaterial->id,
            'qty_on_hand'   => '50.0000',
            'avg_unit_cost' => '20000.0000',
        ]);

        // 4. Suppliers
        $this->supplierA = Supplier::create([
            'supplier_name' => 'PT Tekstil Prima Payment A',
            'phone'         => '081299991111',
            'address'       => 'Jl. Industri A No. 10',
            'email'         => 'supplierA@test.local',
            'active'        => true,
            'outlet_id'     => $this->outletA->id,
        ]);
        $this->supplierA->materials()->attach($this->materialA->id);

        $this->supplierB = Supplier::create([
            'supplier_name' => 'CV Benang Makmur Payment B',
            'phone'         => '081299992222',
            'address'       => 'Jl. Industri B No. 20',
            'email'         => 'supplierB@test.local',
            'active'        => true,
            'outlet_id'     => $this->outletB->id,
        ]);
        $this->supplierB->materials()->attach($this->materialB->id);

        $this->unassignedSupplier = Supplier::create([
            'supplier_name' => 'Toko Kain Legacy Unassigned',
            'phone'         => '081299993333',
            'address'       => 'Jl. Legacy No. 30',
            'email'         => 'legacy@test.local',
            'active'        => true,
            'outlet_id'     => null,
        ]);
        $this->unassignedSupplier->materials()->attach($this->unassignedMaterial->id);

        // 5. Purchases
        // Purchase A Approved (Total 10 x 50.000 = 500.000)
        $this->purchaseA_Approved = Purchase::create([
            'purchase_no'     => 'PO-202610-A-PAY01',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now()->subDays(2),
            'submitted_by'    => $this->gudangA->id,
            'approved_at'     => now()->subDay(),
            'approved_by'     => $this->adminA->id,
            'outlet_id'       => $this->outletA->id,
        ]);
        $this->itemA = PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Approved->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '10.0000',
            'unit_price'   => '50000.00',
            'qty_received' => '0.0000',
        ]);

        // Purchase B Approved (Total 20 x 40.000 = 800.000)
        $this->purchaseB_Approved = Purchase::create([
            'purchase_no'     => 'PO-202610-B-PAY01',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'submitted_at'    => now()->subDays(2),
            'submitted_by'    => $this->gudangB->id,
            'approved_at'     => now()->subDay(),
            'approved_by'     => $this->adminB->id,
            'outlet_id'       => $this->outletB->id,
        ]);
        $this->itemB = PurchaseItem::create([
            'purchase_id'  => $this->purchaseB_Approved->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '20.0000',
            'unit_price'   => '40000.00',
            'qty_received' => '0.0000',
        ]);

        // Purchase A Draft (Unapproved)
        $this->purchaseA_Draft = Purchase::create([
            'purchase_no'     => 'PO-202610-A-DRAFT',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Draft->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '5.0000',
            'unit_price'   => '50000.00',
            'qty_received' => '0.0000',
        ]);

        // Purchase A Cancelled
        $this->purchaseA_Cancelled = Purchase::create([
            'purchase_no'     => 'PO-202610-A-CANCELLED',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::CANCELLED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA_Cancelled->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '5.0000',
            'unit_price'   => '50000.00',
            'qty_received' => '0.0000',
        ]);

        // Unassigned Purchase
        $this->unassignedPurchase = Purchase::create([
            'purchase_no'     => 'PO-202610-UNASSIGNED',
            'supplier_id'     => $this->unassignedSupplier->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'outlet_id'       => null,
        ]);
        $this->itemUnassigned = PurchaseItem::create([
            'purchase_id'  => $this->unassignedPurchase->id,
            'material_id'  => $this->unassignedMaterial->id,
            'qty'          => '5.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '0.0000',
        ]);

        // 6. Tenant-Scoped Opening Cash: 50.000.000 per tenant
        $this->openingRecord = OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'opening_date' => '2026-10-01',
            'amount'       => '50000000.00',
            'notes'        => 'Saldo Awal Kas Test Suite 8.4 - Outlet A',
            'created_by'   => $this->adminA->id,
        ]);

        OpeningCashBalance::create([
            'outlet_id'    => $this->outletB->id,
            'opening_date' => '2026-10-01',
            'amount'       => '50000000.00',
            'notes'        => 'Saldo Awal Kas Test Suite 8.4 - Outlet B',
            'created_by'   => $this->adminB->id,
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
    // 01 - 07: PURCHASE PAYMENT READ ISOLATION
    // ==========================================

    /**
     * 01. Admin A dapat membuka halaman payment Purchase A yang eligible.
     */
    public function test_01_admin_a_can_open_payment_page_for_eligible_purchase_a(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.create', $this->purchaseA_Approved));

        $response->assertOk();
        $response->assertSee($this->purchaseA_Approved->purchase_no);
        $response->assertSee('Pembayaran Supplier');
    }

    /**
     * 02. Admin B dapat membuka halaman payment Purchase B.
     */
    public function test_02_admin_b_can_open_payment_page_for_eligible_purchase_b(): void
    {
        $response = $this->actingAs($this->adminB)
            ->get(route('admin.purchases.payments.create', $this->purchaseB_Approved));

        $response->assertOk();
        $response->assertSee($this->purchaseB_Approved->purchase_no);
    }

    /**
     * 03. Admin A tidak dapat membuka payment Purchase B (404).
     */
    public function test_03_admin_a_cannot_open_payment_page_for_purchase_b(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.create', $this->purchaseB_Approved));

        $response->assertNotFound();
    }

    /**
     * 04. Foreign Purchase GET menghasilkan 404.
     */
    public function test_04_foreign_purchase_get_returns_404(): void
    {
        $response = $this->actingAs($this->adminB)
            ->get(route('admin.purchases.payments.create', $this->purchaseA_Approved));

        $response->assertNotFound();
    }

    /**
     * 05. Payment detail/proof dari tenant lain ditolak (404).
     */
    public function test_05_payment_detail_from_other_tenant_rejected_or_show_proof_returns_404(): void
    {
        Storage::disk('local')->put('transfer-proofs/proof_b.pdf', 'Dummy PDF B');

        $paymentB = PurchasePayment::create([
            'purchase_id'         => $this->purchaseB_Approved->id,
            'amount'              => '400000.00',
            'payment_date'        => '2026-10-21',
            'payment_method'      => 'TRANSFER',
            'transfer_proof_path' => 'transfer-proofs/proof_b.pdf',
            'created_by'          => $this->adminB->id,
        ]);

        $response = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.proof', $paymentB));

        $response->assertNotFound();
    }

    /**
     * 06. Payment history/listing dan antrean unpaid dibatasi outlet aktif.
     */
    public function test_06_payment_history_listing_scoped_to_active_outlet(): void
    {
        $responseA = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.index'));

        $responseA->assertOk();
        $responseA->assertSee($this->purchaseA_Approved->purchase_no);
        $responseA->assertDontSee($this->purchaseB_Approved->purchase_no);

        $responseB = $this->actingAs($this->adminB)
            ->get(route('admin.purchases.payments.index'));

        $responseB->assertOk();
        $responseB->assertSee($this->purchaseB_Approved->purchase_no);
        $responseB->assertDontSee($this->purchaseA_Approved->purchase_no);
    }

    /**
     * 07. Payment amount aggregate tidak mencampur tenant.
     */
    public function test_07_payment_amount_aggregate_does_not_mix_tenants(): void
    {
        // Add partial payment to PO A (200.000 out of 500.000)
        PurchasePayment::create([
            'purchase_id'    => $this->purchaseA_Approved->id,
            'amount'         => '200000.00',
            'payment_date'   => '2026-10-21',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminA->id,
        ]);

        // Add partial payment to PO B (300.000 out of 800.000)
        PurchasePayment::create([
            'purchase_id'    => $this->purchaseB_Approved->id,
            'amount'         => '300000.00',
            'payment_date'   => '2026-10-21',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminB->id,
        ]);

        $summaryA = $this->financeService->calculateProcurementSummary(null, $this->outletA->id);
        $this->assertEquals('500000.00', $summaryA['total_po']);
        $this->assertEquals('200000.00', $summaryA['po_paid']);
        $this->assertEquals('300000.00', $summaryA['po_outstanding']);

        $summaryB = $this->financeService->calculateProcurementSummary(null, $this->outletB->id);
        $this->assertEquals('800000.00', $summaryB['total_po']);
        $this->assertEquals('300000.00', $summaryB['po_paid']);
        $this->assertEquals('500000.00', $summaryB['po_outstanding']);
    }

    // ==========================================
    // 08 - 18: PURCHASE PAYMENT WRITE ISOLATION
    // ==========================================

    /**
     * 08. Admin A dapat mencatat payment sah Purchase A.
     */
    public function test_08_admin_a_can_record_valid_payment_for_purchase_a(): void
    {
        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'payment_method' => 'TRANSFER_BCA',
            'reference_no'   => 'TRF-A-001',
            'notes'          => 'Pelunasan PO A',
            'transfer_proof' => UploadedFile::fake()->create('proof_a.pdf', 150, 'application/pdf'),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $response->assertRedirect(route('admin.financial-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id'    => $this->purchaseA_Approved->id,
            'amount'         => '500000.00',
            'payment_method' => 'TRANSFER_BCA',
            'created_by'     => $this->adminA->id,
        ]);

        $this->assertTrue($this->purchaseA_Approved->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $this->purchaseA_Approved->fresh()->status);
    }

    /**
     * 09. Admin B dapat mencatat payment sah Purchase B.
     */
    public function test_09_admin_b_can_record_valid_payment_for_purchase_b(): void
    {
        $payload = [
            'amount'         => '800000.00',
            'payment_date'   => '2026-10-21',
            'payment_method' => 'TRANSFER_MANDIRI',
            'reference_no'   => 'TRF-B-001',
            'notes'          => 'Pelunasan PO B',
            'transfer_proof' => UploadedFile::fake()->create('proof_b.jpg', 150, 'image/jpeg'),
        ];

        $response = $this->actingAs($this->adminB)
            ->post(route('admin.purchases.payments.store', $this->purchaseB_Approved), $payload);

        $response->assertRedirect(route('admin.financial-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('purchase_payments', [
            'purchase_id'    => $this->purchaseB_Approved->id,
            'amount'         => '800000.00',
            'payment_method' => 'TRANSFER_MANDIRI',
            'created_by'     => $this->adminB->id,
        ]);

        $this->assertTrue($this->purchaseB_Approved->fresh()->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $this->purchaseB_Approved->fresh()->status);
    }

    /**
     * 10. Admin A tidak dapat membayar Purchase B (404).
     */
    public function test_10_admin_a_cannot_pay_purchase_b(): void
    {
        $payload = [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseB_Approved), $payload);

        $response->assertNotFound();

        $this->assertDatabaseMissing('purchase_payments', [
            'purchase_id' => $this->purchaseB_Approved->id,
        ]);
    }

    /**
     * 11. Foreign Purchase POST menghasilkan 404.
     */
    public function test_11_foreign_purchase_post_returns_404(): void
    {
        $payload = [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->adminB)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $response->assertNotFound();
    }

    /**
     * 12. Purchase unassigned ditolak saat flag TRUE (404).
     */
    public function test_12_purchase_unassigned_rejected_when_flag_true(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.create', $this->unassignedPurchase))
            ->assertNotFound();

        $payload = [
            'amount'         => '50000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->unassignedPurchase), $payload)
            ->assertNotFound();
    }

    /**
     * 13. Purchase dengan Supplier lintas-outlet ditolak (404).
     */
    public function test_13_purchase_with_cross_tenant_supplier_rejected(): void
    {
        // Purchase claiming outlet A but referencing supplier B
        $crossPo = Purchase::create([
            'purchase_no'     => 'PO-CROSS-SUP',
            'supplier_id'     => $this->supplierB->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $crossPo->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '5.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '0.0000',
        ]);

        $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.create', $crossPo))
            ->assertNotFound();

        $payload = [
            'amount'         => '50000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $crossPo), $payload)
            ->assertNotFound();
    }

    /**
     * 14. Purchase dengan Material lintas-outlet ditolak (404).
     */
    public function test_14_purchase_with_cross_tenant_material_rejected(): void
    {
        // Purchase claiming outlet A but containing material B
        $crossPo = Purchase::create([
            'purchase_no'     => 'PO-CROSS-MAT',
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-20',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'outlet_id'       => $this->outletA->id,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $crossPo->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '5.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '0.0000',
        ]);

        $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.create', $crossPo))
            ->assertNotFound();

        $payload = [
            'amount'         => '50000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $crossPo), $payload)
            ->assertNotFound();
    }

    /**
     * 15. Fake outlet_id payload tidak mengubah tenant.
     */
    public function test_15_fake_outlet_id_payload_does_not_change_tenant(): void
    {
        $payload = [
            'outlet_id'      => $this->outletB->id,
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $payment = PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals($this->outletA->id, $payment->purchase->outlet_id);
    }

    /**
     * 16. Fake X-Outlet-ID tidak mengubah tenant.
     */
    public function test_16_fake_x_outlet_id_does_not_change_tenant(): void
    {
        $payload = [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $payment = PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals($this->outletA->id, $payment->purchase->outlet_id);
    }

    /**
     * 17. Fake purchase_id tidak mengalihkan payment.
     */
    public function test_17_fake_purchase_id_does_not_divert_payment(): void
    {
        $payload = [
            'purchase_id'    => $this->purchaseB_Approved->id,
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $this->assertDatabaseMissing('purchase_payments', [
            'purchase_id' => $this->purchaseB_Approved->id,
        ]);
    }

    /**
     * 18. Fake payment_id tidak mengubah tenant.
     */
    public function test_18_fake_payment_id_does_not_alter_tenant(): void
    {
        $payload = [
            'payment_id'     => 99999,
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $payment = PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals($this->outletA->id, $payment->purchase->outlet_id);
    }

    // ==========================================
    // 19 - 24: DOMAIN RULES & LIFECYCLE
    // ==========================================

    /**
     * 19. Amount validation existing tetap berlaku.
     */
    public function test_19_amount_validation_rules_enforced(): void
    {
        $payload = [
            'amount'         => '-50000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $response->assertSessionHasErrors('amount');
    }

    /**
     * 20. Outstanding amount mengikuti workflow existing.
     */
    public function test_20_outstanding_amount_follows_existing_workflow(): void
    {
        $this->assertEquals('500000.00', $this->purchaseA_Approved->fresh()->outstanding_amount);

        // Pay 150.000
        $payload = [
            'amount'         => '150000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];
        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $this->assertEquals('350000.00', $this->purchaseA_Approved->fresh()->outstanding_amount);
        $this->assertFalse($this->purchaseA_Approved->fresh()->isFullyPaid());
    }

    /**
     * 21. Overpayment ditolak jika dilarang business rules.
     */
    public function test_21_overpayment_rejected_by_business_rules(): void
    {
        $payload = [
            'amount'         => '600000.00', // PO total is 500.000
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('purchase_payments', [
            'purchase_id' => $this->purchaseA_Approved->id,
        ]);
    }

    /**
     * 22. Partial payment sah jika didukung existing.
     */
    public function test_22_partial_payment_valid_and_leaves_correct_outstanding(): void
    {
        $payload = [
            'amount'         => '200000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('200000.00', $this->purchaseA_Approved->fresh()->paid_amount);
        $this->assertEquals('300000.00', $this->purchaseA_Approved->fresh()->outstanding_amount);
        $this->assertFalse($this->purchaseA_Approved->fresh()->isFullyPaid());
    }

    /**
     * 23. Duplicate/replay tidak menghasilkan double financial posting yang melanggar.
     */
    public function test_23_duplicate_replay_does_not_allow_double_full_payment(): void
    {
        // 1st full payment (500.000)
        $payload1 = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof1.pdf', 100, 'application/pdf'),
        ];
        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload1);

        $this->assertTrue($this->purchaseA_Approved->fresh()->isFullyPaid());

        // 2nd replay payment attempt
        $payload2 = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof2.pdf', 100, 'application/pdf'),
        ];
        $response2 = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload2);

        $response2->assertSessionHasErrors('amount');
        $this->assertEquals(1, PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->count());
    }

    /**
     * 24. isFullyPaid() tetap menghasilkan state yang benar setelah pembayaran.
     */
    public function test_24_is_fully_paid_transitions_status_to_ordered(): void
    {
        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $freshPo = $this->purchaseA_Approved->fresh();
        $this->assertTrue($freshPo->isFullyPaid());
        $this->assertEquals(PurchaseStatus::ORDERED, $freshPo->status);
    }

    // ==========================================
    // 25 - 33: UNAUTHORIZED MUTATION & INTEGRITY
    // ==========================================

    /**
     * 25. Unauthorized payment tidak membuat PurchasePayment.
     */
    public function test_25_unauthorized_payment_does_not_create_purchase_payment(): void
    {
        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminB)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $this->assertEquals(0, PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->count());
    }

    /**
     * 26. Unauthorized payment tidak membuat mutasi finansial.
     */
    public function test_26_unauthorized_payment_does_not_mutate_financial_records(): void
    {
        $countBefore = PurchasePayment::count();

        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminB)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $this->assertEquals($countBefore, PurchasePayment::count());
    }

    /**
     * 27. Unauthorized payment tidak mengubah saldo tenant lain.
     */
    public function test_27_unauthorized_payment_does_not_alter_other_tenant_cash(): void
    {
        $cashA_Before = $this->financeService->calculateCurrentCash(null, $this->outletA->id);

        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminB)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $cashA_After = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals($cashA_Before, $cashA_After);
    }

    /**
     * 28. Unauthorized payment tidak mengubah status Purchase tenant lain.
     */
    public function test_28_unauthorized_payment_does_not_alter_other_tenant_purchase_status(): void
    {
        $statusBefore = $this->purchaseA_Approved->status;

        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminB)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $this->assertEquals($statusBefore, $this->purchaseA_Approved->fresh()->status);
    }

    /**
     * 29. Unauthorized payment tidak mengubah approval history.
     */
    public function test_29_unauthorized_payment_does_not_alter_approval_history(): void
    {
        $approvedBy = $this->purchaseA_Approved->approved_by;
        $approvedAt = $this->purchaseA_Approved->approved_at;

        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminB)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $fresh = $this->purchaseA_Approved->fresh();
        $this->assertEquals($approvedBy, $fresh->approved_by);
        $this->assertEquals($approvedAt->toDateTimeString(), $fresh->approved_at->toDateTimeString());
    }

    /**
     * 30. Financial cash calculation tenant A tidak memakai transaksi tenant B.
     */
    public function test_30_financial_cash_calculation_tenant_a_does_not_use_tenant_b_transactions(): void
    {
        // Tenant B records payment of 800.000
        PurchasePayment::create([
            'purchase_id'    => $this->purchaseB_Approved->id,
            'amount'         => '800000.00',
            'payment_date'   => '2026-10-21',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminB->id,
        ]);

        // Cash for tenant A must remain 50.000.000 (opening cash), unaffected by Tenant B's 800.000 outflow
        $cashA = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals('50000000.00', $cashA);

        // Cash for tenant B must be 50.000.000 - 800.000 = 49.200.000
        $cashB = $this->financeService->calculateCurrentCash(null, $this->outletB->id);
        $this->assertEquals('49200000.00', $cashB);
    }

    /**
     * 31. Payment berhasil menghasilkan finance side effect yang benar untuk tenant A.
     */
    public function test_31_payment_produces_correct_financial_cash_reduction_for_tenant_a(): void
    {
        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $cashA = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals('49500000.00', $cashA);
    }

    /**
     * 32. Finance posting tidak merujuk ke Purchase B.
     */
    public function test_32_finance_posting_does_not_reference_purchase_b(): void
    {
        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $payment = PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->first();
        $this->assertNotNull($payment);
        $this->assertNotEquals($this->purchaseB_Approved->id, $payment->purchase_id);
    }

    /**
     * 33. Transaksi gagal rollback seluruh payment dan menghapus file proof.
     */
    public function test_33_failed_transaction_rolls_back_payment_and_removes_uploaded_proof(): void
    {
        // Cancel the PO to make store fail under DB lock
        $this->purchaseA_Approved->update(['status' => PurchaseStatus::CANCELLED]);

        $payload = [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof_fail.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(0, PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->count());

        // Ensure no orphaned files in transfer-proofs
        $files = Storage::disk('local')->files('transfer-proofs');
        $this->assertEmpty($files);
    }

    // ==========================================
    // 34 - 43: TENANT CONTEXT & ROLE BOUNDARIES
    // ==========================================

    /**
     * 34. User tanpa outlet ditolak (403).
     */
    public function test_34_user_without_outlet_rejected(): void
    {
        $this->actingAs($this->unboundAdmin)
            ->get(route('admin.purchases.payments.create', $this->purchaseA_Approved))
            ->assertForbidden();

        $payload = [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->unboundAdmin)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload)
            ->assertForbidden();
    }

    /**
     * 35. Outlet inactive ditolak (403).
     */
    public function test_35_outlet_inactive_rejected(): void
    {
        $this->actingAs($this->adminInactiveOutlet)
            ->get(route('admin.purchases.payments.create', $this->purchaseA_Approved))
            ->assertForbidden();
    }

    /**
     * 36. User inactive ditolak tanpa mutation.
     */
    public function test_36_user_inactive_rejected_without_mutation(): void
    {
        $payload = [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->inactiveAdminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $response->assertRedirect(route('login'));
        $this->assertEquals(0, PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->count());
    }

    /**
     * 37. GUDANG tidak dapat mengakses payment ADMIN (403).
     */
    public function test_37_gudang_cannot_access_admin_payment_endpoints(): void
    {
        $this->actingAs($this->gudangA)
            ->get(route('admin.purchases.payments.index'))
            ->assertForbidden();

        $this->actingAs($this->gudangA)
            ->get(route('admin.purchases.payments.create', $this->purchaseA_Approved))
            ->assertForbidden();
    }

    /**
     * 38. PRODUKSI tidak dapat mengakses payment ADMIN (403).
     */
    public function test_38_produksi_cannot_access_admin_payment_endpoints(): void
    {
        $this->actingAs($this->produksiA)
            ->get(route('admin.purchases.payments.index'))
            ->assertForbidden();

        $this->actingAs($this->produksiA)
            ->get(route('admin.purchases.payments.create', $this->purchaseA_Approved))
            ->assertForbidden();
    }

    /**
     * 39. OWNER tidak memperoleh bypass payment store (403).
     */
    public function test_39_owner_cannot_bypass_admin_payment_store(): void
    {
        $payload = [
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload)
            ->assertForbidden();
    }

    /**
     * 40. CUSTOMER tidak mendapat akses finance internal (403).
     */
    public function test_40_customer_cannot_access_internal_finance(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('admin.purchases.payments.index'))
            ->assertForbidden();
    }

    /**
     * 41. Approval PO tetap hanya ADMIN.
     */
    public function test_41_po_approval_remains_admin_only(): void
    {
        $this->actingAs($this->gudangA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Draft))
            ->assertForbidden();

        $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA_Draft))
            ->assertForbidden();
    }

    /**
     * 42. Flag FALSE mempertahankan payment legacy behavior.
     */
    public function test_42_flag_false_preserves_legacy_payment_behavior(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $response = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.create', $this->unassignedPurchase));

        $response->assertOk();
    }

    /**
     * 43. Flag TRUE fail-closed jika TenantContext invalid.
     */
    public function test_43_flag_true_fails_closed_when_tenant_context_invalid(): void
    {
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->actingAs($this->unboundAdmin)
            ->get(route('admin.purchases.payments.index'))
            ->assertForbidden();
    }

    // ==========================================
    // 44 - 46: PAYMENT PROOF & SERVICE SECURITY
    // ==========================================

    /**
     * 44. Payment proof tenant B tidak dapat diakses Admin A (404).
     */
    public function test_44_payment_proof_tenant_b_inaccessible_to_admin_a(): void
    {
        Storage::disk('local')->put('transfer-proofs/secret_b.pdf', 'Confidential Payment Proof B');

        $paymentB = PurchasePayment::create([
            'purchase_id'         => $this->purchaseB_Approved->id,
            'amount'              => '800000.00',
            'payment_date'        => '2026-10-21',
            'payment_method'      => 'TRANSFER',
            'transfer_proof_path' => 'transfer-proofs/secret_b.pdf',
            'created_by'          => $this->adminB->id,
        ]);

        // Admin A forbidden / 404
        $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.proof', $paymentB))
            ->assertNotFound();

        // Admin B can access
        $this->actingAs($this->adminB)
            ->get(route('admin.purchases.payments.proof', $paymentB))
            ->assertOk();
    }

    /**
     * 45. No cross-tenant file metadata exposure.
     */
    public function test_45_no_cross_tenant_file_metadata_exposure(): void
    {
        Storage::disk('local')->put('transfer-proofs/secret_b.pdf', 'Proof Content');

        $paymentB = PurchasePayment::create([
            'purchase_id'         => $this->purchaseB_Approved->id,
            'amount'              => '800000.00',
            'payment_date'        => '2026-10-21',
            'payment_method'      => 'TRANSFER',
            'transfer_proof_path' => 'transfer-proofs/secret_b.pdf',
            'created_by'          => $this->adminB->id,
        ]);

        $response = $this->actingAs($this->adminA)
            ->get(route('admin.purchases.payments.proof', $paymentB));

        $response->assertNotFound();
        $this->assertStringNotContainsString('secret_b.pdf', $response->getContent() ?: '');
    }

    /**
     * 46. Direct financial service calculation scopes tenant cleanly.
     */
    public function test_46_direct_financial_service_calculation_scopes_tenant_cleanly(): void
    {
        // PO A has 500.000 unpaid
        // PO B has 800.000 unpaid
        $summaryA = $this->financeService->calculateProcurementSummary(null, $this->outletA->id);
        $this->assertCount(1, $summaryA['unpaid_purchases']);
        $this->assertEquals($this->purchaseA_Approved->id, $summaryA['unpaid_purchases'][0]['purchase_id']);

        $summaryB = $this->financeService->calculateProcurementSummary(null, $this->outletB->id);
        $this->assertCount(1, $summaryB['unpaid_purchases']);
        $this->assertEquals($this->purchaseB_Approved->id, $summaryB['unpaid_purchases'][0]['purchase_id']);
    }

    // ==========================================
    // 47 - 54: REGRESSION INTEGRITY & SAFETY GATES
    // ==========================================

    /**
     * 47. Fase 8.1 read isolation regressions remain sound.
     */
    public function test_47_phase_8_1_read_isolation_regressions_remain_sound(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->get(route('gudang.purchases.show', $this->purchaseB_Approved));

        $response->assertNotFound();
    }

    /**
     * 48. Fase 8.2 PO write isolation regressions remain sound.
     */
    public function test_48_phase_8_2_po_write_isolation_regressions_remain_sound(): void
    {
        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseB_Approved));

        $response->assertNotFound();
    }

    /**
     * 49. Fase 8.3 receiving isolation regressions remain sound.
     */
    public function test_49_phase_8_3_receiving_isolation_regressions_remain_sound(): void
    {
        $response = $this->actingAs($this->gudangA)
            ->post(route('gudang.purchases.receive', $this->purchaseB_Approved), [
                'received_date' => '2026-10-21',
            ]);

        $response->assertNotFound();
    }

    /**
     * 50. Downstream stock and order integrity preserved.
     */
    public function test_50_downstream_stock_and_order_integrity_preserved(): void
    {
        $stockBefore = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;

        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        // Payment must NOT alter stock on hand (receiving does that, not payment)
        $stockAfter = MaterialStock::where('material_id', $this->materialA->id)->first()->qty_on_hand;
        $this->assertEquals($stockBefore, $stockAfter);
    }

    /**
     * 51. Tidak ada Global Tenant Scope pada PurchasePayment atau Purchase.
     */
    public function test_51_no_global_tenant_scope_on_purchase_payment_or_purchase(): void
    {
        $this->assertEmpty((new PurchasePayment)->getGlobalScopes());
        $this->assertEmpty((new Purchase)->getGlobalScopes());
    }

    /**
     * 52. Tidak ada perubahan formula keuangan lama.
     */
    public function test_52_no_change_to_legacy_accounting_formula(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        // When isolation is false, calculateCurrentCash sums all records as legacy does
        PurchasePayment::create([
            'purchase_id'    => $this->purchaseA_Approved->id,
            'amount'         => '100000.00',
            'payment_date'   => '2026-10-21',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminA->id,
        ]);
        PurchasePayment::create([
            'purchase_id'    => $this->purchaseB_Approved->id,
            'amount'         => '200000.00',
            'payment_date'   => '2026-10-21',
            'payment_method' => 'TRANSFER',
            'created_by'     => $this->adminB->id,
        ]);

        $legacyCash = $this->financeService->calculateCurrentCash($this->openingRecord, null);
        // 50.000.000 - (100.000 + 200.000) = 49.700.000
        $this->assertEquals('49700000.00', $legacyCash);
    }

    /**
     * 53. Tidak ada cross-tenant mutation pada Payment, Purchase, atau Finance.
     */
    public function test_53_no_cross_tenant_mutation_on_payment_purchase_or_finance(): void
    {
        $payload = [
            'amount'         => '500000.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        // Admin A pays PO A
        $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        // Verify Outlet B state is 100% untouched
        $this->assertEquals('0.00', $this->purchaseB_Approved->fresh()->paid_amount);
        $this->assertEquals(PurchaseStatus::DRAFT, $this->purchaseB_Approved->fresh()->status);
        $this->assertEquals('50000000.00', $this->financeService->calculateCurrentCash(null, $this->outletB->id));
    }

    /**
     * 54. Tidak ada partial payment/cash movement pada kegagalan transaksi.
     */
    public function test_54_no_partial_payment_or_cash_movement_on_transaction_failure(): void
    {
        $cashBefore = $this->financeService->calculateCurrentCash(null, $this->outletA->id);

        // Intentionally trigger validation failure inside transaction with amount exceeding outstanding
        $payload = [
            'amount'         => '99999999.00',
            'payment_date'   => '2026-10-21',
            'transfer_proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $this->purchaseA_Approved), $payload);

        $response->assertSessionHasErrors('amount');

        $cashAfter = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals($cashBefore, $cashAfter);
        $this->assertEquals(0, PurchasePayment::where('purchase_id', $this->purchaseA_Approved->id)->count());
    }
}
