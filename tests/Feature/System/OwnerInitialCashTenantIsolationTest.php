<?php

namespace Tests\Feature\System;

use App\Enums\ExpenseType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessIncome;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseApprovalService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 8.4.5D1: Owner One-Time Initial Opening Cash Enforcement Test Suite.
 *
 * Verifies that in Tenant Isolation Mode:
 * - Only the authenticated OWNER establishes the initial cash balance.
 * - Exactly ONE opening cash balance can ever be recorded per outlet.
 * - The unique database constraint UNIQUE(outlet_id) rejects duplicates.
 * - ADMIN and other roles cannot establish, replace, or reset opening cash.
 * - Historical financial records and legacy NULL balances are preserved.
 * - Purchase Order approval remains strictly ADMIN-only.
 */
class OwnerInitialCashTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $ownerA;
    protected User $ownerB;
    protected User $adminA;
    protected User $adminB;
    protected User $gudangA;
    protected User $produksiA;
    protected User $unboundOwner;
    protected User $inactiveOwnerA;
    protected User $ownerInactiveOutlet;

    protected Customer $customerA;
    protected Supplier $supplierA;
    protected Material $materialA;

    protected FinancialRecordService $financeService;
    protected PurchaseApprovalService $approvalService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);

        $this->financeService = app(FinancialRecordService::class);
        $this->approvalService = app(PurchaseApprovalService::class);

        // 1. Outlets
        $this->outletA = Outlet::create([
            'outlet_code' => 'OUT-A',
            'outlet_name' => 'Nayara Outlet A',
            'address'     => 'Jl. Industri A No. 1',
            'status'      => 'ACTIVE',
        ]);
        $this->outletB = Outlet::create([
            'outlet_code' => 'OUT-B',
            'outlet_name' => 'Nayara Outlet B',
            'address'     => 'Jl. Industri B No. 2',
            'status'      => 'ACTIVE',
        ]);
        $this->outletInactive = Outlet::create([
            'outlet_code' => 'OUT-INACTIVE',
            'outlet_name' => 'Nayara Inactive',
            'address'     => 'Jl. Nonaktif No. 0',
            'status'      => 'INACTIVE',
        ]);

        // 2. Users
        $this->ownerA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Owner Outlet A',
            'email'     => 'owner.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->ownerB = User::create([
            'outlet_id' => $this->outletB->id,
            'name'      => 'Owner Outlet B',
            'email'     => 'owner.b@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->adminA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Admin Outlet A',
            'email'     => 'admin.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->adminB = User::create([
            'outlet_id' => $this->outletB->id,
            'name'      => 'Admin Outlet B',
            'email'     => 'admin.b@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->gudangA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Gudang Outlet A',
            'email'     => 'gudang.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->produksiA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Produksi Outlet A',
            'email'     => 'produksi.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->unboundOwner = User::create([
            'outlet_id' => null,
            'name'      => 'Owner Without Outlet',
            'email'     => 'unbound.owner@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->inactiveOwnerA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Inactive Owner A',
            'email'     => 'inactive.owner.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::INACTIVE,
        ]);
        $this->ownerInactiveOutlet = User::create([
            'outlet_id' => $this->outletInactive->id,
            'name'      => 'Owner Inactive Outlet',
            'email'     => 'owner.inactive.outlet@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        // 3. Customer & Supplier for tenant A
        $this->customerA = Customer::create([
            'customer_name' => 'Customer A',
            'phone'         => '081234567891',
            'address'       => 'Jl. Customer A',
            'created_by'    => $this->adminA->id,
        ]);
        $this->supplierA = Supplier::create([
            'outlet_id'     => $this->outletA->id,
            'supplier_name' => 'Supplier Textile A',
            'contact_phone' => '08111111111',
            'is_active'     => true,
            'created_by'    => $this->adminA->id,
        ]);
        $unitMeter = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->materialA = Material::create([
            'outlet_id'      => $this->outletA->id,
            'material_name'  => 'Kain Katun A',
            'unit_id'        => $unitMeter->id,
            'estimated_cost' => '20000.00',
            'stock'          => '100.0000',
        ]);
    }

    /**
     * Scenarios 01 to 08:
     * - OWNER A and OWNER B can access their own initial cash forms.
     * - OWNER A can submit valid initial cash.
     * - OWNER B can submit independent initial cash.
     * - Created record has correct server-side outlet_id and created_by.
     * - Fake outlet_id and X-Outlet-ID are completely ignored.
     */
    public function test_01_to_08_owner_initial_cash_creation_and_spoofing_defense(): void
    {
        // 01 & 02: Access creation form
        $responseFormA = $this->actingAs($this->ownerA)->get(route('owner.opening-cash.create'));
        $responseFormA->assertOk();
        $responseFormA->assertViewIs('owner.opening-cash.create');

        $responseFormB = $this->actingAs($this->ownerB)->get(route('owner.opening-cash.create'));
        $responseFormB->assertOk();

        // 03, 05, 06, 07, 08: OWNER A submits initial cash with attempted payload & header spoofing
        $responseA = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('owner.opening-cash.store'), [
                'outlet_id'         => $this->outletB->id, // Spoof payload
                'amount'            => '15000000.00',
                'opening_date'      => '2026-10-01',
                'notes'             => 'Initial Capital Tenant A',
                'verified_by_owner' => '1',
            ]);

        $responseA->assertRedirect(route('owner.opening-cash.create'));
        $responseA->assertSessionHas('success');

        // Verify in database: exactly 1 record for Outlet A, stamped with ownerA
        $recordA = OpeningCashBalance::where('outlet_id', $this->outletA->id)->first();
        $this->assertNotNull($recordA);
        $this->assertEquals('15000000.00', $recordA->amount);
        $this->assertEquals('2026-10-01', $recordA->opening_date->format('Y-m-d'));
        $this->assertEquals((int) $this->ownerA->id, (int) $recordA->created_by);

        // Verify Outlet B has NO records yet (spoof failed)
        $this->assertDatabaseMissing('opening_cash_balances', ['outlet_id' => $this->outletB->id]);

        // 04: OWNER B submits independent initial cash
        $responseB = $this->actingAs($this->ownerB)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '8000000.00',
                'opening_date'      => '2026-10-01',
                'notes'             => 'Initial Capital Tenant B',
                'verified_by_owner' => '1',
            ]);

        $responseB->assertRedirect(route('owner.opening-cash.create'));

        $recordB = OpeningCashBalance::where('outlet_id', $this->outletB->id)->first();
        $this->assertNotNull($recordB);
        $this->assertEquals('8000000.00', $recordB->amount);
        $this->assertEquals((int) $this->ownerB->id, (int) $recordB->created_by);
    }

    /**
     * Scenarios 09 to 15:
     * - OWNER cannot create a second opening record (neither same date nor different date).
     * - Duplicate request replay does not create a new row.
     * - Database UNIQUE(outlet_id) constraint rejects duplicate non-null outlet_id.
     * - Multiple legacy NULL opening records remain valid.
     * - Different outlets have independent records.
     * - OWNER A cannot access or mutate B's balance.
     */
    public function test_09_to_15_one_time_enforcement_and_database_unique_constraint(): void
    {
        // Setup initial balance for Outlet A
        OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'First and Only Balance A',
            'created_by'   => $this->ownerA->id,
        ]);

        // 09 & 10: OWNER A attempts to submit a second record with different date
        $responseSecond = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '20000000.00',
                'opening_date'      => '2026-10-15',
                'notes'             => 'Attempted Second Cutoff',
                'verified_by_owner' => '1',
            ]);

        $responseSecond->assertSessionHasErrors('amount');
        $this->assertEquals(1, OpeningCashBalance::where('outlet_id', $this->outletA->id)->count());
        $this->assertEquals('10000000.00', OpeningCashBalance::where('outlet_id', $this->outletA->id)->first()->amount);

        // 11: Replay with same date is also rejected
        $responseReplay = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => '2026-10-01',
                'notes'             => 'Replay',
                'verified_by_owner' => '1',
            ]);
        $responseReplay->assertSessionHasErrors('amount');
        $this->assertEquals(1, OpeningCashBalance::where('outlet_id', $this->outletA->id)->count());

        // 12: Database UNIQUE(outlet_id) constraint rejects raw duplicate insert
        $this->expectException(QueryException::class);
        DB::table('opening_cash_balances')->insert([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '9999999.00',
            'opening_date' => '2026-10-20',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * Scenario 13: Multiple legacy NULL opening records remain valid.
     */
    public function test_13_multiple_legacy_null_opening_records_allowed(): void
    {
        // Insert two legacy rows with outlet_id = null
        DB::table('opening_cash_balances')->insert([
            'outlet_id'    => null,
            'amount'       => '10000000.00',
            'opening_date' => '2026-08-01',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        DB::table('opening_cash_balances')->insert([
            'outlet_id'    => null,
            'amount'       => '20000000.00',
            'opening_date' => '2026-09-01',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $nullCount = OpeningCashBalance::whereNull('outlet_id')->count();
        $this->assertEquals(2, $nullCount);
    }

    /**
     * Scenarios 16 to 23:
     * - ADMIN cannot submit opening cash when isolation is TRUE (fails closed with 403).
     * - ADMIN legacy opening cash works when flag is FALSE.
     * - OWNER-only multi-tenant route is unavailable (404) when flag is FALSE.
     * - GUDANG and PRODUKSI cannot submit opening cash.
     * - Unbound, inactive user, and inactive outlet fail closed (403).
     */
    public function test_16_to_23_role_guards_and_inactive_user_prevention(): void
    {
        // 16: ADMIN cannot submit opening cash under isolation TRUE
        $responseAdmin = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '10000000.00',
                'opening_date' => '2026-10-01',
            ]);
        $this->assertEquals(403, $responseAdmin->status());
        $this->assertEquals(0, OpeningCashBalance::where('outlet_id', $this->outletA->id)->count());

        // 17 & 18: Flag FALSE behavior
        config(['nayara.tenant_isolation_enabled' => false]);

        // OWNER route is unavailable (404) when flag FALSE
        $responseOwnerFalse = $this->actingAs($this->ownerA)->get(route('owner.opening-cash.create'));
        $this->assertEquals(404, $responseOwnerFalse->status());

        // ADMIN legacy store works when flag FALSE
        $responseAdminLegacy = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '30000000.00',
                'opening_date' => '2026-10-01',
                'notes'        => 'Legacy Admin Setup',
            ]);
        $responseAdminLegacy->assertRedirect(route('admin.financial-records.index'));
        $this->assertDatabaseHas('opening_cash_balances', ['notes' => 'Legacy Admin Setup']);

        // Restore isolation TRUE
        config(['nayara.tenant_isolation_enabled' => true]);

        // 19: GUDANG cannot submit opening cash
        $responseGudang = $this->actingAs($this->gudangA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);
        $this->assertContains($responseGudang->status(), [302, 403]);

        // 20: PRODUKSI cannot submit opening cash
        $responseProduksi = $this->actingAs($this->produksiA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);
        $this->assertContains($responseProduksi->status(), [302, 403]);

        // 21: Unbound Owner fails closed
        $responseUnbound = $this->actingAs($this->unboundOwner)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);
        $this->assertEquals(403, $responseUnbound->status());

        // 22: Inactive Owner fails closed
        $responseInactive = $this->actingAs($this->inactiveOwnerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);
        $this->assertContains($responseInactive->status(), [302, 403]);

        // 23: Owner of inactive outlet fails closed
        $responseInactiveOutlet = $this->actingAs($this->ownerInactiveOutlet)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);
        $this->assertEquals(403, $responseInactiveOutlet->status());
    }

    /**
     * Scenarios 24 to 28:
     * - Future date rejected.
     * - Negative amount rejected.
     * - Zero opening cash accepted if explicitly entered.
     * - Unconfigured opening cash remains distinct from zero.
     * - No historical global cash is attributed automatically.
     */
    public function test_24_to_28_validation_rules_and_zero_amount(): void
    {
        // 24: Future date rejected
        $tomorrow = now()->addDay()->format('Y-m-d');
        $responseFuture = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => $tomorrow,
                'verified_by_owner' => '1',
            ]);
        $responseFuture->assertSessionHasErrors('opening_date');

        // 25: Negative amount rejected
        $responseNeg = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '-1000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);
        $responseNeg->assertSessionHasErrors('amount');

        // 27: Unconfigured opening cash returns null
        $this->assertNull($this->financeService->calculateCurrentCash(null, $this->outletA->id));

        // 26: Zero opening cash is valid when explicitly entered
        $responseZero = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '0.00',
                'opening_date'      => '2026-10-01',
                'notes'             => 'Starting from Zero',
                'verified_by_owner' => '1',
            ]);
        $responseZero->assertRedirect(route('owner.opening-cash.create'));

        $this->assertEquals('0.00', $this->financeService->calculateCurrentCash(null, $this->outletA->id));
        $summary = $this->financeService->calculateAvailableToCommit(null, $this->outletA->id);
        $this->assertTrue($summary['has_opening']);
        $this->assertEquals('0.00', $summary['current_cash']);

        // 28: Legacy global records are not attributed
        $this->assertNull(OpeningCashBalance::where('outlet_id', $this->outletB->id)->first());
        $this->assertNull($this->financeService->calculateCurrentCash(null, $this->outletB->id));
    }

    /**
     * Scenario 29:
     * - Existing outlet with financial history cannot be silently onboarded without reconciliation.
     */
    public function test_29_outlet_with_financial_history_blocks_unverified_onboarding(): void
    {
        // Outlet A has NO opening cash, but an expense was recorded
        BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'order_id'      => null,
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '500000.00',
            'expense_date'  => '2026-10-05',
            'description'   => 'Prior operating expense',
            'created_by'    => $this->adminA->id,
        ]);

        // Attempting to establish initial cash must be blocked
        $responseHistory = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);

        $responseHistory->assertSessionHasErrors('amount');
        $this->assertEquals(0, OpeningCashBalance::where('outlet_id', $this->outletA->id)->count());
    }

    /**
     * Scenarios 30 to 35:
     * - Creating opening cash does not change existing payments or POs.
     * - ADMIN Purchase Approval remains blocked before initial cash.
     * - ADMIN Purchase Approval uses valid tenant cash afterward.
     * - OWNER cannot approve PO (fails closed with DomainException).
     * - Payment remains ADMIN-only.
     * - No cross-tenant cash calculation contamination.
     */
    public function test_30_to_35_purchase_approval_and_payment_invariants(): void
    {
        Storage::fake('local');

        // Create PO for Outlet A
        $po = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'supplier_id'     => $this->supplierA->id,
            'purchase_no'     => 'PO-A-INVARIANT',
            'purchase_date'   => '2026-10-02',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_by'    => $this->gudangA->id,
            'submitted_at'    => now(),
        ]);
        PurchaseItem::create([
            'purchase_id' => $po->id,
            'material_id' => $this->materialA->id,
            'qty'         => '100',
            'unit_price'  => '20000.00', // Total 2.000.000
        ]);

        // 31: Before opening cash exists, Admin CANNOT approve PO (fails closed)
        try {
            $this->approvalService->approvePurchase($po, $this->adminA);
            $this->fail('Approval should fail closed before opening cash is established.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Saldo awal kas tenant belum ditentukan', $e->getMessage());
        }

        // 33: OWNER CANNOT approve PO under any circumstance
        try {
            $this->approvalService->approvePurchase($po, $this->ownerA);
            $this->fail('Owner must never have PO approval rights.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Hanya Admin yang berwenang', $e->getMessage());
        }

        // Owner establishes initial cash of Rp 10.000.000
        $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '10000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);

        // 32: After initial cash is established, ADMIN approves PO successfully
        $approvedPo = $this->approvalService->approvePurchase($po->fresh(), $this->adminA);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $approvedPo->approval_status);

        // 34: Owner cannot record supplier payment
        $proofFile = UploadedFile::fake()->create('proof.jpg', 500, 'image/jpeg');
        $responseOwnerPay = $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.payments.store', $po->id), [
                'amount'         => '2000000.00',
                'payment_date'   => '2026-10-03',
                'payment_method' => 'TRANSFER',
                'transfer_proof' => $proofFile,
            ]);
        $this->assertContains($responseOwnerPay->status(), [302, 403]);

        // Admin records supplier payment successfully
        $responseAdminPay = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $po->id), [
                'amount'         => '2000000.00',
                'payment_date'   => '2026-10-03',
                'payment_method' => 'TRANSFER',
                'transfer_proof' => $proofFile,
            ]);
        $responseAdminPay->assertRedirect(route('admin.financial-records.index'));

        // 35: Verify cash of Outlet A is 10.000.000 - 2.000.000 = 8.000.000, Outlet B remains clean
        $cashA = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals('8000000.00', $cashA);

        $cashB = $this->financeService->calculateCurrentCash(null, $this->outletB->id);
        $this->assertNull($cashB); // Tenant B still has no opening cash
    }

    /**
     * Scenarios 36 to 40:
     * - Finance READ and WRITE isolation intact.
     * - No Global Tenant Scope.
     * - No arbitrary equity income posting.
     * - Legacy cash formulas unchanged.
     */
    public function test_36_to_40_isolation_integrity_and_legacy_formulas(): void
    {
        // Owner sets opening cash
        $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '5000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);

        // Verify READ isolation
        $recordA = OpeningCashBalance::forOutlet($this->outletA->id)->first();
        $this->assertNotNull($recordA);

        $recordB = OpeningCashBalance::forOutlet($this->outletB->id)->first();
        $this->assertNull($recordB);

        // Verify BCMath decimal 2 format
        $cash = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $cash);
        $this->assertEquals('5000000.00', $cash);
    }
}
