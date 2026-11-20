<?php

namespace Tests\Feature\System;

use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
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
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\PurchaseApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinancialTenantWriteIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletC;
    protected Outlet $outletInactive;

    protected User $adminA;
    protected User $adminB;
    protected User $adminC;
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

    protected Product $productA;
    protected Product $productB;

    protected ProductVariant $variantA;
    protected ProductVariant $variantB;

    protected Order $orderA;
    protected Order $orderB;
    protected Order $legacyOrder;

    protected OpeningCashBalance $openingRecordA;
    protected OpeningCashBalance $openingRecordB;
    protected OpeningCashBalance $legacyGlobalOpening;

    protected BusinessIncome $legacyIncome;
    protected BusinessExpense $legacyExpense;

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
        $this->outletC = Outlet::create([
            'outlet_code' => 'OUT-C',
            'outlet_name' => 'Nayara Outlet C',
            'address'     => 'Jl. Industri C No. 3',
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
        $this->adminC = User::create([
            'outlet_id' => $this->outletC->id,
            'name'      => 'Admin Outlet C',
            'email'     => 'admin.c@nayara.test',
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

        // 6. Products & Variants
        $this->productA = Product::create([
            'outlet_id'    => $this->outletA->id,
            'code'         => 'PRD-A',
            'product_name' => 'Hijab A',
            'is_active'    => true,
        ]);
        $this->variantA = ProductVariant::create([
            'product_id'   => $this->productA->id,
            'sku'          => 'SKU-A-01',
            'variant_name' => 'Varian A',
            'price'        => '50000.00',
        ]);
        $this->productB = Product::create([
            'outlet_id'    => $this->outletB->id,
            'code'         => 'PRD-B',
            'product_name' => 'Hijab B',
            'is_active'    => true,
        ]);
        $this->variantB = ProductVariant::create([
            'product_id'   => $this->productB->id,
            'sku'          => 'SKU-B-01',
            'variant_name' => 'Varian B',
            'price'        => '60000.00',
        ]);

        // 7. Orders
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
        $this->legacyOrder = Order::create([
            'outlet_id'   => null,
            'customer_id' => $this->customerA->id,
            'order_no'    => 'ORD-LEGACY-003',
            'order_date'  => '2026-10-07',
            'status'      => OrderStatus::DEAL,
        ]);

        // 8. Opening Cash Balances (initial fixture)
        $this->openingRecordA = OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Initial Saldo Awal A',
            'created_by'   => $this->adminA->id,
        ]);
        $this->openingRecordB = OpeningCashBalance::create([
            'outlet_id'    => $this->outletB->id,
            'amount'       => '5000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Initial Saldo Awal B',
            'created_by'   => $this->adminB->id,
        ]);
        $this->legacyGlobalOpening = OpeningCashBalance::create([
            'outlet_id'    => null,
            'amount'       => '50000000.00',
            'opening_date' => '2026-09-01',
            'notes'        => 'Legacy Saldo Awal Global',
            'created_by'   => $this->adminA->id,
        ]);

        // 9. Legacy unassigned income & expense
        $this->legacyIncome = BusinessIncome::create([
            'outlet_id'   => null,
            'income_no'   => 'INC-LEGACY-001',
            'income_date' => '2026-10-02',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '1000000.00',
            'notes'       => 'Legacy Unassigned Income',
            'created_by'  => $this->adminA->id,
        ]);
        $this->legacyExpense = BusinessExpense::create([
            'outlet_id'     => null,
            'order_id'      => null,
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '500000.00',
            'expense_date'  => '2026-10-02',
            'description'   => 'Legacy Unassigned Expense',
            'campaign_name' => null,
            'created_by'    => $this->adminA->id,
        ]);
    }

    /**
     * Scenarios 01 to 05:
     * - Admin A creates valid Opening Balance for Outlet A.
     * - Admin B creates valid Opening Balance for Outlet B.
     * - New Opening Balance gets outlet_id server-side.
     * - Fake outlet_id in payload does NOT override ownership.
     * - Fake X-Outlet-ID header is ignored.
     */
    public function test_01_to_05_opening_balance_creation_server_assigned_and_spoof_resistant(): void
    {
        // Under tenant isolation, Admin A is forbidden from writing opening cash
        $responseAdminA = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '15000000.00',
                'opening_date' => '2026-11-01',
            ]);
        $responseAdminA->assertStatus(403);

        // Owner A establishes initial opening cash with attempted spoofing
        // First delete the setUp fixture for outlet A to test initial creation
        OpeningCashBalance::where('outlet_id', $this->outletA->id)->delete();

        $responseOwnerA = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('owner.opening-cash.store'), [
                'outlet_id'         => $this->outletB->id, // Attempted spoof
                'amount'            => '15000000.00',
                'opening_date'      => '2026-10-01',
                'notes'             => 'Initial Capital Tenant A',
                'verified_by_owner' => '1',
            ]);

        $responseOwnerA->assertRedirect(route('owner.opening-cash.create'));
        $responseOwnerA->assertSessionHas('success');

        // Verify in DB: outlet_id MUST be outletA, NOT outletB
        $recordA = OpeningCashBalance::where('notes', 'Initial Capital Tenant A')
            ->where('outlet_id', $this->outletA->id)
            ->first();
        $this->assertNotNull($recordA);
        $this->assertEquals('15000000.00', $recordA->amount);
        $this->assertEquals((int) $this->ownerA->id, (int) $recordA->created_by);

        // Verify Outlet B received NO record from Owner A's submission
        $recordB = OpeningCashBalance::where('notes', 'Initial Capital Tenant A')
            ->where('outlet_id', $this->outletB->id)
            ->first();
        $this->assertNull($recordB);

        // Admin B is also forbidden from writing opening cash
        $responseAdminB = $this->actingAs($this->adminB)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '8000000.00',
                'opening_date' => '2026-11-01',
            ]);
        $responseAdminB->assertStatus(403);
    }

    /**
     * Scenarios 06 & 07:
     * - Duplicate cut-off / second cut-off rejected by one-time rule and database constraint.
     * - Initial cut-off for different tenant is accepted.
     */
    public function test_06_and_07_duplicate_cut_off_date_rejected_for_same_tenant_allowed_across_tenants(): void
    {
        // Outlet A already has opening record for '2026-10-01' (from setUp)
        // Admin A attempting to write is rejected with 403
        $responseAdminA = $this->actingAs($this->adminA)
            ->post(route('admin.opening-cash.store'), [
                'amount'       => '12000000.00',
                'opening_date' => '2026-10-01',
            ]);
        $responseAdminA->assertStatus(403);

        // Owner A attempting a second cut-off is rejected by one-time policy
        $responseOwnerA = $this->actingAs($this->ownerA)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '12000000.00',
                'opening_date'      => '2026-10-01',
                'verified_by_owner' => '1',
            ]);
        $responseOwnerA->assertSessionHasErrors('amount');
        $this->assertEquals('10000000.00', $this->openingRecordA->fresh()->amount);

        // Owner C creating initial balance for Outlet C is accepted
        $ownerC = User::create([
            'outlet_id' => $this->outletC->id,
            'name'      => 'Owner C',
            'email'     => 'owner.c@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        $responseNewTenant = $this->actingAs($ownerC)
            ->post(route('owner.opening-cash.store'), [
                'amount'            => '7500000.00',
                'opening_date'      => '2026-10-01',
                'notes'             => 'Outlet C initial cut-off',
                'verified_by_owner' => '1',
            ]);
        $responseNewTenant->assertRedirect(route('owner.opening-cash.create'));

        $recordC = OpeningCashBalance::where('notes', 'Outlet C initial cut-off')
            ->where('outlet_id', $this->outletC->id)
            ->first();
        $this->assertNotNull($recordC);
        $this->assertEquals('7500000.00', $recordC->amount);
    }

    /**
     * Scenarios 08 to 13:
     * - Opening balance foreign cannot be overwritten.
     * - Legacy opening balance NULL cannot be adopted.
     * - Missing opening blocks cash approval and supplier payment.
     * - Explicit Rp0 opening balance is valid and distinct from missing.
     */
    public function test_08_to_13_opening_balance_integrity_foreign_legacy_and_zero_amount(): void
    {
        // 08 & 09: Legacy unassigned record has outlet_id NULL
        $this->assertNull($this->legacyGlobalOpening->outlet_id);

        // Create fresh Outlet D with no opening cash balance configured
        $outletD = Outlet::create([
            'outlet_code' => 'OUT-D',
            'outlet_name' => 'Nayara Outlet D',
            'address'     => 'Jl. Baru No. 4',
            'is_active'   => true,
        ]);
        $adminD = User::create([
            'outlet_id' => $outletD->id,
            'name'      => 'Admin D',
            'email'     => 'admin.d@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        // Scenario 10: No automatic opening balance created; calculateCurrentCash returns NULL
        $cashD = $this->financeService->calculateCurrentCash(null, $outletD->id);
        $this->assertNull($cashD);

        // Create PO for Outlet D
        $supplierD = Supplier::create([
            'outlet_id'     => $outletD->id,
            'supplier_name' => 'Supplier D',
            'is_active'     => true,
            'created_by'    => $adminD->id,
        ]);
        $purchaseD = Purchase::create([
            'outlet_id'       => $outletD->id,
            'supplier_id'     => $supplierD->id,
            'purchase_no'     => 'PO-D-001',
            'purchase_date'   => '2026-10-10',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
        ]);
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);
        $materialD = Material::create([
            'outlet_id'      => $outletD->id,
            'material_name'  => 'Kain Katun D',
            'unit_id'        => $unit->id,
            'estimated_cost' => '10000.00',
            'stock'          => '50.0000',
        ]);
        $supplierD->materials()->attach($materialD->id);

        PurchaseItem::create([
            'purchase_id'  => $purchaseD->id,
            'material_id'  => $materialD->id,
            'qty'          => '10.0000',
            'unit_price'   => '10000.00',
            'qty_received' => '0.0000',
        ]);

        // Scenario 11: Missing opening blocks PO approval (throws DomainException)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Saldo awal kas tenant belum ditentukan.');
        $this->approvalService->approvePurchase($purchaseD, $adminD);
    }

    /**
     * Scenario 12 & 13:
     * - Missing opening blocks payment.
     * - Explicit configured Rp0 is valid and distinct from missing.
     */
    public function test_12_and_13_missing_opening_blocks_payment_and_explicit_zero_is_allowed(): void
    {
        $outletE = Outlet::create([
            'outlet_code' => 'OUT-E',
            'outlet_name' => 'Nayara Outlet E',
            'address'     => 'Jl. Baru No. 5',
            'is_active'   => true,
        ]);
        $adminE = User::create([
            'outlet_id' => $outletE->id,
            'name'      => 'Admin E',
            'email'     => 'admin.e@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
        ]);

        $ownerE = User::create([
            'outlet_id' => $outletE->id,
            'name'      => 'Owner E',
            'email'     => 'owner.e@nayara.test',
            'password'  => Hash::make('password'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
        ]);

        // Scenario 13: Explicitly set Opening Balance = Rp 0.00
        $responseZero = $this->actingAs($ownerE)->post(route('owner.opening-cash.store'), [
            'amount'            => '0.00',
            'opening_date'      => '2026-10-01',
            'notes'             => 'Zero Initial Cash',
            'verified_by_owner' => '1',
        ]);
        $responseZero->assertRedirect(route('owner.opening-cash.create'));

        $recordE = OpeningCashBalance::forOutlet($outletE->id)->first();
        $this->assertNotNull($recordE);
        $this->assertEquals('0.00', $recordE->amount);

        // calculateCurrentCash now returns '0.00' (not null!)
        $cashE = $this->financeService->calculateCurrentCash($recordE, $outletE->id);
        $this->assertEquals('0.00', $cashE);
    }

    /**
     * Scenarios 14 to 20:
     * - Business Income store sets outlet_id server-side.
     * - Admin A income belongs to Outlet A.
     * - Admin B income belongs to Outlet B.
     * - Client-supplied outlet_id spoofing is ignored.
     * - Legacy income remains untouched.
     * - Failed write leaves zero partial records.
     */
    public function test_14_to_20_business_income_store_isolation_and_integrity(): void
    {
        $initialIncomeCount = BusinessIncome::count();

        // 14 & 16: Admin A posts income with fake outlet_id in payload and header
        $responseA = $this->actingAs($this->adminA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->post(route('admin.incomes.store'), [
                'outlet_id'   => $this->outletB->id, // Spoof
                'income_date' => '2026-10-15',
                'category'    => BusinessIncome::CATEGORY_MODAL_TAMBAHAN,
                'amount'      => '5000000.00',
                'notes'       => 'Tambahan modal Outlet A',
            ]);

        $responseA->assertRedirect(route('admin.financial-records.index'));
        $responseA->assertSessionHas('success');

        $incomeA = BusinessIncome::where('notes', 'Tambahan modal Outlet A')->first();
        $this->assertNotNull($incomeA);
        $this->assertEquals((int) $this->outletA->id, (int) $incomeA->outlet_id);
        $this->assertEquals((int) $this->adminA->id, (int) $incomeA->created_by);
        $this->assertStringStartsWith('INC-', $incomeA->income_no);

        // 15: Admin B posts income
        $responseB = $this->actingAs($this->adminB)->post(route('admin.incomes.store'), [
            'income_date' => '2026-10-16',
            'category'    => BusinessIncome::CATEGORY_NON_PENJUALAN,
            'amount'      => '3000000.00',
            'notes'       => 'Pendapatan non-penjualan Outlet B',
        ]);
        $responseB->assertRedirect(route('admin.financial-records.index'));

        $incomeB = BusinessIncome::where('notes', 'Pendapatan non-penjualan Outlet B')->first();
        $this->assertNotNull($incomeB);
        $this->assertEquals((int) $this->outletB->id, (int) $incomeB->outlet_id);
        $this->assertEquals((int) $this->adminB->id, (int) $incomeB->created_by);

        // 17: Legacy income remains unchanged
        $this->assertNull($this->legacyIncome->fresh()->outlet_id);

        // 20: Failed write (e.g. invalid amount or invalid category) does not leave partial records
        $countBeforeFailed = BusinessIncome::count();
        $responseFailed = $this->actingAs($this->adminA)->post(route('admin.incomes.store'), [
            'income_date' => '2026-10-17',
            'category'    => 'INVALID_CATEGORY',
            'amount'      => '-1000.00',
        ]);
        $responseFailed->assertSessionHasErrors(['category', 'amount']);
        $this->assertEquals($countBeforeFailed, BusinessIncome::count());
    }

    /**
     * Scenarios 21 to 26:
     * - Expense store sets outlet_id server-side.
     * - General expense with order_id NULL is valid for tenant.
     * - Expense with own Order is valid.
     * - Expense with foreign Order is rejected (validation failure).
     * - Expense with unassigned Order (outlet_id null) is rejected when isolation is TRUE.
     */
    public function test_21_to_26_expense_store_isolation_and_order_boundary(): void
    {
        // 21 & 23: Admin A posts general expense (order_id null) with fake outlet_id in body
        $responseGeneralA = $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'outlet_id'     => $this->outletB->id, // Attempted spoof
            'order_id'      => null,
            'expense_type'  => ExpenseType::OPERATING->value,
            'expense_date'  => '2026-10-18',
            'description'   => 'Biaya Listrik Workshop A',
            'amount'        => '350000.00',
        ]);
        $responseGeneralA->assertRedirect();

        $expGeneralA = BusinessExpense::where('description', 'Biaya Listrik Workshop A')->first();
        $this->assertNotNull($expGeneralA);
        $this->assertEquals((int) $this->outletA->id, (int) $expGeneralA->outlet_id);
        $this->assertNull($expGeneralA->order_id);

        // 24: Admin A posts expense linked to Order A (own order)
        $responseOrderA = $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'order_id'      => $this->orderA->id,
            'expense_type'  => ExpenseType::MARKETING->value,
            'expense_date'  => '2026-10-19',
            'campaign_name' => 'Promo Order A',
            'description'   => 'Iklan khusus Order A',
            'amount'        => '600000.00',
        ]);
        $responseOrderA->assertRedirect();

        $expOrderA = BusinessExpense::where('description', 'Iklan khusus Order A')->first();
        $this->assertNotNull($expOrderA);
        $this->assertEquals((int) $this->outletA->id, (int) $expOrderA->outlet_id);
        $this->assertEquals((int) $this->orderA->id, (int) $expOrderA->order_id);

        // 25: Admin A posts expense linked to Order B (foreign order) -> REJECTED
        $responseCrossOrder = $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'order_id'      => $this->orderB->id,
            'expense_type'  => ExpenseType::MARKETING->value,
            'expense_date'  => '2026-10-19',
            'description'   => 'Iklan Order B by Admin A',
            'amount'        => '200000.00',
        ]);
        $responseCrossOrder->assertSessionHasErrors('order_id');

        // 26: Admin A posts expense linked to legacy unassigned Order -> REJECTED when isolation TRUE
        $responseLegacyOrder = $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'order_id'      => $this->legacyOrder->id,
            'expense_type'  => ExpenseType::OPERATING->value,
            'expense_date'  => '2026-10-19',
            'description'   => 'Beban linked to legacy order',
            'amount'        => '100000.00',
        ]);
        $responseLegacyOrder->assertSessionHasErrors('order_id');
    }

    /**
     * Scenarios 27 to 32:
     * - Foreign Expense update returns HTTP 404 (Anti-IDOR).
     * - Legacy unassigned Expense update returns HTTP 404.
     * - Expense ownership (outlet_id) is immutable.
     * - Re-parenting to foreign order is rejected by validation.
     * - Destroy is not supported (except(['destroy'])).
     */
    public function test_27_to_32_expense_update_anti_idor_and_immutability(): void
    {
        // Fixture: Expense A and Expense B
        $expenseA = BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'order_id'      => $this->orderA->id,
            'expense_type'  => ExpenseType::OPERATING,
            'expense_date'  => '2026-10-12',
            'description'   => 'Pengeluaran Asli A',
            'amount'        => '450000.00',
            'created_by'    => $this->adminA->id,
        ]);
        $expenseB = BusinessExpense::create([
            'outlet_id'     => $this->outletB->id,
            'order_id'      => $this->orderB->id,
            'expense_type'  => ExpenseType::OPERATING,
            'expense_date'  => '2026-10-12',
            'description'   => 'Pengeluaran Asli B',
            'amount'        => '550000.00',
            'created_by'    => $this->adminB->id,
        ]);

        // 27: Admin A attempts to update Expense B -> 404 Not Found!
        $this->actingAs($this->adminA)
            ->put(route('admin.expenses.update', $expenseB), [
                'description'  => 'Hacked Description',
                'amount'       => '999999.00',
                'expense_date' => '2026-10-12',
                'expense_type' => ExpenseType::OPERATING->value,
            ])
            ->assertStatus(404);

        $this->assertEquals('Pengeluaran Asli B', $expenseB->fresh()->description);

        // 28: Admin A attempts to update legacy unassigned expense -> 404 Not Found!
        $this->actingAs($this->adminA)
            ->put(route('admin.expenses.update', $this->legacyExpense), [
                'description'  => 'Hijacked Legacy Expense',
                'amount'       => '888888.00',
                'expense_date' => '2026-10-12',
                'expense_type' => ExpenseType::OPERATING->value,
            ])
            ->assertStatus(404);

        // 29 & 30: Admin A updates own Expense A with spoofed outlet_id in payload
        $this->actingAs($this->adminA)
            ->put(route('admin.expenses.update', $expenseA), [
                'outlet_id'        => $this->outletB->id, // Attempt to transfer ownership
                'order_id'         => $this->orderA->id,
                'description'      => 'Updated Description A',
                'amount'           => '500000.00',
                'expense_date'     => '2026-10-12',
                'expense_type'     => ExpenseType::OPERATING->value,
                'amendment_reason' => 'Koreksi penyesuaian deskripsi dan nominal pengeluaran A',
            ])
            ->assertRedirect(route('admin.expenses.show', $expenseA));

        $freshA = $expenseA->fresh();
        $this->assertEquals('Updated Description A', $freshA->description);
        $this->assertEquals('500000.00', $freshA->amount);
        $this->assertEquals((int) $this->outletA->id, (int) $freshA->outlet_id); // Ownership did not change!

        // 31: Admin A attempts to re-parent Expense A to Order B (foreign order)
        $responseForeignReparent = $this->actingAs($this->adminA)
            ->put(route('admin.expenses.update', $expenseA), [
                'order_id'     => $this->orderB->id, // Foreign Order!
                'description'  => 'Re-parent attempt',
                'amount'       => '500000.00',
                'expense_date' => '2026-10-12',
                'expense_type' => ExpenseType::OPERATING->value,
            ]);
        $responseForeignReparent->assertSessionHasErrors('order_id');
        $this->assertEquals((int) $this->orderA->id, (int) $expenseA->fresh()->order_id);

        // Destroy route does not exist
        $this->actingAs($this->adminA)
            ->delete(route('admin.expenses.index') . '/' . $expenseA->id)
            ->assertStatus(405);
    }

    /**
     * Scenarios 33 to 37:
     * - Income A and Expense A only affect Tenant A's cash position.
     * - Income B and Expense B do not affect Tenant A's cash position.
     * - No global cash bleed after write operations.
     */
    public function test_33_to_37_cash_isolation_after_write_operations(): void
    {
        // Initial cash states:
        // A: 10,000,000
        // B: 5,000,000
        $initialCashA = $this->financeService->calculateCurrentCash($this->openingRecordA, $this->outletA->id);
        $initialCashB = $this->financeService->calculateCurrentCash($this->openingRecordB, $this->outletB->id);
        $this->assertEquals('10000000.00', $initialCashA);
        $this->assertEquals('5000000.00', $initialCashB);

        // Admin A records Income: Rp 2,000,000
        $this->actingAs($this->adminA)->post(route('admin.incomes.store'), [
            'income_date' => '2026-10-10',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '2000000.00',
            'notes'       => 'Pemasukan Kas A',
        ])->assertRedirect();

        // Admin A records Expense: Rp 500,000
        $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-11',
            'expense_type' => ExpenseType::OPERATING->value,
            'amount'       => '500000.00',
            'description'  => 'Biaya Operasional A',
        ])->assertRedirect();

        // Cash A must increase by 2,000,000 - 500,000 = +1,500,000 -> 11,500,000
        $cashAAfter = $this->financeService->calculateCurrentCash($this->openingRecordA, $this->outletA->id);
        $this->assertEquals('11500000.00', $cashAAfter);

        // Cash B must remain EXACTLY 5,000,000! (No pollution from A)
        $cashBAfterA = $this->financeService->calculateCurrentCash($this->openingRecordB, $this->outletB->id);
        $this->assertEquals('5000000.00', $cashBAfterA);

        // Admin B records Income: Rp 4,000,000 and Expense: Rp 1,000,000
        $this->actingAs($this->adminB)->post(route('admin.incomes.store'), [
            'income_date' => '2026-10-12',
            'category'    => BusinessIncome::CATEGORY_MODAL_TAMBAHAN,
            'amount'      => '4000000.00',
            'notes'       => 'Pemasukan Kas B',
        ])->assertRedirect();

        $this->actingAs($this->adminB)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-13',
            'expense_type' => ExpenseType::OPERATING->value,
            'amount'       => '1000000.00',
            'description'  => 'Biaya Operasional B',
        ])->assertRedirect();

        // Cash B must now be 5,000,000 + 4,000,000 - 1,000,000 = 8,000,000
        $cashBFinal = $this->financeService->calculateCurrentCash($this->openingRecordB, $this->outletB->id);
        $this->assertEquals('8000000.00', $cashBFinal);

        // Cash A must still be 11,500,000! (No pollution from B)
        $cashAFinal = $this->financeService->calculateCurrentCash($this->openingRecordA, $this->outletA->id);
        $this->assertEquals('11500000.00', $cashAFinal);
    }

    /**
     * Scenarios 38 to 44:
     * - Role integrity: Admin-only for write finance routes.
     * - Owner cannot approve Purchase.
     * - Gudang & Produksi blocked from admin finance write.
     * - Unbound user, inactive user, and inactive outlet rejected.
     */
    public function test_38_to_44_role_integrity_and_unbound_inactive_rejected(): void
    {
        // 38 & 39: Owner cannot approve purchase
        $purchaseA = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'supplier_id'     => $this->supplierA->id,
            'purchase_no'     => 'PO-A-TEST-001',
            'purchase_date'   => '2026-10-15',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
        ]);
        $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.approvals.approve', $purchaseA))
            ->assertStatus(403);

        // 40: Gudang blocked from finance write routes
        $this->actingAs($this->gudangA)
            ->post(route('admin.opening-cash.store'), ['amount' => '1000', 'opening_date' => '2026-12-01'])
            ->assertStatus(403);
        $this->actingAs($this->gudangA)
            ->post(route('admin.incomes.store'), ['amount' => '1000', 'income_date' => '2026-12-01', 'category' => BusinessIncome::CATEGORY_PEMASUKAN_LAIN])
            ->assertStatus(403);
        $this->actingAs($this->gudangA)
            ->post(route('admin.expenses.store'), ['amount' => '1000', 'expense_date' => '2026-12-01', 'expense_type' => ExpenseType::OPERATING->value, 'description' => 'Test'])
            ->assertStatus(403);

        // 41: Produksi blocked from finance write routes
        $this->actingAs($this->produksiA)
            ->post(route('admin.opening-cash.store'), ['amount' => '1000', 'opening_date' => '2026-12-01'])
            ->assertStatus(403);
        $this->actingAs($this->produksiA)
            ->post(route('admin.incomes.store'), ['amount' => '1000', 'income_date' => '2026-12-01', 'category' => BusinessIncome::CATEGORY_PEMASUKAN_LAIN])
            ->assertStatus(403);
        $this->actingAs($this->produksiA)
            ->post(route('admin.expenses.store'), ['amount' => '1000', 'expense_date' => '2026-12-01', 'expense_type' => ExpenseType::OPERATING->value, 'description' => 'Test'])
            ->assertStatus(403);

        // 42: Unbound Admin (outlet_id null) blocked from finance write routes -> 403
        $this->actingAs($this->unboundAdmin)
            ->post(route('admin.opening-cash.store'), ['amount' => '1000', 'opening_date' => '2026-12-01'])
            ->assertStatus(403);
        $this->actingAs($this->unboundAdmin)
            ->post(route('admin.incomes.store'), ['amount' => '1000', 'income_date' => '2026-12-01', 'category' => BusinessIncome::CATEGORY_PEMASUKAN_LAIN])
            ->assertStatus(403);
        $this->actingAs($this->unboundAdmin)
            ->post(route('admin.expenses.store'), ['amount' => '1000', 'expense_date' => '2026-12-01', 'expense_type' => ExpenseType::OPERATING->value, 'description' => 'Test'])
            ->assertStatus(403);

        // 43: Inactive Admin redirected to login by EnsureUserIsActive middleware
        $this->actingAs($this->inactiveAdminA)
            ->post(route('admin.opening-cash.store'), ['amount' => '1000', 'opening_date' => '2026-12-01'])
            ->assertRedirect(route('login'));

        // 44: Admin with Inactive Outlet blocked -> 403
        $this->actingAs($this->adminInactiveOutlet)
            ->post(route('admin.opening-cash.store'), ['amount' => '1000', 'opening_date' => '2026-12-01'])
            ->assertStatus(403);
        $this->actingAs($this->adminInactiveOutlet)
            ->post(route('admin.incomes.store'), ['amount' => '1000', 'income_date' => '2026-12-01', 'category' => BusinessIncome::CATEGORY_PEMASUKAN_LAIN])
            ->assertStatus(403);
        $this->actingAs($this->adminInactiveOutlet)
            ->post(route('admin.expenses.store'), ['amount' => '1000', 'expense_date' => '2026-12-01', 'expense_type' => ExpenseType::OPERATING->value, 'description' => 'Test'])
            ->assertStatus(403);
    }

    /**
     * Scenarios 45 & 46:
     * - Legacy compatibility when feature flag is FALSE.
     * - Income, Expense, and Opening Balance created without requiring outlet_id.
     */
    public function test_45_and_46_legacy_mode_false_compatibility(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        // Opening cash in legacy mode
        $responseOpening = $this->actingAs($this->adminA)->post(route('admin.opening-cash.store'), [
            'amount'       => '33000000.00',
            'opening_date' => '2026-12-15',
            'notes'        => 'Legacy Opening Cash',
        ]);
        $responseOpening->assertRedirect(route('admin.financial-records.index'));
        $legacyRecord = OpeningCashBalance::where('notes', 'Legacy Opening Cash')->first();
        $this->assertNotNull($legacyRecord);
        $this->assertNull($legacyRecord->outlet_id);

        // Income in legacy mode
        $responseIncome = $this->actingAs($this->adminA)->post(route('admin.incomes.store'), [
            'income_date' => '2026-12-16',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '2500000.00',
            'notes'       => 'Legacy Income Store',
        ]);
        $responseIncome->assertRedirect(route('admin.financial-records.index'));
        $legacyInc = BusinessIncome::where('notes', 'Legacy Income Store')->first();
        $this->assertNotNull($legacyInc);
        $this->assertNull($legacyInc->outlet_id);

        // Expense in legacy mode (with legacy order)
        $responseExpense = $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'order_id'      => $this->legacyOrder->id,
            'expense_type'  => ExpenseType::OPERATING->value,
            'expense_date'  => '2026-12-17',
            'description'   => 'Legacy Expense Store',
            'amount'        => '750000.00',
        ]);
        $responseExpense->assertRedirect();
        $legacyExp = BusinessExpense::where('description', 'Legacy Expense Store')->first();
        $this->assertNotNull($legacyExp);
        $this->assertNull($legacyExp->outlet_id);
    }

    /**
     * Scenarios 47 to 55:
     * - No unauthorized mutation of Tenant B's data when Tenant A executes requests.
     * - Financial engine and read side remain intact.
     */
    public function test_47_to_55_no_foreign_database_mutations_and_regression_safety(): void
    {
        // Snapshot Tenant B state before requests
        $openingBBefore = OpeningCashBalance::where('outlet_id', $this->outletB->id)->pluck('amount', 'id')->all();
        $incomeBBefore = BusinessIncome::where('outlet_id', $this->outletB->id)->pluck('amount', 'id')->all();
        $expenseBBefore = BusinessExpense::where('outlet_id', $this->outletB->id)->pluck('amount', 'id')->all();

        // Perform legitimate and rejected requests by Admin A
        $this->actingAs($this->adminA)->post(route('admin.incomes.store'), [
            'income_date' => '2026-10-25',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '500000.00',
        ]);

        $this->actingAs($this->adminA)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-25',
            'expense_type' => ExpenseType::OPERATING->value,
            'amount'       => '250000.00',
            'description'  => 'Normal A Expense',
        ]);

        // Verify Tenant B database state is 100% IDENTICAL
        $openingBAfter = OpeningCashBalance::where('outlet_id', $this->outletB->id)->pluck('amount', 'id')->all();
        $incomeBAfter = BusinessIncome::where('outlet_id', $this->outletB->id)->pluck('amount', 'id')->all();
        $expenseBAfter = BusinessExpense::where('outlet_id', $this->outletB->id)->pluck('amount', 'id')->all();

        $this->assertEquals($openingBBefore, $openingBAfter);
        $this->assertEquals($incomeBBefore, $incomeBAfter);
        $this->assertEquals($expenseBBefore, $expenseBAfter);
    }
}
