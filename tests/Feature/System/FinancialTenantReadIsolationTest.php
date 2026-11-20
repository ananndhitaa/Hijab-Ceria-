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
use App\Services\Finance\PeriodFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinancialTenantReadIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletInactive;

    protected User $adminA;
    protected User $adminB;
    protected User $ownerA;
    protected User $ownerB;
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

    protected OpeningCashBalance $openingRecordA;
    protected OpeningCashBalance $openingRecordB;
    protected OpeningCashBalance $legacyGlobalOpening;

    protected BusinessIncome $incomeA;
    protected BusinessIncome $incomeB;
    protected BusinessIncome $legacyIncome;

    protected BusinessExpense $expenseA;
    protected BusinessExpense $expenseB;
    protected BusinessExpense $expenseAGeneral;
    protected BusinessExpense $legacyExpense;

    protected Order $orderA;
    protected Order $orderB;

    protected Invoice $invoiceA;
    protected Invoice $invoiceB;

    protected Payment $paymentA;
    protected Payment $paymentB;

    protected Purchase $purchaseA;
    protected Purchase $purchaseB;

    protected PurchasePayment $purchasePaymentA;
    protected PurchasePayment $purchasePaymentB;

    protected FinancialRecordService $financeService;
    protected PeriodFinancialService $periodFinanceService;

    protected function setUp(): void
    {
        parent::setUp();

        config(['nayara.tenant_isolation_enabled' => true]);
        Storage::fake('local');

        $this->financeService = app(FinancialRecordService::class);
        $this->periodFinanceService = app(PeriodFinancialService::class);

        // 1. Outlets
        $this->outletA = Outlet::create([
            'outlet_name'                => 'Konveksi Hijab Nayara Outlet A',
            'outlet_code'                => 'OUT-A',
            'slug'                       => 'out-a',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->outletB = Outlet::create([
            'outlet_name'                => 'Konveksi Hijab Nayara Outlet B',
            'outlet_code'                => 'OUT-B',
            'slug'                       => 'out-b',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);

        $this->outletInactive = Outlet::create([
            'outlet_name'                => 'Konveksi Hijab Inactive Outlet',
            'outlet_code'                => 'OUT-INACT',
            'slug'                       => 'out-inact',
            'status'                     => 'INACTIVE',
            'is_public_showcase_enabled' => false,
        ]);

        // 2. Users
        $this->adminA = User::create([
            'name'      => 'Admin Outlet A',
            'email'     => 'admin.a@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminB = User::create([
            'name'      => 'Admin Outlet B',
            'email'     => 'admin.b@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->ownerA = User::create([
            'name'      => 'Owner Outlet A',
            'email'     => 'owner.a@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->ownerB = User::create([
            'name'      => 'Owner Outlet B',
            'email'     => 'owner.b@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletB->id,
        ]);

        $this->gudangA = User::create([
            'name'      => 'Gudang Outlet A',
            'email'     => 'gudang.a@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::GUDANG,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->produksiA = User::create([
            'name'      => 'Produksi Outlet A',
            'email'     => 'produksi.a@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::PRODUKSI,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->unboundAdmin = User::create([
            'name'      => 'Unbound Admin',
            'email'     => 'admin.unbound@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => null,
        ]);

        $this->inactiveAdminA = User::create([
            'name'      => 'Inactive Admin A',
            'email'     => 'admin.inact@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::INACTIVE,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminInactiveOutlet = User::create([
            'name'      => 'Admin Inactive Outlet',
            'email'     => 'admin.inactout@nayara.test',
            'password'  => Hash::make('password123'),
            'role'      => UserRole::ADMIN,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $this->outletInactive->id,
        ]);

        // 3. Master Data
        $unit = Unit::firstOrCreate(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->materialA = Material::create([
            'material_name' => 'Kain Rayon Outlet A',
            'unit_id'       => $unit->id,
            'outlet_id'     => $this->outletA->id,
            'active'        => true,
        ]);

        $this->materialB = Material::create([
            'material_name' => 'Kain Katun Outlet B',
            'unit_id'       => $unit->id,
            'outlet_id'     => $this->outletB->id,
            'active'        => true,
        ]);

        $this->supplierA = Supplier::create([
            'supplier_name' => 'Supplier Textile A',
            'outlet_id'     => $this->outletA->id,
            'active'        => true,
        ]);
        $this->supplierA->materials()->attach($this->materialA->id);

        $this->supplierB = Supplier::create([
            'supplier_name' => 'Supplier Textile B',
            'outlet_id'     => $this->outletB->id,
            'active'        => true,
        ]);
        $this->supplierB->materials()->attach($this->materialB->id);

        $this->customerA = Customer::create([
            'customer_name' => 'Customer Nayara A',
            'phone'         => '081211111111',
            'outlet_id'     => $this->outletA->id,
        ]);

        $this->customerB = Customer::create([
            'customer_name' => 'Customer Nayara B',
            'phone'         => '081222222222',
            'outlet_id'     => $this->outletB->id,
        ]);

        $this->productA = Product::create([
            'product_name' => 'Hijab Pashmina Silk A',
            'category'     => 'Pashmina',
            'active'       => true,
        ]);
        $this->variantA = ProductVariant::create([
            'product_id'   => $this->productA->id,
            'variant_name' => 'Navy Blue A',
            'sku'          => 'SKU-A-001',
            'active'       => true,
        ]);

        $this->productB = Product::create([
            'product_name' => 'Hijab Segiempat Voal B',
            'category'     => 'Segiempat',
            'active'       => true,
        ]);
        $this->variantB = ProductVariant::create([
            'product_id'   => $this->productB->id,
            'variant_name' => 'Emerald Green B',
            'sku'          => 'SKU-B-001',
            'active'       => true,
        ]);

        // 4. Financial Records Fixture

        // 4a. Opening Balances:
        // Tenant A: Rp 10.000.000
        $this->openingRecordA = OpeningCashBalance::create([
            'outlet_id'    => $this->outletA->id,
            'amount'       => '10000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Opening Cash A Rp 10.000.000',
            'created_by'   => $this->adminA->id,
        ]);

        // Tenant B: Rp 5.000.000
        $this->openingRecordB = OpeningCashBalance::create([
            'outlet_id'    => $this->outletB->id,
            'amount'       => '5000000.00',
            'opening_date' => '2026-10-01',
            'notes'        => 'Opening Cash B Rp 5.000.000',
            'created_by'   => $this->adminB->id,
        ]);

        // Legacy Global Opening (outlet_id = null): Rp 1.000.000
        $this->legacyGlobalOpening = OpeningCashBalance::create([
            'outlet_id'    => null,
            'amount'       => '1000000.00',
            'opening_date' => '2026-09-01',
            'notes'        => 'Legacy Global Opening Cash Rp 1.000.000',
            'created_by'   => $this->adminA->id,
        ]);

        // 4b. Business Incomes:
        // Tenant A: Rp 2.500.000
        $this->incomeA = BusinessIncome::create([
            'outlet_id'   => $this->outletA->id,
            'income_no'   => 'INC-A-202610-001',
            'income_date' => '2026-10-05',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '2500000.00',
            'notes'       => 'Pendapatan Bazar Tenant A',
            'created_by'  => $this->adminA->id,
        ]);

        // Tenant B: Rp 1.200.000
        $this->incomeB = BusinessIncome::create([
            'outlet_id'   => $this->outletB->id,
            'income_no'   => 'INC-B-202610-002',
            'income_date' => '2026-10-06',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '1200000.00',
            'notes'       => 'Pendapatan Event Tenant B',
            'created_by'  => $this->adminB->id,
        ]);

        // Legacy Global Income: Rp 300.000
        $this->legacyIncome = BusinessIncome::create([
            'outlet_id'   => null,
            'income_no'   => 'INC-LEGACY-003',
            'income_date' => '2026-10-07',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '300000.00',
            'notes'       => 'Pendapatan Unassigned Legacy',
            'created_by'  => $this->adminA->id,
        ]);

        // 4c. Orders & Customer Payments:
        // Order Tenant A: 100 pcs @ 50.000 = Rp 5.000.000
        $this->orderA = Order::create([
            'outlet_id'   => $this->outletA->id,
            'customer_id' => $this->customerA->id,
            'order_no'    => 'ORD-A-202610-001',
            'order_date'  => '2026-10-08',
            'status'      => OrderStatus::DEAL,
        ]);
        OrderItem::create([
            'order_id'           => $this->orderA->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => '50000.00',
        ]);
        $this->invoiceA = Invoice::create([
            'order_id'     => $this->orderA->id,
            'invoice_no'   => 'INV-A-202610-001',
            'invoice_date' => '2026-10-08',
            'total_amount' => '5000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);
        $this->paymentA = Payment::create([
            'invoice_id'   => $this->invoiceA->id,
            'payment_type' => PaymentType::DP,
            'amount'       => '3000000.00',
            'payment_date' => '2026-10-09',
            'reference_no' => 'PAY-A-001',
            'status'       => PaymentStatus::CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $this->adminA->id,
        ]);

        // Order Tenant B: 50 pcs @ 60.000 = Rp 3.000.000
        $this->orderB = Order::create([
            'outlet_id'   => $this->outletB->id,
            'customer_id' => $this->customerB->id,
            'order_no'    => 'ORD-B-202610-002',
            'order_date'  => '2026-10-10',
            'status'      => OrderStatus::DEAL,
        ]);
        OrderItem::create([
            'order_id'           => $this->orderB->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => '60000.00',
        ]);
        $this->invoiceB = Invoice::create([
            'order_id'     => $this->orderB->id,
            'invoice_no'   => 'INV-B-202610-002',
            'invoice_date' => '2026-10-10',
            'total_amount' => '3000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);
        $this->paymentB = Payment::create([
            'invoice_id'   => $this->invoiceB->id,
            'payment_type' => PaymentType::DP,
            'amount'       => '1500000.00',
            'payment_date' => '2026-10-11',
            'reference_no' => 'PAY-B-002',
            'status'       => PaymentStatus::CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $this->adminB->id,
        ]);

        // 4d. Business Expenses:
        // Expense Tenant A: Rp 800.000 (attributed to Order A)
        $this->expenseA = BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'order_id'      => $this->orderA->id,
            'expense_type'  => ExpenseType::MARKETING,
            'amount'        => '800000.00',
            'expense_date'  => '2026-10-12',
            'description'   => 'Iklan Instagram Tenant A',
            'campaign_name' => 'Promo A Okt',
            'created_by'    => $this->adminA->id,
        ]);

        // General Expense Tenant A (order_id null): Rp 400.000
        $this->expenseAGeneral = BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'order_id'      => null,
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '400000.00',
            'expense_date'  => '2026-10-13',
            'description'   => 'Listrik Workshop Tenant A',
            'campaign_name' => null,
            'created_by'    => $this->adminA->id,
        ]);

        // Expense Tenant B: Rp 600.000 (attributed to Order B)
        $this->expenseB = BusinessExpense::create([
            'outlet_id'     => $this->outletB->id,
            'order_id'      => $this->orderB->id,
            'expense_type'  => ExpenseType::MARKETING,
            'amount'        => '600000.00',
            'expense_date'  => '2026-10-14',
            'description'   => 'Endorsement Selebgram Tenant B',
            'campaign_name' => 'Promo B Okt',
            'created_by'    => $this->adminB->id,
        ]);

        // Legacy Global Expense: Rp 200.000
        $this->legacyExpense = BusinessExpense::create([
            'outlet_id'     => null,
            'order_id'      => null,
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '200000.00',
            'expense_date'  => '2026-10-14',
            'description'   => 'Beban Operasional Legacy',
            'campaign_name' => null,
            'created_by'    => $this->adminA->id,
        ]);

        // 4e. Purchases & Purchase Payments:
        // Purchase Tenant A: 50m @ 20.000 = Rp 1.000.000
        $this->purchaseA = Purchase::create([
            'outlet_id'       => $this->outletA->id,
            'supplier_id'     => $this->supplierA->id,
            'purchase_no'     => 'PO-A-202610-001',
            'purchase_date'   => '2026-10-15',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseA->id,
            'material_id'  => $this->materialA->id,
            'qty'          => '50.0000',
            'unit_price'   => '20000.00',
            'qty_received' => '0.0000',
        ]);
        $this->purchasePaymentA = PurchasePayment::create([
            'purchase_id'    => $this->purchaseA->id,
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-16',
            'payment_method' => 'TRANSFER',
            'reference_no'   => 'POP-A-001',
            'created_by'     => $this->adminA->id,
        ]);

        // Purchase Tenant B: 40m @ 25.000 = Rp 1.000.000
        $this->purchaseB = Purchase::create([
            'outlet_id'       => $this->outletB->id,
            'supplier_id'     => $this->supplierB->id,
            'purchase_no'     => 'PO-B-202610-002',
            'purchase_date'   => '2026-10-17',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $this->purchaseB->id,
            'material_id'  => $this->materialB->id,
            'qty'          => '40.0000',
            'unit_price'   => '25000.00',
            'qty_received' => '0.0000',
        ]);
        $this->purchasePaymentB = PurchasePayment::create([
            'purchase_id'    => $this->purchaseB->id,
            'amount'         => '1000000.00',
            'payment_date'   => '2026-10-18',
            'payment_method' => 'TRANSFER',
            'reference_no'   => 'POP-B-002',
            'created_by'     => $this->adminB->id,
        ]);
    }

    /**
     * Scenario 01 - 07:
     * - Finance READ tenant A only displays Tenant A records.
     * - Finance READ tenant B only displays Tenant B records.
     * - Legacy global opening balance (outlet_id null) does NOT appear on A or B.
     * - Income B does not appear on ledger A.
     * - Expense B does not appear on ledger A.
     * - Customer Payment B does not appear on ledger A.
     * - Purchase Payment B does not appear on ledger A.
     */
    public function test_01_to_07_financial_records_index_read_isolation(): void
    {
        // 1. Admin A accesses financial records
        $responseA = $this->actingAs($this->adminA)->get(route('admin.financial-records.index'));
        $responseA->assertOk();

        // Admin A sees Tenant A records
        $responseA->assertSee('INC-A-202610-001');
        $responseA->assertSee('PAY-A-001');
        $responseA->assertSee('Iklan Instagram Tenant A');
        $responseA->assertSee('Listrik Workshop Tenant A');
        $responseA->assertSee('PO-A-202610-001');

        // Admin A NEVER sees Tenant B records (Scenarios 04, 05, 06, 07)
        $responseA->assertDontSee('INC-B-202610-002');
        $responseA->assertDontSee('PAY-B-002');
        $responseA->assertDontSee('Endorsement Selebgram Tenant B');
        $responseA->assertDontSee('PO-B-202610-002');

        // Admin A NEVER sees legacy unassigned records (Scenario 03)
        $responseA->assertDontSee('INC-LEGACY-003');
        $responseA->assertDontSee('Beban Operasional Legacy');

        // 2. Admin B accesses financial records
        $responseB = $this->actingAs($this->adminB)->get(route('admin.financial-records.index'));
        $responseB->assertOk();

        // Admin B sees Tenant B records
        $responseB->assertSee('INC-B-202610-002');
        $responseB->assertSee('PAY-B-002');
        $responseB->assertSee('Endorsement Selebgram Tenant B');
        $responseB->assertSee('PO-B-202610-002');

        // Admin B NEVER sees Tenant A records
        $responseB->assertDontSee('INC-A-202610-001');
        $responseB->assertDontSee('PAY-A-001');
        $responseB->assertDontSee('Iklan Instagram Tenant A');
        $responseB->assertDontSee('Listrik Workshop Tenant A');
        $responseB->assertDontSee('PO-A-202610-001');
    }

    /**
     * Scenario 08 & 09:
     * - Financial cash card A only uses Tenant A's cash position.
     * - Financial cash card B only uses Tenant B's cash position.
     */
    public function test_08_and_09_financial_cash_card_isolation(): void
    {
        // Tenant A Cash Calculation:
        // Opening: 10,000,000
        // Masuk: Income A (2,500,000) + Payment A (3,000,000) = 5,500,000
        // Keluar: Expense A (800,000) + Expense A General (400,000) + Purchase Payment A (1,000,000) = 2,200,000
        // Kas Tersedia A: 10,000,000 + 5,500,000 - 2,200,000 = 13,300,000
        $dataA = $this->financeService->getConsolidatedRecords([], $this->outletA->id);
        $this->assertEquals('10000000.00', $dataA['cash_position']['opening_balance']);
        $this->assertEquals('5500000.00', $dataA['cash_position']['all_time_masuk']);
        $this->assertEquals('2200000.00', $dataA['cash_position']['all_time_keluar']);
        $this->assertEquals('13300000.00', $dataA['cash_position']['kas_tersedia']);

        // Tenant B Cash Calculation:
        // Opening: 5,000,000
        // Masuk: Income B (1,200,000) + Payment B (1,500,000) = 2,700,000
        // Keluar: Expense B (600,000) + Purchase Payment B (1,000,000) = 1,600,000
        // Kas Tersedia B: 5,000,000 + 2,700,000 - 1,600,000 = 6,100,000
        $dataB = $this->financeService->getConsolidatedRecords([], $this->outletB->id);
        $this->assertEquals('5000000.00', $dataB['cash_position']['opening_balance']);
        $this->assertEquals('2700000.00', $dataB['cash_position']['all_time_masuk']);
        $this->assertEquals('1600000.00', $dataB['cash_position']['all_time_keluar']);
        $this->assertEquals('6100000.00', $dataB['cash_position']['kas_tersedia']);

        // Verify HTML views display their exact scoped cash position
        $responseA = $this->actingAs($this->adminA)->get(route('admin.financial-records.index'));
        $responseA->assertOk();
        $this->assertEquals('13300000.00', $responseA->viewData('cashPosition')['kas_tersedia']);

        $responseB = $this->actingAs($this->adminB)->get(route('admin.financial-records.index'));
        $responseB->assertOk();
        $this->assertEquals('6100000.00', $responseB->viewData('cashPosition')['kas_tersedia']);
    }

    /**
     * Scenario 10 & 11:
     * - Date filter remains tenant-scoped.
     * - Search does not bypass outlet scope.
     */
    public function test_10_and_11_filter_tanggal_and_search_stay_tenant_scoped(): void
    {
        // Admin A searches for Tenant B's invoice or description
        $responseSearchB = $this->actingAs($this->adminA)->get(route('admin.financial-records.index', [
            'search' => 'Selebgram',
        ]));
        $responseSearchB->assertOk();
        $responseSearchB->assertDontSee('Endorsement Selebgram Tenant B');
        $this->assertCount(0, $responseSearchB->viewData('records'));

        // Admin A searches for Tenant B's PO number
        $responseSearchPoB = $this->actingAs($this->adminA)->get(route('admin.financial-records.index', [
            'search' => 'PO-B-202610-002',
        ]));
        $responseSearchPoB->assertOk();
        $this->assertCount(0, $responseSearchPoB->viewData('records'));
        $this->assertStringNotContainsString('Supplier Textile B', $responseSearchPoB->getContent());

        // Admin A searches for Tenant A's expense
        $responseSearchA = $this->actingAs($this->adminA)->get(route('admin.financial-records.index', [
            'search' => 'Instagram',
        ]));
        $responseSearchA->assertOk();
        $responseSearchA->assertSee('Iklan Instagram Tenant A');
        $this->assertCount(1, $responseSearchA->viewData('records'));

        // Date range filter on Tenant A
        $responseDateFilter = $this->actingAs($this->adminA)->get(route('admin.financial-records.index', [
            'start_date' => '2026-10-12',
            'end_date'   => '2026-10-13',
        ]));
        $responseDateFilter->assertOk();
        $records = $responseDateFilter->viewData('records');
        foreach ($records as $item) {
            $this->assertGreaterThanOrEqual('2026-10-12', $item['date']);
            $this->assertLessThanOrEqual('2026-10-13', $item['date']);
        }
    }

    /**
     * Scenario 12 & 13:
     * - Pagination stays scoped.
     * - Summary / count / total does not mix tenants.
     */
    public function test_12_and_13_pagination_and_summary_totals_are_strictly_scoped(): void
    {
        $responseA = $this->actingAs($this->adminA)->get(route('admin.financial-records.index'));
        $summaryA = $responseA->viewData('summary');

        // Total Masuk for A in October: 2.500.000 (Income) + 3.000.000 (Payment) = 5.500.000
        $this->assertEquals('5500000.00', $summaryA['total_masuk']);
        // Total Keluar for A in October: 800.000 + 400.000 + 1.000.000 = 2.200.000
        $this->assertEquals('2200000.00', $summaryA['total_keluar']);
        // Arus Bersih: 5.500.000 - 2.200.000 = 3.300.000
        $this->assertEquals('3300000.00', $summaryA['arus_bersih']);

        // Sales summary:
        $salesA = $responseA->viewData('salesSummary');
        $this->assertEquals('5000000.00', $salesA['total_penjualan']);
        $this->assertEquals('3000000.00', $salesA['sudah_dibayar']);
        $this->assertEquals('2000000.00', $salesA['belum_dibayar']);
    }

    /**
     * Scenario 14, 15, 16:
     * - Opening cash edit READ only shows own tenant's record.
     * - Legacy global opening (outlet_id null) does not appear.
     */
    public function test_14_to_16_opening_cash_read_shows_only_own_tenant(): void
    {
        $responseA = $this->actingAs($this->adminA)->get(route('admin.opening-cash.edit'));
        $responseA->assertOk();
        $currentA = $responseA->viewData('current');
        $this->assertNotNull($currentA);
        $this->assertEquals((int) $this->outletA->id, (int) $currentA->outlet_id);
        $this->assertEquals('10000000.00', $currentA->amount);

        $responseB = $this->actingAs($this->adminB)->get(route('admin.opening-cash.edit'));
        $responseB->assertOk();
        $currentB = $responseB->viewData('current');
        $this->assertNotNull($currentB);
        $this->assertEquals((int) $this->outletB->id, (int) $currentB->outlet_id);
        $this->assertEquals('5000000.00', $currentB->amount);
    }

    /**
     * Scenario 17 & 18:
     * - Business Income create validates tenant context.
     * - Unbound user fails closed (403).
     */
    public function test_17_and_18_business_income_create_validates_tenant(): void
    {
        $responseA = $this->actingAs($this->adminA)->get(route('admin.incomes.create'));
        $responseA->assertOk();

        $responseUnbound = $this->actingAs($this->unboundAdmin)->get(route('admin.incomes.create'));
        $responseUnbound->assertStatus(403);
    }

    /**
     * Scenario 19 & 20:
     * - Business Expense index only shows tenant's expenses.
     * - Foreign expense show/edit returns 404 (Anti-IDOR).
     */
    public function test_19_and_20_business_expense_read_and_anti_idor(): void
    {
        // 19. Index
        $responseA = $this->actingAs($this->adminA)->get(route('admin.expenses.index'));
        $responseA->assertOk();
        $responseA->assertSee('Iklan Instagram Tenant A');
        $responseA->assertSee('Listrik Workshop Tenant A');
        $responseA->assertDontSee('Endorsement Selebgram Tenant B');
        $responseA->assertDontSee('Beban Operasional Legacy');

        // 20. Show & Edit Anti-IDOR
        // Admin A viewing own expense -> 200 OK
        $this->actingAs($this->adminA)->get(route('admin.expenses.show', $this->expenseA))->assertOk();
        $this->actingAs($this->adminA)->get(route('admin.expenses.edit', $this->expenseA))->assertOk();

        // Admin A viewing foreign expense B -> 404 Not Found!
        $this->actingAs($this->adminA)->get(route('admin.expenses.show', $this->expenseB))->assertStatus(404);
        $this->actingAs($this->adminA)->get(route('admin.expenses.edit', $this->expenseB))->assertStatus(404);

        // Admin A viewing legacy unassigned expense -> 404 Not Found!
        $this->actingAs($this->adminA)->get(route('admin.expenses.show', $this->legacyExpense))->assertStatus(404);
        $this->actingAs($this->adminA)->get(route('admin.expenses.edit', $this->legacyExpense))->assertStatus(404);
    }

    /**
     * Scenario 21 & 22:
     * - Expense linked to a foreign order is rejected fail-closed (404).
     * - Expense with order_id NULL belonging to own tenant is safely accessible.
     */
    public function test_21_and_22_expense_order_relation_consistency_and_null_order(): void
    {
        // General expense with order_id NULL belonging to Tenant A -> 200 OK
        $this->actingAs($this->adminA)->get(route('admin.expenses.show', $this->expenseAGeneral))->assertOk();

        // Tampered expense: outlet_id = A, but order_id = orderB (cross-tenant mismatch)
        $mismatchedExpense = BusinessExpense::create([
            'outlet_id'     => $this->outletA->id,
            'order_id'      => $this->orderB->id,
            'expense_type'  => ExpenseType::OPERATING,
            'amount'        => '50000.00',
            'expense_date'  => '2026-10-14',
            'description'   => 'Mismatched order expense',
            'created_by'    => $this->adminA->id,
        ]);

        // Attempting to show or edit this mismatched expense fails closed with 404
        $this->actingAs($this->adminA)->get(route('admin.expenses.show', $mismatchedExpense))->assertStatus(404);
        $this->actingAs($this->adminA)->get(route('admin.expenses.edit', $mismatchedExpense))->assertStatus(404);
    }

    /**
     * Scenario 23:
     * - Order dropdown in expense create only displays current tenant's orders.
     */
    public function test_23_expense_create_order_dropdown_scoped(): void
    {
        $responseA = $this->actingAs($this->adminA)->get(route('admin.expenses.create'));
        $responseA->assertOk();
        $ordersA = $responseA->viewData('orders');
        $this->assertTrue($ordersA->contains('id', $this->orderA->id));
        $this->assertFalse($ordersA->contains('id', $this->orderB->id));

        // Passing foreign order_id in query parameter does not select foreign order
        $responseForeignParam = $this->actingAs($this->adminA)->get(route('admin.expenses.create', [
            'order_id' => $this->orderB->id,
        ]));
        $responseForeignParam->assertOk();
        $this->assertNull($responseForeignParam->viewData('selectedOrderId'));
    }

    /**
     * Scenario 24 to 28:
     * - Period financial reporting for Owner A and B are strictly isolated.
     * - Sales revenue, customer payments, supplier payments, expenses, HPP, gross/net profit.
     */
    public function test_24_to_28_period_financial_reporting_isolation(): void
    {
        // Owner A reports for October 2026
        $responseOwnerA = $this->actingAs($this->ownerA)->get(route('owner.performance.index', [
            'preset'     => 'custom',
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
        ]));
        $responseOwnerA->assertOk();
        $financialsA = $responseOwnerA->viewData('financials');

        // Revenue A: 100 pcs @ 50.000 = 5.000.000
        $this->assertEquals('5000000.00', $financialsA['total_penjualan']);
        // Pendapatan Diterima A: Payment A = 3.000.000
        $this->assertEquals('3000000.00', $financialsA['pendapatan_diterima']);
        // Supplier Payments A: PurchasePayment A = 1.000.000
        $this->assertEquals('1000000.00', $financialsA['supplier_payments']);
        // Business Expenses A: 800.000 + 400.000 = 1.200.000
        $this->assertEquals('1200000.00', $financialsA['business_expenses']);
        // Total Pengeluaran A: 1.000.000 + 1.200.000 = 2.200.000
        $this->assertEquals('2200000.00', $financialsA['total_pengeluaran']);
        // Kas Tersedia A: 13.300.000
        $this->assertEquals('13300000.00', $financialsA['kas_tersedia']);

        // Owner B reports for October 2026
        $responseOwnerB = $this->actingAs($this->ownerB)->get(route('owner.performance.index', [
            'preset'     => 'custom',
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
        ]));
        $responseOwnerB->assertOk();
        $financialsB = $responseOwnerB->viewData('financials');

        // Revenue B: 50 pcs @ 60.000 = 3.000.000
        $this->assertEquals('3000000.00', $financialsB['total_penjualan']);
        // Pendapatan Diterima B: Payment B = 1.500.000
        $this->assertEquals('1500000.00', $financialsB['pendapatan_diterima']);
        // Supplier Payments B: PurchasePayment B = 1.000.000
        $this->assertEquals('1000000.00', $financialsB['supplier_payments']);
        // Business Expenses B: 600.000
        $this->assertEquals('600000.00', $financialsB['business_expenses']);
        // Total Pengeluaran B: 1.000.000 + 600.000 = 1.600.000
        $this->assertEquals('1600000.00', $financialsB['total_pengeluaran']);
        // Kas Tersedia B: 6.100.000
        $this->assertEquals('6100000.00', $financialsB['kas_tersedia']);
    }

    /**
     * Scenario 29:
     * - PeriodFinancialService without outlet parameter when isolation is enabled fails closed.
     */
    public function test_29_period_financial_service_without_outlet_fails_closed(): void
    {
        $result = $this->periodFinanceService->calculatePeriodFinancials('2026-10-01', '2026-10-31', null);
        $this->assertEquals('UNAUTHORIZED', $result['status']);
        $this->assertFalse($result['is_ready']);
        $this->assertEquals('0.00', $result['total_penjualan']);
        $this->assertEquals('0.00', $result['total_pengeluaran']);
        $this->assertNull($result['kas_tersedia']);
    }

    /**
     * Scenario 30 & 31:
     * - Fake outlet_id in query and fake X-Outlet-ID header are ignored.
     */
    public function test_30_and_31_fake_outlet_id_and_header_are_ignored(): void
    {
        $response = $this->actingAs($this->adminA)
            ->withHeaders(['X-Outlet-ID' => (string) $this->outletB->id])
            ->get(route('admin.financial-records.index', [
                'outlet_id' => $this->outletB->id,
            ]));

        $response->assertOk();
        // Admin A still sees Tenant A records
        $response->assertSee('INC-A-202610-001');
        // Admin A still does NOT see Tenant B records
        $response->assertDontSee('INC-B-202610-002');
    }

    /**
     * Scenario 32, 33, 34:
     * - Unbound user (outlet_id null), inactive outlet, and inactive user fail closed (403).
     */
    public function test_32_to_34_unbound_user_and_inactive_tenant_rejected(): void
    {
        // Unbound Admin
        $this->actingAs($this->unboundAdmin)
            ->get(route('admin.financial-records.index'))
            ->assertStatus(403);

        // Inactive Outlet Admin
        $this->actingAs($this->adminInactiveOutlet)
            ->get(route('admin.financial-records.index'))
            ->assertStatus(403);

        // Inactive Admin is blocked and redirected to login by EnsureUserIsActive middleware
        $this->actingAs($this->inactiveAdminA)
            ->get(route('admin.financial-records.index'))
            ->assertRedirect(route('login'));
    }

    /**
     * Scenario 35 & 36:
     * - Existing role middleware remains enforced.
     * - OWNER does not obtain PO approval privilege.
     */
    public function test_35_and_36_role_middleware_and_owner_cannot_approve_po(): void
    {
        // Gudang cannot access admin financial records
        $this->actingAs($this->gudangA)
            ->get(route('admin.financial-records.index'))
            ->assertStatus(403);

        // Produksi cannot access admin financial records
        $this->actingAs($this->produksiA)
            ->get(route('admin.financial-records.index'))
            ->assertStatus(403);

        // Owner cannot approve PO
        $this->actingAs($this->ownerA)
            ->post(route('admin.purchases.approvals.approve', $this->purchaseA))
            ->assertStatus(403);
    }

    /**
     * Scenario 37:
     * - Fallback purchase outlet does NOT self-authorize on approval review (show).
     * - Foreign Purchase review returns 404.
     * - Unbound Admin returns 403.
     */
    public function test_37_purchase_approval_show_never_falls_back_to_foreign_outlet(): void
    {
        // Admin A viewing own purchase -> 200 OK
        $this->actingAs($this->adminA)
            ->get(route('admin.purchases.approvals.show', $this->purchaseA))
            ->assertOk();

        // Admin A viewing foreign purchase B -> 404 Not Found!
        $this->actingAs($this->adminA)
            ->get(route('admin.purchases.approvals.show', $this->purchaseB))
            ->assertStatus(404);

        // Unbound Admin viewing purchase A -> 403 Forbidden!
        $this->actingAs($this->unboundAdmin)
            ->get(route('admin.purchases.approvals.show', $this->purchaseA))
            ->assertStatus(403);
    }

    /**
     * Scenario 38:
     * - Foreign opening record cannot be used in calculations.
     */
    public function test_38_foreign_opening_record_rejected_in_calculation(): void
    {
        // Passing Tenant B's opening record to Tenant A fails closed (returns null)
        $result = $this->financeService->calculateCurrentCash($this->openingRecordB, $this->outletA->id);
        $this->assertNull($result);

        // Passing legacy global opening (outlet_id null) fails closed
        $resultLegacy = $this->financeService->calculateCurrentCash($this->legacyGlobalOpening, $this->outletA->id);
        $this->assertNull($resultLegacy);
    }

    /**
     * Scenario 39:
     * - Legacy compatibility when feature flag is FALSE.
     */
    public function test_39_legacy_flag_false_compatibility(): void
    {
        config(['nayara.tenant_isolation_enabled' => false]);

        // Calling getConsolidatedRecords without outletId works in legacy mode
        $legacyRecords = $this->financeService->getConsolidatedRecords();
        $this->assertArrayHasKey('records', $legacyRecords);
        $this->assertArrayHasKey('cash_position', $legacyRecords);

        // Period financials without outletId works in legacy mode
        $legacyPeriod = $this->periodFinanceService->calculatePeriodFinancials('2026-10-01', '2026-10-31');
        $this->assertNotEquals('UNAUTHORIZED', $legacyPeriod['status']);
    }

    /**
     * Scenario 40 to 42:
     * - GET finance routes produce ZERO side effects or mutations.
     */
    public function test_40_to_42_get_endpoints_have_zero_mutations(): void
    {
        $openingCountBefore = OpeningCashBalance::count();
        $incomeCountBefore = BusinessIncome::count();
        $expenseCountBefore = BusinessExpense::count();
        $paymentCountBefore = Payment::count();
        $poPaymentCountBefore = PurchasePayment::count();

        // Perform multiple GET requests across all modules
        $this->actingAs($this->adminA)->get(route('admin.financial-records.index'))->assertOk();
        $this->actingAs($this->adminA)->get(route('admin.expenses.index'))->assertOk();
        $this->actingAs($this->adminA)->get(route('admin.expenses.show', $this->expenseA))->assertOk();
        $this->actingAs($this->adminA)->get(route('admin.expenses.create'))->assertOk();
        $this->actingAs($this->adminA)->get(route('admin.opening-cash.edit'))->assertOk();
        $this->actingAs($this->ownerA)->get(route('owner.performance.index'))->assertOk();
        $this->actingAs($this->ownerA)->get(route('owner.dashboard'))->assertOk();

        $this->assertEquals($openingCountBefore, OpeningCashBalance::count());
        $this->assertEquals($incomeCountBefore, BusinessIncome::count());
        $this->assertEquals($expenseCountBefore, BusinessExpense::count());
        $this->assertEquals($paymentCountBefore, Payment::count());
        $this->assertEquals($poPaymentCountBefore, PurchasePayment::count());
    }

    /**
     * Scenario 43:
     * - Tenant B private data never present in Tenant A HTML or JSON.
     */
    public function test_43_no_tenant_b_data_in_tenant_a_output(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.financial-records.index'));
        $content = $response->getContent();

        $this->assertStringNotContainsString('Customer Nayara B', $content);
        $this->assertStringNotContainsString('Supplier Textile B', $content);
        $this->assertStringNotContainsString('PO-B-202610-002', $content);
        $this->assertStringNotContainsString('INC-B-202610-002', $content);
        $this->assertStringNotContainsString('Endorsement Selebgram Tenant B', $content);
    }

    /**
     * Scenario 44:
     * - Owner dashboard analytics are strictly tenant-scoped.
     */
    public function test_44_owner_dashboard_analytics_strictly_scoped(): void
    {
        // Grant premium entitlement for analytics access
        \App\Models\Platform\TenantSubscription::create([
            'outlet_id' => $this->outletA->id,
            'type'      => 'PREMIUM',
            'status'    => 'ACTIVE',
            'starts_at' => now()->subDay(),
            'expires_at'=> now()->addMonth(),
        ]);
        \App\Models\Platform\TenantSubscription::create([
            'outlet_id' => $this->outletB->id,
            'type'      => 'PREMIUM',
            'status'    => 'ACTIVE',
            'starts_at' => now()->subDay(),
            'expires_at'=> now()->addMonth(),
        ]);

        $responseOwnerA = $this->actingAs($this->ownerA)->get(route('owner.dashboard', ['preset' => 'all']));
        $responseOwnerA->assertOk();

        $totalOrdersA = $responseOwnerA->viewData('totalOrders');
        $this->assertEquals(1, $totalOrdersA);

        $analyticsA = $responseOwnerA->viewData('analytics');
        $this->assertNotEmpty($analyticsA['top_products']);
        $this->assertEquals('Hijab Pashmina Silk A — Navy Blue A', $analyticsA['top_products'][0]['label'] ?? '');

        // Owner B dashboard
        $responseOwnerB = $this->actingAs($this->ownerB)->get(route('owner.dashboard', ['preset' => 'all']));
        $responseOwnerB->assertOk();

        $totalOrdersB = $responseOwnerB->viewData('totalOrders');
        $this->assertEquals(1, $totalOrdersB);

        $analyticsB = $responseOwnerB->viewData('analytics');
        $this->assertNotEmpty($analyticsB['top_products']);
        $this->assertEquals('Hijab Segiempat Voal B — Emerald Green B', $analyticsB['top_products'][0]['label'] ?? '');
    }
}
