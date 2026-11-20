<?php

namespace Tests\Feature\System;

use App\Enums\ExpenseType;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessExpenseHistory;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Sales\Order;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseApprovalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Fase 8.4.5D3 Diagnostic Suite Converted to Safe Behavior in Fase 8.4.5D4A.
 *
 * Original Diagnostic Findings in Fase 8.4.5D3:
 * 1. Expense amount modifications directly changed available cash without any audit trail.
 * 2. Expense date could be moved across the initial opening cash cut-off boundary, distorting cash.
 * 3. The database schema lacked an immutable audit history table for business expenses.
 *
 * Safe Invariants Enforced in Fase 8.4.5D4A:
 * 1. Every successful expense amendment creates an immutable before/after audit history record with actor & reason.
 * 2. Moving expense_date across the opening cash cut-off boundary is strictly rejected.
 * 3. Failed or blocked amendments produce zero audit history and preserve existing records.
 * 4. Negative cash operational expense recording remains allowed, preserving PO approval cash guards.
 * 5. Legacy mode (flag FALSE) remains fully compatible without requiring amendment_reason or history entries.
 */
class FinancialExpenseAmendmentAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;

    protected User $ownerA;
    protected User $adminA;
    protected User $adminB;
    protected User $gudangA;

    protected Supplier $supplierA;
    protected Material $materialA;
    protected Customer $customerA;
    protected Order $orderA;

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

        // 2. Users
        $this->ownerA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Owner A',
            'email'     => 'owner.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->adminA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Admin A',
            'email'     => 'admin.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->adminB = User::create([
            'outlet_id' => $this->outletB->id,
            'name'      => 'Admin B',
            'email'     => 'admin.b@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->gudangA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Gudang A',
            'email'     => 'gudang.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
        ]);

        // 3. Supplier & Material
        $this->supplierA = Supplier::create([
            'outlet_id'     => $this->outletA->id,
            'supplier_name' => 'Supplier Textile A',
            'contact_phone' => '08111111111',
            'is_active'     => true,
            'created_by'    => $this->adminA->id,
        ]);
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->materialA = Material::create([
            'outlet_id'      => $this->outletA->id,
            'material_name'  => 'Kain Katun A',
            'unit_id'        => $unit->id,
            'estimated_cost' => '20000.00',
            'stock'          => '100.0000',
        ]);

        // 4. Customer & Order
        $this->customerA = Customer::create([
            'customer_name' => 'Customer A',
            'phone'         => '081234567891',
            'address'       => 'Jl. Customer A',
            'created_by'    => $this->adminA->id,
        ]);
        $this->orderA = Order::create([
            'outlet_id'    => $this->outletA->id,
            'order_no'     => 'ORD-202610-001',
            'customer_id'  => $this->customerA->id,
            'order_date'   => '2026-10-15',
            'total_amount' => '10000000.00',
            'created_by'   => $this->adminA->id,
        ]);
    }

    /**
     * Helper to establish initial cash via OWNER endpoint.
     */
    protected function establishOpeningCash(string $amount, string $date = '2026-10-10'): OpeningCashBalance
    {
        $this->actingAs($this->ownerA)->post(route('owner.opening-cash.store'), [
            'amount'            => $amount,
            'opening_date'      => $date,
            'notes'             => 'Initial balance',
            'verified_by_owner' => '1',
        ])->assertRedirect(route('owner.opening-cash.create'));

        return OpeningCashBalance::forOutlet($this->outletA->id)->firstOrFail();
    }

    /**
     * Helper to create a Purchase Order.
     */
    protected function createPurchase(string $qty, string $unitPrice): Purchase
    {
        $purchase = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'purchase_no'     => 'PO-' . uniqid(),
            'supplier_id'     => $this->supplierA->id,
            'purchase_date'   => '2026-10-15',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'created_by'      => $this->gudangA->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'material_id' => $this->materialA->id,
            'qty'         => $qty,
            'unit_price'  => $unitPrice,
            'subtotal'    => bcmul($qty, $unitPrice, 2),
        ]);

        return $purchase;
    }

    /**
     * Converted Test 1 to 4:
     * Demonstrates cash impact before and after expense amount modification WITH immutable audit history.
     * - Reducing expense amount increases cash, but now requires reason and creates an immutable history record.
     * - Increasing expense amount decreases cash, and appends a second immutable history record.
     */
    public function test_01_to_04_expense_amount_modifications_and_cash_reflection(): void
    {
        $opening = $this->establishOpeningCash('50000000.00', '2026-10-10');

        // Initial cash before expense
        $cashInitial = $this->financeService->calculateCurrentCash($opening, $this->outletA->id);
        $this->assertEquals('50000000.00', $cashInitial);

        // Record initial expense: 20.000.000
        $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-12',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Biaya Listrik Pabrik',
            'amount'       => '20000000.00',
        ])->assertRedirect();

        $expense = BusinessExpense::where('outlet_id', $this->outletA->id)->firstOrFail();

        // Cash after expense creation = 50M - 20M = 30M
        $cashAfterCreation = $this->financeService->calculateCurrentCash($opening, $this->outletA->id);
        $this->assertEquals('30000000.00', $cashAfterCreation);
        $this->assertEquals(0, BusinessExpenseHistory::where('business_expense_id', $expense->id)->count());

        // AMENDMENT 1: Admin reduces expense amount from 20M to 5M with mandatory explanatory reason
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expense), [
            'expense_date'     => '2026-10-12',
            'expense_type'     => ExpenseType::OPERATING->value,
            'description'      => 'Biaya Listrik Pabrik (Koreksi Penurunan)',
            'amount'           => '5000000.00',
            'amendment_reason' => 'Koreksi penurunan nominal faktur listrik sesuai kwitansi PLN resmi',
        ])->assertRedirect(route('admin.expenses.show', $expense));

        // Cash has increased from 30M to 45M (50M - 5M = 45M)
        $cashAfterReduction = $this->financeService->calculateCurrentCash($opening, $this->outletA->id);
        $this->assertEquals('45000000.00', $cashAfterReduction);

        // VERIFY IMMUTABLE AUDIT TRAIL RECORD 1
        $this->assertEquals(1, BusinessExpenseHistory::where('business_expense_id', $expense->id)->count());
        $history1 = BusinessExpenseHistory::where('business_expense_id', $expense->id)->first();
        $this->assertEquals('20000000.00', $history1->old_amount);
        $this->assertEquals('5000000.00', $history1->new_amount);
        $this->assertEquals($this->adminA->id, $history1->actor_user_id);
        $this->assertEquals($this->outletA->id, $history1->outlet_id);
        $this->assertEquals('Koreksi penurunan nominal faktur listrik sesuai kwitansi PLN resmi', $history1->reason);

        // AMENDMENT 2: Admin increases expense amount from 5M to 35M with mandatory explanatory reason
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expense), [
            'expense_date'     => '2026-10-12',
            'expense_type'     => ExpenseType::OPERATING->value,
            'description'      => 'Biaya Listrik Pabrik (Koreksi Kenaikan)',
            'amount'           => '35000000.00',
            'amendment_reason' => 'Koreksi penyesuaian nominal akibat denda keterlambatan dari PLN',
        ])->assertRedirect(route('admin.expenses.show', $expense));

        // Cash has decreased from 45M to 15M (50M - 35M = 15M)
        $cashAfterIncrease = $this->financeService->calculateCurrentCash($opening, $this->outletA->id);
        $this->assertEquals('15000000.00', $cashAfterIncrease);

        // VERIFY IMMUTABLE AUDIT TRAIL RECORD 2
        $this->assertEquals(2, BusinessExpenseHistory::where('business_expense_id', $expense->id)->count());
        $history2 = BusinessExpenseHistory::where('business_expense_id', $expense->id)->latest('id')->first();
        $this->assertEquals('5000000.00', $history2->old_amount);
        $this->assertEquals('35000000.00', $history2->new_amount);
        $this->assertEquals('Koreksi penyesuaian nominal akibat denda keterlambatan dari PLN', $history2->reason);
    }

    /**
     * Converted Test 5 & 6:
     * Enforces that moving expense_date across the opening cash cutoff is strictly blocked.
     * Attempting to backdate a post-cutoff expense to pre-cutoff fails validation and creates zero history.
     */
    public function test_05_and_06_moving_expense_date_across_cutoff_impacts_cash_and_commitment(): void
    {
        $opening = $this->establishOpeningCash('50000000.00', '2026-10-10');

        // Post-cutoff expense on 2026-10-15: 25.000.000
        $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-15',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Biaya Sewa Gudang',
            'amount'       => '25000000.00',
        ])->assertRedirect();

        $expense = BusinessExpense::where('outlet_id', $this->outletA->id)->firstOrFail();

        // Current cash is 50M - 25M = 25M. Available to commit = 25M.
        $this->assertEquals('25000000.00', $this->financeService->calculateCurrentCash($opening, $this->outletA->id));
        $availBefore = $this->financeService->calculateAvailableToCommit(null, $this->outletA->id);
        $this->assertEquals('25000000.00', $availBefore['available_to_commit']);

        // AMENDMENT ATTEMPT: Admin attempts to move date to 2026-10-05 (BEFORE opening date 2026-10-10)
        $response = $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expense), [
            'expense_date'     => '2026-10-05', // Crosses cut-off!
            'expense_type'     => ExpenseType::OPERATING->value,
            'description'      => 'Biaya Sewa Gudang (Backdated)',
            'amount'           => '25000000.00',
            'amendment_reason' => 'Percobaan pemindahan tanggal melintasi batas cut-off saldo kas',
        ]);

        // SAFE BEHAVIOR: Rejected with validation error on expense_date
        $response->assertSessionHasErrors(['expense_date']);

        // Invariants preserved: Database record untouched
        $this->assertEquals('2026-10-15', $expense->fresh()->expense_date->format('Y-m-d'));

        // Cash remains 25M and available_to_commit remains 25M (no artificial cash inflation)
        $cashAfterAttempt = $this->financeService->calculateCurrentCash($opening, $this->outletA->id);
        $this->assertEquals('25000000.00', $cashAfterAttempt);

        $availAfterAttempt = $this->financeService->calculateAvailableToCommit(null, $this->outletA->id);
        $this->assertEquals('25000000.00', $availAfterAttempt['available_to_commit']);

        // Zero audit history created for rejected attempt
        $this->assertEquals(0, BusinessExpenseHistory::where('business_expense_id', $expense->id)->count());
    }

    /**
     * Converted Test 7:
     * Demonstrates that reducing an expense with valid reason allows a previously rejected PO to be approved.
     */
    public function test_07_expense_reduction_expands_po_approval_capacity(): void
    {
        $opening = $this->establishOpeningCash('50000000.00', '2026-10-10');

        // Post-cutoff expense of 30M -> Cash = 20M
        $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-12',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Biaya Awal',
            'amount'       => '30000000.00',
        ])->assertRedirect();

        $expense = BusinessExpense::where('outlet_id', $this->outletA->id)->firstOrFail();
        $this->assertEquals('20000000.00', $this->financeService->calculateCurrentCash($opening, $this->outletA->id));

        // PO for 35M is rejected because available_to_commit (20M) < 35M
        $po = $this->createPurchase('350', '100000.00'); // 35M
        try {
            $this->approvalService->approvePurchase($po, $this->adminA);
            $this->fail('Expected DomainException for insufficient funds.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Dana tersedia belum mencukupi', $e->getMessage());
        }

        // Admin reduces expense from 30M to 5M with mandatory explanatory reason -> Cash is now 45M >= 35M
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expense), [
            'expense_date'     => '2026-10-12',
            'expense_type'     => ExpenseType::OPERATING->value,
            'description'      => 'Biaya Dikurangi',
            'amount'           => '5000000.00',
            'amendment_reason' => 'Koreksi pengurangan beban sewa gudang berdasarkan kesepakatan diskon',
        ])->assertRedirect(route('admin.expenses.show', $expense));

        // Exactly one history record is created
        $this->assertEquals(1, BusinessExpenseHistory::where('business_expense_id', $expense->id)->count());

        // Now PO approval succeeds!
        $this->approvalService->approvePurchase($po, $this->adminA);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $po->fresh()->approval_status);
    }

    /**
     * Converted Test 8 & 10:
     * Verifies that foreign tenant mutation remains blocked (404) and unrelated records are not modified.
     */
    public function test_08_and_10_foreign_tenant_mutation_blocked_and_unrelated_records_untouched(): void
    {
        $this->establishOpeningCash('50000000.00', '2026-10-10');

        // Expense B on Outlet B
        $expenseB = BusinessExpense::create([
            'outlet_id'    => $this->outletB->id,
            'expense_date' => '2026-10-15',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Expense Outlet B',
            'amount'       => '10000000.00',
            'created_by'   => $this->adminB->id,
        ]);

        // Admin A attempts to update Expense B -> 404
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expenseB), [
            'expense_date'     => '2026-10-15',
            'expense_type'     => ExpenseType::OPERATING->value,
            'description'      => 'Malicious Update attempt',
            'amount'           => '999999.00',
            'amendment_reason' => 'Percobaan serangan IDOR antar-outlet oleh Admin A',
        ])->assertStatus(404);

        $this->assertEquals('10000000.00', $expenseB->fresh()->amount);
        $this->assertEquals('Expense Outlet B', $expenseB->fresh()->description);

        // Zero history records created
        $this->assertEquals(0, BusinessExpenseHistory::count());
    }

    /**
     * Converted Test 9:
     * Legacy mode (flag FALSE) compatibility verification.
     */
    public function test_09_legacy_flag_false_compatibility(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        $legacyExpense = BusinessExpense::create([
            'outlet_id'    => null,
            'expense_date' => '2026-10-01',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Legacy Expense',
            'amount'       => '500000.00',
            'created_by'   => $this->adminA->id,
        ]);

        // Legacy mode update works WITHOUT amendment_reason
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $legacyExpense), [
            'expense_date' => '2026-10-01',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Legacy Expense Updated',
            'amount'       => '750000.00',
        ])->assertRedirect(route('admin.expenses.show', $legacyExpense));

        $this->assertEquals('750000.00', $legacyExpense->fresh()->amount);
        $this->assertNull($legacyExpense->fresh()->outlet_id);

        // Zero history records created under legacy flag FALSE
        $this->assertEquals(0, BusinessExpenseHistory::count());
    }

    /**
     * Converted Test 11:
     * Demonstrates that recording an operational expense exceeding opening cash produces negative cash,
     * and proves that existing PO approval is hard-blocked when cash is negative.
     */
    public function test_11_negative_cash_calculation_and_hard_gate_enforcement(): void
    {
        $opening = $this->establishOpeningCash('20000000.00', '2026-10-10');

        // Expense of 25M exceeds opening cash of 20M
        $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-12',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Biaya Operasional Melebihi Kas',
            'amount'       => '25000000.00',
        ])->assertRedirect();

        // CASH BECOMES NEGATIVE (-5.000.000,00) - APPROVED BUSINESS BEHAVIOR
        $currentCash = $this->financeService->calculateCurrentCash($opening, $this->outletA->id);
        $this->assertEquals('-5000000.00', $currentCash);

        // PO approval for even a small amount (1M) is hard-blocked because available_to_commit is negative (-5M)
        $po = $this->createPurchase('10', '100000.00'); // 1M
        try {
            $this->approvalService->approvePurchase($po, $this->adminA);
            $this->fail('Expected DomainException because cash is negative.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Dana tersedia belum mencukupi', $e->getMessage());
        }
    }

    /**
     * Converted Test 12:
     * Verifies presence and architectural integrity of the new immutable business_expense_histories table.
     */
    public function test_12_presence_and_integrity_of_dedicated_expense_audit_history_table(): void
    {
        // Assert that the dedicated immutable audit history table for business expenses now exists
        $this->assertTrue(Schema::hasTable('business_expense_histories'));

        // Assert all required audit fields exist in the schema
        $this->assertTrue(Schema::hasColumns('business_expense_histories', [
            'id',
            'business_expense_id',
            'outlet_id',
            'actor_user_id',
            'reason',
            'old_amount',
            'new_amount',
            'old_expense_date',
            'new_expense_date',
            'old_order_id',
            'new_order_id',
            'old_expense_type',
            'new_expense_type',
            'old_campaign_name',
            'new_campaign_name',
            'old_description',
            'new_description',
            'created_at',
        ]));
    }
}
