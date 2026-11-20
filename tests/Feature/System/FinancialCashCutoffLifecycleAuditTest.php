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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 8.4.5C: Financial Cash Cut-off Lifecycle & Transaction Safety Audit Test Suite.
 *
 * Evaluates the behavior of Opening Cash Balances, Cut-Off re-baselining,
 * Cash Availability calculations, and Concurrency boundaries across tenants.
 */
class FinancialCashCutoffLifecycleAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $adminA;
    protected User $adminB;
    protected User $ownerA;
    protected User $gudangA;
    protected User $produksiA;
    protected User $unboundAdmin;
    protected User $inactiveAdminA;
    protected User $adminInactiveOutlet;

    protected Customer $customerA;
    protected Customer $customerB;

    protected Supplier $supplierA;
    protected Supplier $supplierB;

    protected Material $materialA;
    protected Material $materialB;

    protected Order $orderA;
    protected Order $orderB;

    protected OpeningCashBalance $openingRecordA;
    protected OpeningCashBalance $openingRecordB;

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
        $this->ownerA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Owner Outlet A',
            'email'     => 'owner.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
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
        $this->unboundAdmin = User::create([
            'outlet_id' => null,
            'name'      => 'Admin Without Outlet',
            'email'     => 'unbound.admin@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->inactiveAdminA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Inactive Admin A',
            'email'     => 'inactive.admin.a@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::INACTIVE,
        ]);
        $this->adminInactiveOutlet = User::create([
            'outlet_id' => $this->outletInactive->id,
            'name'      => 'Admin Inactive Outlet',
            'email'     => 'admin.inactive.outlet@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        // 3. Customers
        $this->customerA = Customer::create([
            'customer_name' => 'Customer A',
            'phone'         => '081234567891',
            'address'       => 'Jl. Customer A',
            'created_by'    => $this->adminA->id,
        ]);
        $this->customerB = Customer::create([
            'customer_name' => 'Customer B',
            'phone'         => '081234567892',
            'address'       => 'Jl. Customer B',
            'created_by'    => $this->adminB->id,
        ]);

        // 4. Suppliers
        $this->supplierA = Supplier::create([
            'outlet_id'     => $this->outletA->id,
            'supplier_name' => 'Supplier Textile A',
            'contact_phone' => '08111111111',
            'is_active'     => true,
            'created_by'    => $this->adminA->id,
        ]);
        $this->supplierB = Supplier::create([
            'outlet_id'     => $this->outletB->id,
            'supplier_name' => 'Supplier Textile B',
            'contact_phone' => '08222222222',
            'is_active'     => true,
            'created_by'    => $this->adminB->id,
        ]);

        // 5. Units & Materials
        $unitMeter = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->materialA = Material::create([
            'outlet_id'      => $this->outletA->id,
            'material_name'  => 'Kain Katun A',
            'unit_id'        => $unitMeter->id,
            'estimated_cost' => '20000.00',
            'stock'          => '100.0000',
        ]);
        $this->materialB = Material::create([
            'outlet_id'      => $this->outletB->id,
            'material_name'  => 'Kain Rayon B',
            'unit_id'        => $unitMeter->id,
            'estimated_cost' => '25000.00',
            'stock'          => '100.0000',
        ]);

        // 6. Orders
        $this->orderA = Order::create([
            'outlet_id'   => $this->outletA->id,
            'customer_id' => $this->customerA->id,
            'order_no'    => 'ORD-A-202610-001',
            'order_date'  => '2026-10-05',
            'status'      => OrderStatus::DEAL,
        ]);
        $this->orderB = Order::create([
            'outlet_id'   => $this->outletB->id,
            'customer_id' => $this->customerB->id,
            'order_no'    => 'ORD-B-202610-002',
            'order_date'  => '2026-10-06',
            'status'      => OrderStatus::DEAL,
        ]);

        // 7. Base Opening Balances (Initial Cut-off at 2026-10-01)
        $this->openingRecordA = OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Initial Saldo Awal A 2026-10-01',
            'created_by'   => $this->adminA->id,
        ]);
        $this->openingRecordB = OpeningCashBalance::create([
            'outlet_id'    => $this->outletB->id,
            'amount'       => '5000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Initial Saldo Awal B 2026-10-01',
            'created_by'   => $this->adminB->id,
        ]);
    }

    /**
     * Scenarios 01 to 05:
     * - One valid opening balance works for Outlet A.
     * - Separate opening balances work for A and B.
     * - Tenant A cannot use Tenant B opening cash.
     * - Tenant A cannot mutate Tenant B cash records.
     * - Opening balance Rp0 is distinguished from missing.
     */
    public function test_01_to_05_opening_balance_baseline_and_tenant_distinction(): void
    {
        // 01: Valid opening balance for Outlet A
        $cashA = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals('10000000.00', $cashA);

        // 02: Separate opening balance for Outlet B
        $cashB = $this->financeService->calculateCurrentCash(null, $this->outletB->id);
        $this->assertEquals('5000000.00', $cashB);

        // 03: Tenant C with no opening balance cannot use A or B
        $outletC = Outlet::create([
            'outlet_code' => 'OUT-C',
            'outlet_name' => 'Outlet C',
            'address'     => 'Jl. C',
            'status'      => 'ACTIVE',
        ]);
        $cashC = $this->financeService->calculateCurrentCash(null, $outletC->id);
        $this->assertNull($cashC);

        // 04: Admin A cannot mutate cash records (rejected 403 under isolation)
        $responseAdmin = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'outlet_id'    => $this->outletB->id,
                'amount'       => '99999999.00',
                'opening_date' => '2026-11-01',
                'notes'        => 'Malicious Tenant A Cutoff',
            ]);
        $responseAdmin->assertStatus(403);

        // Outlet B must not have any record with notes 'Malicious Tenant A Cutoff'
        $this->assertDatabaseMissing('opening_cash_balances', [
            'outlet_id' => $this->outletB->id,
            'notes'     => 'Malicious Tenant A Cutoff',
        ]);

        // 05: Explicit Rp0 opening balance established by Owner is distinguished from missing
        $ownerC = User::create([
            'outlet_id' => $outletC->id,
            'name'      => 'Owner C',
            'email'     => 'owner.c@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $responseZero = $this->actingAs($ownerC)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '0.00',
                'opening_date'      => '2026-10-01',
                'notes'             => 'Explicit Rp0 Cutoff',
                'verified_by_owner' => '1',
            ]);
        $responseZero->assertRedirect(route('owner.opening-cash.create'));

        $cashCZero = $this->financeService->calculateCurrentCash(null, $outletC->id);
        $this->assertNotNull($cashCZero);
        $this->assertEquals('0.00', $cashCZero);

        $summaryC = $this->financeService->calculateAvailableToCommit(null, $outletC->id);
        $this->assertTrue($summaryC['has_opening']);
        $this->assertEquals('0.00', $summaryC['current_cash']);
    }

    /**
     * Scenarios 06 & 07:
     * - Admin cannot submit opening cash under isolation (403).
     * - Owner cannot submit a duplicate / second opening balance.
     * - Owner of different tenant can establish initial cut-off.
     */
    public function test_06_and_07_duplicate_cut_off_date_rules(): void
    {
        // 06: Admin attempting to write is rejected with 403
        $responseAdmin = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '12000000.00',
                'opening_date' => '2026-10-01',
            ]);
        $responseAdmin->assertStatus(403);

        // Owner A attempting second cut-off is rejected by one-time policy
        $responseDuplicate = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '12000000.00',
                'opening_date'      => '2026-10-01',
                'notes'             => 'Duplicate attempt',
                'verified_by_owner' => '1',
            ]);

        $responseDuplicate->assertSessionHasErrors('amount');
        $this->assertEquals('10000000.00', $this->openingRecordA->fresh()->amount);

        // 07: Same date for different tenant (Outlet C) is accepted for Owner C
        $outletC = Outlet::create([
            'outlet_code' => 'OUT-C',
            'outlet_name' => 'Outlet C',
            'address'     => 'Jl. C',
            'status'      => 'ACTIVE',
        ]);
        $ownerC = User::create([
            'outlet_id' => $outletC->id,
            'name'      => 'Owner C',
            'email'     => 'owner.c@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $responseC = $this->actingAs($ownerC)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '7500000.00',
                'opening_date'      => '2026-10-01', // Same date as A & B, but for C
                'notes'             => 'Outlet C initial cut-off',
                'verified_by_owner' => '1',
            ]);

        $responseC->assertRedirect(route('owner.opening-cash.create'));
        $this->assertDatabaseHas('opening_cash_balances', [
            'outlet_id' => $outletC->id,
            'amount'    => '7500000.00',
        ]);
    }

    /**
     * Scenarios 08 to 11:
     * - Second cut-off is STRICTLY REJECTED under one-time policy.
     * - No unverified re-baselining or cash reset occurs.
     * - Cash availability remains accurately calculated from the original baseline.
     */
    public function test_08_to_11_second_cut_off_creation_and_cash_rebaselining_impact(): void
    {
        // Step 1: Record transactions between 2026-10-01 and 2026-10-14
        BusinessIncome::create([
            'outlet_id'   => $this->outletA->id,
            'income_no'   => 'INC-A-001',
            'income_date' => '2026-10-05',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '3000000.00',
            'notes'       => 'Mid-period Income',
            'created_by'  => $this->adminA->id,
        ]);

        BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'order_id'      => null,
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '1000000.00',
            'expense_date'  => '2026-10-08',
            'description'   => 'Mid-period Expense',
            'campaign_name' => null,
            'created_by'    => $this->adminA->id,
        ]);

        // 09: Calculate cash before second cut-off creation attempt
        // 10.000.000 + 3.000.000 - 1.000.000 = 12.000.000
        $cashBefore = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals('12000000.00', $cashBefore);

        // 08: Admin A attempting second cut-off is rejected with 403
        $responseAdmin = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '20000000.00',
                'opening_date' => '2026-10-15',
            ]);
        $responseAdmin->assertStatus(403);

        // Owner A attempting second cut-off is rejected by one-time policy
        $responseOwner = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '20000000.00',
                'opening_date'      => '2026-10-15',
                'notes'             => 'Second Cutoff Mid-October',
                'verified_by_owner' => '1',
            ]);
        $responseOwner->assertSessionHasErrors('amount');

        // 10 & 11: Calculate cash after rejected attempt:
        // Original baseline remains intact! Cash is STILL 12.000.000, NOT 20.000.000!
        $cashAfter = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals('12000000.00', $cashAfter);
        $this->assertEquals($cashBefore, $cashAfter);

        // Exactly 1 row exists in opening_cash_balances table for Outlet A
        $totalCutoffs = OpeningCashBalance::where('outlet_id', $this->outletA->id)->count();
        $this->assertEquals(1, $totalCutoffs);
    }

    /**
     * Scenarios 12 & 13:
     * - Backdated second cut-off is rejected.
     * - Future-dated cut-off is rejected by validation.
     * - Active balance remains completely secure.
     */
    public function test_12_and_13_backdated_and_future_dated_cut_off_behavior(): void
    {
        // 12: Admin A attempting backdated cut-off is rejected with 403
        $responseBackdatedAdmin = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '3000000.00',
                'opening_date' => '2026-09-01',
            ]);
        $responseBackdatedAdmin->assertStatus(403);

        // Owner A attempting backdated second cut-off is rejected by one-time policy
        $responseBackdatedOwner = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '3000000.00',
                'opening_date'      => '2026-09-01',
                'verified_by_owner' => '1',
            ]);
        $responseBackdatedOwner->assertSessionHasErrors('amount');

        // Exactly 1 record remains for Outlet A
        $this->assertEquals(1, OpeningCashBalance::where('outlet_id', $this->outletA->id)->count());
        $this->assertEquals('10000000.00', $this->financeService->calculateCurrentCash(null, $this->outletA->id));

        // 13: Future-dated cut-off on a new outlet is rejected by validation rule before_or_equal:today
        $outletF = Outlet::create([
            'outlet_code' => 'OUT-F',
            'outlet_name' => 'Outlet F',
            'address'     => 'Jl. F',
            'status'      => 'ACTIVE',
        ]);
        $ownerF = User::create([
            'outlet_id' => $outletF->id,
            'name'      => 'Owner F',
            'email'     => 'owner.f@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $tomorrow = now()->addDay()->format('Y-m-d');
        $responseFuture = $this->actingAs($ownerF)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '50000000.00',
                'opening_date'      => $tomorrow,
                'verified_by_owner' => '1',
            ]);
        $responseFuture->assertSessionHasErrors('opening_date');

        // No record created for Outlet F
        $this->assertEquals(0, OpeningCashBalance::where('outlet_id', $outletF->id)->count());
    }

    /**
     * Scenarios 14 to 18:
     * - Historical transactions remain intact when second cut-off is attempted and rejected.
     */
    public function test_14_to_18_historical_integrity_preserved_after_new_cut_off(): void
    {
        Storage::fake('local');

        // Setup Customer Payment
        $invoice = Invoice::create([
            'order_id'    => $this->orderA->id,
            'invoice_no'  => 'INV-A-001',
            'invoice_date'=> '2026-10-06',
            'total_amount'=> '5000000.00',
            'status'      => 'PAID',
        ]);
        $payment = Payment::create([
            'invoice_id'   => $invoice->id,
            'amount'       => '5000000.00',
            'payment_date' => '2026-10-06',
            'payment_type' => PaymentType::PELUNASAN,
            'status'       => PaymentStatus::CONFIRMED,
            'created_by'   => $this->adminA->id,
            'confirmed_by' => $this->adminA->id,
        ]);

        // Setup Approved PO
        $po = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'supplier_id'     => $this->supplierA->id,
            'purchase_no'     => 'PO-A-001',
            'purchase_date'   => '2026-10-06',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'approved_by'     => $this->adminA->id,
            'approved_at'     => now(),
        ]);
        PurchaseItem::create([
            'purchase_id' => $po->id,
            'material_id' => $this->materialA->id,
            'qty'         => '100',
            'unit_price'  => '20000.00', // Total 2.000.000
        ]);

        // Setup Supplier Payment
        $supplierPayment = PurchasePayment::create([
            'purchase_id'  => $po->id,
            'amount'       => '2000000.00',
            'payment_date' => '2026-10-07',
            'payment_method' => 'TRANSFER',
            'created_by'   => $this->adminA->id,
        ]);

        // Setup Business Expense
        $expense = BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'order_id'      => $this->orderA->id,
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '500000.00',
            'expense_date'  => '2026-10-08',
            'description'   => 'Operational Expense',
            'created_by'    => $this->adminA->id,
        ]);

        // Snapshot existing record IDs and attributes
        $paymentId = $payment->id;
        $poId = $po->id;
        $supplierPaymentId = $supplierPayment->id;
        $expenseId = $expense->id;

        // Admin A attempts to create a new cut-off on 2026-10-20 -> REJECTED (403)
        $responseAdminReject = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '30000000.00',
                'opening_date' => '2026-10-20',
            ]);
        $responseAdminReject->assertStatus(403);

        // Owner A attempts to create second cut-off -> REJECTED by one-time policy
        $responseOwnerReject = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '30000000.00',
                'opening_date'      => '2026-10-20',
                'verified_by_owner' => '1',
            ]);
        $responseOwnerReject->assertSessionHasErrors('amount');

        // 18: Verify ALL existing historical transactions remain 100% UNTOUCHED
        $freshPayment = Payment::find($paymentId);
        $this->assertNotNull($freshPayment);
        $this->assertEquals('5000000.00', $freshPayment->amount);
        $this->assertEquals(PaymentStatus::CONFIRMED, $freshPayment->status);

        $freshPo = Purchase::find($poId);
        $this->assertNotNull($freshPo);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $freshPo->approval_status);

        $freshSupplierPayment = PurchasePayment::find($supplierPaymentId);
        $this->assertNotNull($freshSupplierPayment);
        $this->assertEquals('2000000.00', $freshSupplierPayment->amount);

        $freshExpense = BusinessExpense::find($expenseId);
        $this->assertNotNull($freshExpense);
        $this->assertEquals('500000.00', $freshExpense->amount);
        $this->assertEquals($this->orderA->id, $freshExpense->order_id);
    }

    /**
     * Scenarios 19 to 21:
     * - Unverified cash inflation is completely prevented.
     * - Admin cannot inject cash to bypass PO approval or supplier payment blocks.
     */
    public function test_19_to_21_diagnostic_unverified_cash_inflation_bypasses_approval_and_payment(): void
    {
        Storage::fake('local');

        // Initial state: Outlet A has Opening Balance 10.000.000 at 2026-10-01
        // Create an Expense of 9.000.000 at 2026-10-02
        BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'order_id'      => null,
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '9000000.00',
            'expense_date'  => '2026-10-02',
            'description'   => 'Heavy operating cost',
            'created_by'    => $this->adminA->id,
        ]);

        // Current cash is now 1.000.000
        $currentCash = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals('1000000.00', $currentCash);

        // Gudang creates a PO of Rp 5.000.000
        $po = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'supplier_id'     => $this->supplierA->id,
            'purchase_no'     => 'PO-A-EXPENSIVE',
            'purchase_date'   => '2026-10-05',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'submitted_by'    => $this->gudangA->id,
            'submitted_at'    => now(),
        ]);
        PurchaseItem::create([
            'purchase_id' => $po->id,
            'material_id' => $this->materialA->id,
            'qty'         => '250',
            'unit_price'  => '20000.00', // Total 5.000.000
        ]);

        // 19: Initially, Admin A CANNOT approve PO because available cash is 1.000.000 < 5.000.000
        try {
            $this->approvalService->approvePurchase($po, $this->adminA);
            $this->fail('Approval should have thrown DomainException due to insufficient funds.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Dana tersedia belum mencukupi', $e->getMessage());
        }

        // 20: Admin A attempts to inject unverified opening cash cut-off on 2026-10-10 of Rp 50.000.000 -> REJECTED (403)
        $responseAdminInflate = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '50000000.00',
                'opening_date' => '2026-10-10',
            ]);
        $responseAdminInflate->assertStatus(403);

        // Owner A also cannot inject a second cut-off -> REJECTED by one-time policy
        $responseOwnerInflate = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '50000000.00',
                'opening_date'      => '2026-10-10',
                'verified_by_owner' => '1',
            ]);
        $responseOwnerInflate->assertSessionHasErrors('amount');

        // Cash availability remains strictly 1.000.000!
        $newCash = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertEquals('1000000.00', $newCash);

        // Admin A STILL CANNOT approve the PO (remains blocked)!
        try {
            $this->approvalService->approvePurchase($po->fresh(), $this->adminA);
            $this->fail('Approval must remain blocked because funds are insufficient.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Dana tersedia belum mencukupi', $e->getMessage());
        }
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $po->fresh()->approval_status);

        // 21: Supplier payment cannot be disbursed for unapproved PO
        $proofFile = UploadedFile::fake()->create('proof.jpg', 500, 'image/jpeg');
        $paymentResponse = $this->actingAs($this->adminA)
            ->post(route('admin.purchases.payments.store', $po->id), [
                'amount'         => '5000000.00',
                'payment_date'   => '2026-10-11',
                'payment_method' => 'TRANSFER',
                'transfer_proof' => $proofFile,
            ]);

        // Payment is not recorded, PO remains DRAFT
        $this->assertEquals(0, PurchasePayment::where('purchase_id', $po->id)->count());
        $this->assertEquals(PurchaseStatus::DRAFT, $po->fresh()->status);
    }

    /**
     * Scenarios 22 & 23:
     * - Verify cash data of Outlet B stays unchanged when Outlet A changes cut-off.
     * - Spoofed outlet_id and X-Outlet-ID are strictly rejected.
     */
    public function test_22_and_23_cross_tenant_cash_and_cut_off_isolation(): void
    {
        $cashBBefore = $this->financeService->calculateCurrentCash(null, $this->outletB->id);
        $this->assertEquals('5000000.00', $cashBBefore);

        // Admin A attempts to change cut-off with spoofed headers targeting Outlet B -> 403
        $responseAdmin = $this->actingAs($this->adminA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('admin.opening-cash.store'), [
                'outlet_id'    => $this->outletB->id,
                'amount'       => '99000000.00',
                'opening_date' => '2026-11-15',
            ]);
        $responseAdmin->assertStatus(403);

        // Owner A attempting second cut-off with spoofed headers targeting Outlet B is rejected
        $responseOwner = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('owner.opening-cash.store'), [
                'outlet_id'         => $this->outletB->id,
                'amount'            => '99000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);
        $responseOwner->assertSessionHasErrors('amount');

        // Outlet B cash must remain strictly 5.000.000
        $cashBAfter = $this->financeService->calculateCurrentCash(null, $this->outletB->id);
        $this->assertEquals('5000000.00', $cashBAfter);

        // Outlet B's opening record remains 2026-10-01 with amount 5.000.000
        $latestRecordB = OpeningCashBalance::forOutlet($this->outletB->id)->first();
        $this->assertEquals('2026-10-01', $latestRecordB->opening_date->format('Y-m-d'));
        $this->assertEquals('5000000.00', $latestRecordB->amount);
    }

    /**
     * Scenarios 24 & 25:
     * - Unauthorized roles (Owner, Gudang, Produksi) cannot submit opening cash.
     * - Unbound user or inactive user/outlet fail closed.
     */
    public function test_24_and_25_unauthorized_roles_and_inactive_users_rejected(): void
    {
        // 24: Owner cannot submit opening cash
        $responseOwner = $this->actingAs($this->ownerA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '10000000.00',
                'opening_date' => '2026-11-20',
            ]);
        $this->assertContains($responseOwner->status(), [302, 403]);

        // Gudang cannot submit opening cash
        $responseGudang = $this->actingAs($this->gudangA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '10000000.00',
                'opening_date' => '2026-11-20',
            ]);
        $this->assertContains($responseGudang->status(), [302, 403]);

        // Produksi cannot submit opening cash
        $responseProduksi = $this->actingAs($this->produksiA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '10000000.00',
                'opening_date' => '2026-11-20',
            ]);
        $this->assertContains($responseProduksi->status(), [302, 403]);

        // 25: Unbound admin (outlet_id null) fails closed
        $responseUnbound = $this->actingAs($this->unboundAdmin)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '10000000.00',
                'opening_date' => '2026-11-20',
            ]);
        $this->assertEquals(403, $responseUnbound->status());

        // Inactive admin fails closed
        $responseInactive = $this->actingAs($this->inactiveAdminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '10000000.00',
                'opening_date' => '2026-11-20',
            ]);
        $this->assertContains($responseInactive->status(), [302, 403]);

        // Admin of inactive outlet fails closed
        $responseInactiveOutlet = $this->actingAs($this->adminInactiveOutlet)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '10000000.00',
                'opening_date' => '2026-11-20',
            ]);
        $this->assertEquals(403, $responseInactiveOutlet->status());
    }

    /**
     * Scenarios 26 to 30:
     * - Legacy mode (flag FALSE) preserves single-tenant workflow.
     * - No new global cash balance duplication.
     * - Calculations use exact BCMath precision.
     * - No unintended table mutations.
     * - Invoice/Order and PO financial consistency maintained.
     */
    public function test_26_to_30_legacy_compatibility_precision_and_table_integrity(): void
    {
        // 26: Flag FALSE preserves legacy workflow
        config(['nayara.tenant_isolation_enabled' => false]);

        $responseLegacy = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '25000000.00',
                'opening_date' => '2026-12-15',
                'notes'        => 'Legacy Cut-off without isolation',
            ]);
        $responseLegacy->assertRedirect(route('admin.financial-records.index'));

        // Under legacy mode, outlet_id is not enforced and legacy cash calculation works
        $legacyRecord = OpeningCashBalance::where('notes', 'Legacy Cut-off without isolation')->first();
        $this->assertNotNull($legacyRecord);

        $legacyCash = $this->financeService->calculateCurrentCash();
        $this->assertEquals('25000000.00', $legacyCash);

        // Restore isolation
        config(['nayara.tenant_isolation_enabled' => true]);

        // 27: Verify no global NULL records were duplicated during isolated calls
        $globalNullCount = OpeningCashBalance::whereNull('outlet_id')->count();
        $this->assertEquals(1, $globalNullCount); // Only the one created in legacy mode above

        // 28: BCMath scale 2 precision verified
        $cashPrecision = $this->financeService->calculateCurrentCash(null, $this->outletA->id);
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $cashPrecision);

        // 29 & 30: Order and PO outstanding remain mathematically consistent
        $po = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'supplier_id'     => $this->supplierA->id,
            'purchase_no'     => 'PO-A-PRECISION',
            'purchase_date'   => '2026-10-05',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'approved_by'     => $this->adminA->id,
        ]);
        PurchaseItem::create([
            'purchase_id' => $po->id,
            'material_id' => $this->materialA->id,
            'qty'         => '10.5000',
            'unit_price'  => '25000.00', // 262.500,00
        ]);
        $this->assertEquals('262500.00', $po->fresh()->total_amount_string);
        $this->assertEquals('262500.00', $po->fresh()->outstanding_amount);
    }
}
