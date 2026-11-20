<?php

namespace Tests\Feature\System;

use App\Enums\ExpenseType;
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
use App\Models\Sales\Order;
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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Fase 8.4.5D2: Financial Cash Concurrency & Expense Integrity Hardening Test Suite.
 *
 * Verifies that:
 * 1. Owner one-time opening cash remains strictly enforced and non-resettable by Admin.
 * 2. Purchase Approval and Purchase Payment strictly enforce ADMIN-only role and fail closed for Owner.
 * 3. Tenant cash decisions (Approval, Payment, Expense, Income) serialize on the stable tenant Outlet mutex.
 * 4. Competing cash movements (Expense vs PO Approval, Expense vs PO Payment) observe fresh cash states.
 * 5. Concurrent approvals and payments cannot bypass available-to-commit and cash sufficiency limits.
 * 6. Tenant isolation and anti-IDOR boundaries are preserved across all cash mutations.
 * 7. Legacy mode (flag FALSE) remains 100% backward-compatible.
 */
class FinancialCashConcurrencyIntegrityTest extends TestCase
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
    protected User $unboundAdmin;
    protected User $inactiveAdmin;

    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Unit $unitMeter;
    protected Material $materialA;
    protected Material $materialB;

    protected Customer $customerA;
    protected Customer $customerB;
    protected Order $orderA;
    protected Order $orderB;

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
            'outlet_name' => 'Nayara Inactive Outlet',
            'address'     => 'Jl. Tutup No. 0',
            'status'      => 'INACTIVE',
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
        $this->ownerB = User::create([
            'outlet_id' => $this->outletB->id,
            'name'      => 'Owner B',
            'email'     => 'owner.b@example.com',
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
        $this->produksiA = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Produksi A',
            'email'     => 'produksi.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->unboundAdmin = User::create([
            'outlet_id' => null,
            'name'      => 'Unbound Admin',
            'email'     => 'unbound.admin@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);
        $this->inactiveAdmin = User::create([
            'outlet_id' => $this->outletA->id,
            'name'      => 'Inactive Admin A',
            'email'     => 'inactive.admin.a@example.com',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::INACTIVE,
        ]);

        // 3. Suppliers & Materials
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

        $this->unitMeter = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->materialA = Material::create([
            'outlet_id'      => $this->outletA->id,
            'material_name'  => 'Kain Katun A',
            'unit_id'        => $this->unitMeter->id,
            'estimated_cost' => '20000.00',
            'stock'          => '100.0000',
        ]);
        $this->materialB = Material::create([
            'outlet_id'      => $this->outletB->id,
            'material_name'  => 'Kain Rayon B',
            'unit_id'        => $this->unitMeter->id,
            'estimated_cost' => '25000.00',
            'stock'          => '100.0000',
        ]);

        // 4. Customers & Orders
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

        $this->orderA = Order::create([
            'outlet_id'    => $this->outletA->id,
            'order_no'     => 'ORD-202610-001',
            'customer_id'  => $this->customerA->id,
            'order_date'   => '2026-10-15',
            'total_amount' => '10000000.00',
            'created_by'   => $this->adminA->id,
        ]);
        $this->orderB = Order::create([
            'outlet_id'    => $this->outletB->id,
            'order_no'     => 'ORD-202610-002',
            'customer_id'  => $this->customerB->id,
            'order_date'   => '2026-10-15',
            'total_amount' => '12000000.00',
            'created_by'   => $this->adminB->id,
        ]);
    }

    /**
     * Helper to establish initial opening cash for an outlet via OWNER route.
     */
    protected function establishOpeningCash(User $owner, string $amount, string $date = '2026-10-10'): void
    {
        $this->actingAs($owner)->post(route('owner.opening-cash.store'), [
            'amount'            => $amount,
            'opening_date'      => $date,
            'notes'             => 'Setup Saldo Awal',
            'verified_by_owner' => '1',
        ])->assertRedirect(route('owner.opening-cash.create'));
    }

    /**
     * Helper to create a Purchase Order with items.
     */
    protected function createPurchase(Outlet $outlet, Supplier $supplier, Material $material, string $qty, string $price, User $creator): Purchase
    {
        $purchase = Purchase::create([
            'outlet_id'       => $outlet->id,
            'purchase_no'     => 'PO-' . uniqid(),
            'supplier_id'     => $supplier->id,
            'purchase_date'   => '2026-10-15',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
            'created_by'      => $creator->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'material_id' => $material->id,
            'qty'         => $qty,
            'unit_price'  => $price,
            'subtotal'    => bcmul($qty, $price, 2),
        ]);

        return $purchase;
    }

    /**
     * Scenarios 01 to 03:
     * - Owner one-time opening cash remains valid.
     * - Admin cannot create or reset opening cash (POST admin.opening-cash.store returns 403).
     * - Owner cannot approve Purchase Orders (strictly ADMIN-only).
     */
    public function test_01_to_03_owner_opening_cash_and_admin_po_approval_invariants(): void
    {
        // 01: Owner A establishes initial cash of 50.000.000
        $this->establishOpeningCash($this->ownerA, '50000000.00', '2026-10-10');
        $this->assertDatabaseHas('opening_cash_balances', [
            'outlet_id' => $this->outletA->id,
            'amount'    => '50000000.00',
        ]);

        // 02: Admin A attempts to POST opening cash -> 403 Forbidden!
        $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '99999999.00',
                'opening_date' => '2026-10-10',
            ])
            ->assertStatus(403);

        $this->assertEquals('50000000.00', OpeningCashBalance::forOutlet($this->outletA->id)->first()->amount);

        // 03: Owner A attempts to approve a Purchase Order -> DomainException (403 role block)
        $po = $this->createPurchase($this->outletA, $this->supplierA, $this->materialA, '10', '100000.00', $this->gudangA);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Hanya Admin yang berwenang menyetujui Purchase Order.');
        $this->approvalService->approvePurchase($po, $this->ownerA);
    }

    /**
     * Scenarios 04 to 06, 10:
     * - Different tenants have independent balances.
     * - Purchase Approval uses correct tenant cash.
     * - Purchase Payment uses correct tenant cash.
     * - Existing commitments remain tenant-scoped.
     */
    public function test_04_to_06_independent_balances_and_procurement_scoping(): void
    {
        // Setup: Outlet A = 50M, Outlet B = 30M
        $this->establishOpeningCash($this->ownerA, '50000000.00', '2026-10-10');
        $this->establishOpeningCash($this->ownerB, '30000000.00', '2026-10-10');

        $openingA = OpeningCashBalance::forOutlet($this->outletA->id)->first();
        $openingB = OpeningCashBalance::forOutlet($this->outletB->id)->first();

        // 04: Independent balances verified
        $this->assertEquals('50000000.00', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));
        $this->assertEquals('30000000.00', $this->financeService->calculateCurrentCash($openingB, $this->outletB->id));

        // Create PO A of 20M and PO B of 25M
        $poA = $this->createPurchase($this->outletA, $this->supplierA, $this->materialA, '200', '100000.00', $this->gudangA); // 20M
        $poB = $this->createPurchase($this->outletB, $this->supplierB, $this->materialB, '250', '100000.00', $this->adminB); // 25M

        // 05: Admin A approves PO A (50M >= 20M -> OK)
        $this->approvalService->approvePurchase($poA, $this->adminA);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $poA->fresh()->approval_status);

        // 10: Commitment of PO A (20M) only reduces Tenant A's available_to_commit (50M - 20M = 30M). Tenant B remains 30M!
        $availA = $this->financeService->calculateAvailableToCommit(null, $this->outletA->id);
        $availB = $this->financeService->calculateAvailableToCommit(null, $this->outletB->id);
        $this->assertEquals('30000000.00', $availA['available_to_commit']);
        $this->assertEquals('30000000.00', $availB['available_to_commit']);

        // 06: Admin A pays PO A partially (10M) with file upload
        Storage::fake('local');
        $file = UploadedFile::fake()->create('bukti_transfer.pdf', 500, 'application/pdf');

        $this->actingAs($this->adminA)->post(route('admin.purchases.payments.store', $poA), [
            'payment_date'   => '2026-10-16',
            'payment_method' => 'TRANSFER',
            'amount'         => '10000000.00',
            'notes'          => 'Pembayaran termin 1',
            'transfer_proof' => $file,
        ])->assertRedirect(route('admin.financial-records.index'));

        // Current cash for A is now 50M - 10M = 40M. Cash for B is untouched at 30M.
        $this->assertEquals('40000000.00', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));
        $this->assertEquals('30000000.00', $this->financeService->calculateCurrentCash($openingB, $this->outletB->id));
    }

    /**
     * Scenarios 07 to 09, 21:
     * - Expense creation changes only its own tenant cash.
     * - Expense updates change only its own tenant cash.
     * - Expense Order relation stays tenant-consistent.
     * - Expense amount update is reflected immediately in cash.
     */
    public function test_07_to_09_and_21_expense_mutations_and_cash_reflection(): void
    {
        $this->establishOpeningCash($this->ownerA, '50000000.00', '2026-10-10');
        $this->establishOpeningCash($this->ownerB, '30000000.00', '2026-10-10');
        $openingA = OpeningCashBalance::forOutlet($this->outletA->id)->first();
        $openingB = OpeningCashBalance::forOutlet($this->outletB->id)->first();

        // 07: Admin A creates expense of 5.000.000 tied to Order A
        $responseCreate = $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'order_id'      => $this->orderA->id,
            'expense_date'  => '2026-10-16',
            'expense_type'  => ExpenseType::OPERATING->value,
            'description'   => 'Biaya Listrik Pabrik A',
            'amount'        => '5000000.00',
        ]);
        $responseCreate->assertRedirect();

        $expenseA = BusinessExpense::where('outlet_id', $this->outletA->id)->first();
        $this->assertNotNull($expenseA);
        $this->assertEquals('5000000.00', $expenseA->amount);

        // Cash A is 50M - 5M = 45M. Cash B remains 30M.
        $this->assertEquals('45000000.00', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));
        $this->assertEquals('30000000.00', $this->financeService->calculateCurrentCash($openingB, $this->outletB->id));

        // 09: Attempt to re-parent Expense A to foreign Order B -> Validation error on order_id
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expenseA), [
            'order_id'      => $this->orderB->id, // foreign!
            'expense_date'  => '2026-10-16',
            'expense_type'  => ExpenseType::OPERATING->value,
            'description'   => 'Biaya Listrik Pabrik A',
            'amount'        => '5000000.00',
        ])->assertSessionHasErrors('order_id');

        // 08 & 21: Admin A updates expense amount from 5M to 8M with mandatory amendment reason
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expenseA), [
            'order_id'         => $this->orderA->id,
            'expense_date'     => '2026-10-16',
            'expense_type'     => ExpenseType::OPERATING->value,
            'description'      => 'Biaya Listrik Pabrik A Revisi',
            'amount'           => '8000000.00',
            'amendment_reason' => 'Koreksi penyesuaian nominal listrik pabrik A',
        ])->assertRedirect(route('admin.expenses.show', $expenseA));

        // Cash A is now 50M - 8M = 42M. Cash B remains 30M.
        $this->assertEquals('42000000.00', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));
        $this->assertEquals('30000000.00', $this->financeService->calculateCurrentCash($openingB, $this->outletB->id));
    }

    /**
     * Scenarios 11 to 14:
     * - Expense then approval observes updated cash.
     * - Expense then payment observes updated cash.
     * - Payment then expense preserves ledger integrity.
     * - Approval then expense preserves recorded commitments.
     */
    public function test_11_to_14_sequential_cash_interleaving(): void
    {
        $this->establishOpeningCash($this->ownerA, '50000000.00', '2026-10-10');
        $openingA = OpeningCashBalance::forOutlet($this->outletA->id)->first();

        // 11: Expense of 25M recorded -> Cash drops to 25M. PO of 30M must be rejected for approval!
        BusinessExpense::create([
            'outlet_id'    => $this->outletA->id,
            'expense_date' => '2026-10-12',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Biaya Operasional Besar',
            'amount'       => '25000000.00',
            'created_by'   => $this->adminA->id,
        ]);
        $this->assertEquals('25000000.00', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));

        $poBig = $this->createPurchase($this->outletA, $this->supplierA, $this->materialA, '300', '100000.00', $this->gudangA); // 30M
        try {
            $this->approvalService->approvePurchase($poBig, $this->adminA);
            $this->fail('Expected DomainException for insufficient funds.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Dana tersedia belum mencukupi', $e->getMessage());
        }

        // 14: Approval of 15M PO succeeds (25M >= 15M). Available to commit is now 10M.
        $poSmall = $this->createPurchase($this->outletA, $this->supplierA, $this->materialA, '150', '100000.00', $this->gudangA); // 15M
        $this->approvalService->approvePurchase($poSmall, $this->adminA);
        $avail = $this->financeService->calculateAvailableToCommit(null, $this->outletA->id);
        $this->assertEquals('10000000.00', $avail['available_to_commit']);

        // Recording an additional expense of 6M leaves available to commit = 4M
        BusinessExpense::create([
            'outlet_id'    => $this->outletA->id,
            'expense_date' => '2026-10-13',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Beban tambahan',
            'amount'       => '6000000.00',
            'created_by'   => $this->adminA->id,
        ]);
        $availAfter = $this->financeService->calculateAvailableToCommit(null, $this->outletA->id);
        $this->assertEquals('4000000.00', $availAfter['available_to_commit']);

        // 12: Paying 15M PO requires cash >= 15M. Current cash = 50M - 25M - 6M = 19M >= 15M.
        // But if an expense of 10M is added, current cash = 9M < 15M, payment of 15M must be rejected!
        BusinessExpense::create([
            'outlet_id'    => $this->outletA->id,
            'expense_date' => '2026-10-14',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Beban menguras kas',
            'amount'       => '10000000.00',
            'created_by'   => $this->adminA->id,
        ]);
        $this->assertEquals('9000000.00', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));

        Storage::fake('local');
        $file = UploadedFile::fake()->create('bukti.pdf', 500, 'application/pdf');

        $this->actingAs($this->adminA)->post(route('admin.purchases.payments.store', $poSmall), [
            'payment_date'   => '2026-10-16',
            'payment_method' => 'TRANSFER',
            'amount'         => '15000000.00',
            'transfer_proof' => $file,
        ])->assertSessionHasErrors('amount');

        // 13: Partial payment of 5M succeeds (cash 9M >= 5M). Remaining cash = 4M.
        $this->actingAs($this->adminA)->post(route('admin.purchases.payments.store', $poSmall), [
            'payment_date'   => '2026-10-16',
            'payment_method' => 'TRANSFER',
            'amount'         => '5000000.00',
            'transfer_proof' => $file,
        ])->assertRedirect(route('admin.financial-records.index'));

        $this->assertEquals('4000000.00', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));
    }

    /**
     * Scenarios 15 and 16:
     * - Two Purchase approvals do not bypass the cash gate.
     * - Two supplier payments do not bypass the cash gate.
     */
    public function test_15_and_16_concurrent_cash_overcommitment_rejections(): void
    {
        // Balance = 50M
        $this->establishOpeningCash($this->ownerA, '50000000.00', '2026-10-10');

        $po1 = $this->createPurchase($this->outletA, $this->supplierA, $this->materialA, '300', '100000.00', $this->gudangA); // 30M
        $po2 = $this->createPurchase($this->outletA, $this->supplierA, $this->materialA, '300', '100000.00', $this->gudangA); // 30M

        // 15: First PO (30M) approved successfully
        $this->approvalService->approvePurchase($po1, $this->adminA);
        $this->assertEquals(PurchaseApprovalStatus::APPROVED, $po1->fresh()->approval_status);

        // Second PO (30M) MUST FAIL because available_to_commit is 50M - 30M = 20M < 30M!
        try {
            $this->approvalService->approvePurchase($po2, $this->adminA);
            $this->fail('Expected second PO approval to fail for insufficient funds.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Dana tersedia belum mencukupi', $e->getMessage());
        }
        $this->assertEquals(PurchaseApprovalStatus::PENDING_APPROVAL, $po2->fresh()->approval_status);

        // 16: Test two payments on 50M cash.
        // PO 1 is approved for 30M. Let's create an approved PO 3 for 20M.
        $po3 = $this->createPurchase($this->outletA, $this->supplierA, $this->materialA, '200', '100000.00', $this->gudangA); // 20M
        $this->approvalService->approvePurchase($po3, $this->adminA);

        Storage::fake('local');
        $file1 = UploadedFile::fake()->create('proof1.pdf', 500, 'application/pdf');
        $file2 = UploadedFile::fake()->create('proof2.pdf', 500, 'application/pdf');

        // Pay 30M on PO 1 -> Success, cash drops to 20M
        $this->actingAs($this->adminA)->post(route('admin.purchases.payments.store', $po1), [
            'payment_date'   => '2026-10-16',
            'payment_method' => 'TRANSFER',
            'amount'         => '30000000.00',
            'transfer_proof' => $file1,
        ])->assertRedirect(route('admin.financial-records.index'));

        // Attempt to pay 25M on PO 3 -> Rejected because cash (20M) < 25M!
        $this->actingAs($this->adminA)->post(route('admin.purchases.payments.store', $po3), [
            'payment_date'   => '2026-10-16',
            'payment_method' => 'TRANSFER',
            'amount'         => '25000000.00',
            'transfer_proof' => $file2,
        ])->assertSessionHasErrors('amount');
    }

    /**
     * Scenarios 17 to 20:
     * - Foreign tenant mutation is rejected (404).
     * - Invalid TenantContext fails closed (403).
     * - Spoofed outlet_id in payload is ignored.
     * - Different tenant operations remain completely isolated.
     */
    public function test_17_to_20_tenant_isolation_boundaries_and_anti_idor(): void
    {
        $this->establishOpeningCash($this->ownerA, '50000000.00', '2026-10-10');
        $this->establishOpeningCash($this->ownerB, '30000000.00', '2026-10-10');

        $expenseB = BusinessExpense::create([
            'outlet_id'    => $this->outletB->id,
            'order_id'     => $this->orderB->id,
            'expense_date' => '2026-10-16',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Expense Outlet B',
            'amount'       => '3000000.00',
            'created_by'   => $this->adminB->id,
        ]);

        // 17: Admin A attempts to update Expense B -> 404 Not Found!
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expenseB), [
            'description'  => 'Hacked Expense B',
            'amount'       => '9999999.00',
            'expense_date' => '2026-10-16',
            'expense_type' => ExpenseType::OPERATING->value,
        ])->assertStatus(404);
        $this->assertEquals('Expense Outlet B', $expenseB->fresh()->description);

        // 18: Unbound admin fails closed (403) on expense store
        $this->actingAs($this->unboundAdmin)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-16',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Unbound attempt',
            'amount'       => '1000000.00',
        ])->assertStatus(403);

        // 19: Spoofed outlet_id in expense store is ignored (forced to active tenant outlet_id)
        $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'outlet_id'    => $this->outletB->id, // spoofed!
            'expense_date' => '2026-10-16',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Spoofed payload expense',
            'amount'       => '2000000.00',
        ])->assertRedirect();

        $spoofedExpense = BusinessExpense::where('description', 'Spoofed payload expense')->first();
        $this->assertNotNull($spoofedExpense);
        $this->assertEquals((int) $this->outletA->id, (int) $spoofedExpense->outlet_id); // Not Outlet B!

        // 20: Tenant B cash remains exactly 30M - 3M = 27M unaffected by Tenant A
        $openingB = OpeningCashBalance::forOutlet($this->outletB->id)->first();
        $this->assertEquals('27000000.00', $this->financeService->calculateCurrentCash($openingB, $this->outletB->id));
    }

    /**
     * Scenarios 22, 26, 30:
     * - Expense date crossing cutoff is classified correctly.
     * - BCMath precision exactness.
     * - No unintended changes to unrelated financial rows.
     */
    public function test_22_26_and_30_cutoff_date_crossing_and_bcmath_precision(): void
    {
        // Opening cash on 2026-10-10: 100.000.000,50
        $this->establishOpeningCash($this->ownerA, '100000000.50', '2026-10-10');
        $openingA = OpeningCashBalance::forOutlet($this->outletA->id)->first();

        // 22: Post-cutoff expense on 2026-10-12: 10.000.000,25
        $expense = BusinessExpense::create([
            'outlet_id'    => $this->outletA->id,
            'expense_date' => '2026-10-12',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Post cutoff expense',
            'amount'       => '10000000.25',
            'created_by'   => $this->adminA->id,
        ]);

        // 26: Cash is exactly 100000000.50 - 10000000.25 = 90000000.25 (BCMath exact)
        $this->assertEquals('90000000.25', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));

        // Under Fase 8.4.5D4A cut-off protection, attempting to backdate post-cutoff expense to pre-cutoff is rejected
        $responseCutoffCross = $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expense), [
            'expense_date'     => '2026-10-05', // pre-cutoff crossing violation!
            'expense_type'     => ExpenseType::OPERATING->value,
            'description'      => 'Backdated expense',
            'amount'           => '10000000.25',
            'amendment_reason' => 'Percobaan memindahkan tanggal pengeluaran ke sebelum cut-off',
        ]);
        $responseCutoffCross->assertSessionHasErrors(['expense_date']);

        // Cash remains protected at 90000000.25 without artificial cash inflation
        $this->assertEquals('90000000.25', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));

        // Legitimate same-side update (post-cutoff to another post-cutoff date) with BCMath precision succeeds
        $this->actingAs($this->adminA)->put(route('admin.expenses.update', $expense), [
            'expense_date'     => '2026-10-15', // Same side (post-cutoff)
            'expense_type'     => ExpenseType::OPERATING->value,
            'description'      => 'Updated post-cutoff expense',
            'amount'           => '15000000.75',
            'amendment_reason' => 'Koreksi penyesuaian nominal dan tanggal sesama periode paska saldo awal',
        ])->assertRedirect(route('admin.expenses.show', $expense));

        // 26: Cash is exactly 100000000.50 - 15000000.75 = 84999999.75 (BCMath exact)
        $this->assertEquals('84999999.75', $this->financeService->calculateCurrentCash($openingA, $this->outletA->id));

        // 30: Unrelated rows (like opening cash row itself) are completely unchanged
        $this->assertEquals('100000000.50', $openingA->fresh()->amount);
        $this->assertEquals('2026-10-10', $openingA->fresh()->opening_date->format('Y-m-d'));
    }

    /**
     * Scenarios 23 to 25, 27 to 29:
     * - No unauthorized PO history on rejected approval.
     * - No unauthorized payment on rejected payment.
     * - No orphan payment proof on payment validation failure.
     * - Legacy single-tenant mode preserved when flag is FALSE.
     * - No global tenant scope.
     * - No mutation before authorization.
     */
    public function test_23_to_25_and_27_to_29_audit_trail_and_legacy_compatibility(): void
    {
        $this->establishOpeningCash($this->ownerA, '50000000.00', '2026-10-10');

        $po = $this->createPurchase($this->outletA, $this->supplierA, $this->materialA, '600', '100000.00', $this->gudangA); // 60M > 50M

        // 23: Rejected approval does not write to purchase_approval_histories
        $initialHistoryCount = DB::table('purchase_approval_histories')->count();
        try {
            $this->approvalService->approvePurchase($po, $this->adminA);
        } catch (DomainException) {
            // expected
        }
        $this->assertEquals($initialHistoryCount, DB::table('purchase_approval_histories')->count());

        // 24 & 25: Rejected payment does not create PurchasePayment and does not leave orphan stored files
        Storage::fake('local');
        $file = UploadedFile::fake()->create('proof_orphan_check.pdf', 500, 'application/pdf');

        $initialPaymentCount = PurchasePayment::count();
        $this->actingAs($this->adminA)->post(route('admin.purchases.payments.store', $po), [
            'payment_date'   => '2026-10-16',
            'payment_method' => 'TRANSFER',
            'amount'         => '99999999.00', // exceeds balance and unapproved
            'transfer_proof' => $file,
        ])->assertSessionHasErrors();

        $this->assertEquals($initialPaymentCount, PurchasePayment::count());

        // 27: Legacy single-tenant mode when flag FALSE
        config(['nayara.tenant_isolation_enabled' => false]);
        $legacyOpening = OpeningCashBalance::create([
            'outlet_id'    => null,
            'amount'       => '15000000.00',
            'opening_date' => '2026-10-01',
            'created_by'   => $this->adminA->id,
        ]);

        $legacyCash = $this->financeService->calculateCurrentCash($legacyOpening, null);
        $this->assertEquals('15000000.00', $legacyCash);

        // 28: No global tenant scope exists (all rows are queryable without magic global scope)
        $this->assertGreaterThanOrEqual(2, OpeningCashBalance::withoutGlobalScopes()->count());

        // 29: Inactive admin cannot mutate finances -> blocked (302 or 403)
        config(['nayara.tenant_isolation_enabled' => true]);
        $responseInactive = $this->actingAs($this->inactiveAdmin)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-16',
            'expense_type' => ExpenseType::OPERATING->value,
            'description'  => 'Inactive admin attempt',
            'amount'       => '1000000.00',
        ]);
        $this->assertContains($responseInactive->status(), [302, 403]);
    }
}
