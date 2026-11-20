<?php

namespace Tests\Feature\Finance;

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
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use App\Services\Finance\PeriodFinancialService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminFinancialRecordConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $owner;
    private User $gudang;
    private User $produksi;
    private User $customerUser;
    private Customer $customer;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Keuangan',
        ]);

        $this->owner = User::factory()->create([
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Owner Nayara',
        ]);

        $this->gudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staff Gudang',
        ]);

        $this->produksi = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staff Produksi',
        ]);

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Customer Nayara',
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Toko Cinay',
            'phone'         => '081234567890',
            'address'       => 'Bandung',
        ]);

        $this->supplier = Supplier::create([
            'supplier_name' => 'Sumber Kain',
            'contact_person'=> 'Budi Supplier',
            'phone'         => '081298765432',
            'address'       => 'Solo',
        ]);
    }

    // ==================================================
    // AQ. TESTS — SIDEBAR
    // ==================================================

    public function test_1_admin_sidebar_does_not_show_pembayaran_supplier(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $response->assertStatus(200);

        // Sidebar navigation should not have Pembayaran Supplier link
        $response->assertDontSee('href="' . route('admin.purchases.payments.index') . '"', false);
    }

    public function test_2_admin_sidebar_shows_laporan_penjualan(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $response->assertStatus(200);

        // Sidebar navigation MUST have Laporan Penjualan link
        $response->assertSee('href="' . route('admin.reports.sales') . '"', false);
        $response->assertSee('Laporan Penjualan');
    }

    public function test_3_admin_sidebar_shows_financial_records_and_invoices_but_not_biaya(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $response->assertStatus(200);

        // Final Keuangan Admin sidebar links
        $response->assertSee(route('admin.financial-records.index'));
        $response->assertSee(route('admin.invoices.index'));
        $response->assertSee(route('admin.reports.sales'));
        $response->assertSee('Catatan Keuangan');
        $response->assertSee('Invoice & Tagihan', false);
        $response->assertSee('Laporan Penjualan', false);

        // Biaya & Pengeluaran removed from sidebar navigation
        $response->assertDontSee('href="' . route('admin.expenses.index') . '"', false);
        $response->assertDontSee('Biaya & Pengeluaran');
    }

    public function test_3b_laporan_penjualan_sidebar_active_state_when_visiting_sales_report(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.sales'));
        $response->assertStatus(200);

        // On /admin/reports/sales, Laporan Penjualan is active
        $response->assertSee(route('admin.reports.sales'));
        $content = $response->getContent();

        // Laporan Penjualan link contains the active styling class
        $this->assertMatchesRegularExpression(
            '/href="' . preg_quote(route('admin.reports.sales'), '/') . '"\s+class="[^"]*bg-white\/15 text-white font-semibold/',
            $content
        );

        // Other financial links are NOT active
        $this->assertDoesNotMatchRegularExpression(
            '/href="' . preg_quote(route('admin.financial-records.index'), '/') . '"\s+class="[^"]*bg-white\/15 text-white font-semibold/',
            $content
        );
        $this->assertDoesNotMatchRegularExpression(
            '/href="' . preg_quote(route('admin.invoices.index'), '/') . '"\s+class="[^"]*bg-white\/15 text-white font-semibold/',
            $content
        );
    }

    // ==================================================
    // AR. TESTS — MANUAL INCOME
    // ==================================================

    public function test_4_admin_can_create_business_income(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.incomes.store'), [
            'income_date' => '2026-10-15',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '1500000.00',
            'notes'       => 'Pemasukan penjualan limbah kain',
        ]);

        $response->assertRedirect(route('admin.financial-records.index'));
        $this->assertDatabaseHas('business_incomes', [
            'category' => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'   => '1500000.00',
            'notes'    => 'Pemasukan penjualan limbah kain',
        ]);
    }

    public function test_5_non_admin_cannot_create_business_income(): void
    {
        $payload = [
            'income_date' => '2026-10-15',
            'category'    => BusinessIncome::CATEGORY_MODAL_TAMBAHAN,
            'amount'      => '5000000.00',
        ];

        $this->actingAs($this->owner)->get(route('admin.incomes.create'))->assertStatus(403);
        $this->actingAs($this->owner)->post(route('admin.incomes.store'), $payload)->assertStatus(403);

        $this->actingAs($this->gudang)->get(route('admin.incomes.create'))->assertStatus(403);
        $this->actingAs($this->gudang)->post(route('admin.incomes.store'), $payload)->assertStatus(403);

        $this->actingAs($this->produksi)->get(route('admin.incomes.create'))->assertStatus(403);
        $this->actingAs($this->produksi)->post(route('admin.incomes.store'), $payload)->assertStatus(403);

        $this->actingAs($this->customerUser)->get(route('admin.incomes.create'))->assertStatus(403);
        $this->actingAs($this->customerUser)->post(route('admin.incomes.store'), $payload)->assertStatus(403);
    }

    public function test_6_business_income_appears_as_masuk(): void
    {
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0001',
            'income_date' => '2026-10-15',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '750000.00',
            'notes'       => 'Pendapatan jasa potong',
            'created_by'  => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords();

        $matching = $data['records']->first(fn($r) => $r['reference_code'] === 'INC-202610-0001');
        $this->assertNotNull($matching);
        $this->assertEquals('MASUK', $matching['type']);
        $this->assertEquals(BusinessIncome::CATEGORY_PEMASUKAN_LAIN, $matching['category']);
        $this->assertEquals('750000.00', $matching['amount_in']);
    }

    public function test_7_business_income_affects_uang_masuk(): void
    {
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0002',
            'income_date' => '2026-10-15',
            'category'    => BusinessIncome::CATEGORY_MODAL_TAMBAHAN,
            'amount'      => '2500000.00',
            'created_by'  => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords(['start_date' => '2026-10-01', 'end_date' => '2026-10-31']);

        $this->assertEquals('2500000.00', $data['summary']['total_masuk']);
    }

    public function test_8_and_9_business_income_post_cutoff_affects_kas_saat_ini_and_pre_cutoff_does_not(): void
    {
        OpeningCashBalance::create([
            'opening_date' => '2026-10-10',
            'amount'       => '10000000.00',
            'created_by'   => $this->admin->id,
        ]);

        // Pre-cutoff: 2026-10-05 (Historis, should not alter current cash)
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0003',
            'income_date' => '2026-10-05',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '1000000.00',
            'created_by'  => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $currentCashPre = $service->calculateCurrentCash();
        $this->assertEquals('10000000.00', $currentCashPre);

        // Post-cutoff: 2026-10-12 (Should add to current cash)
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0004',
            'income_date' => '2026-10-12',
            'category'    => BusinessIncome::CATEGORY_MODAL_TAMBAHAN,
            'amount'      => '3000000.00',
            'created_by'  => $this->admin->id,
        ]);

        $currentCashPost = $service->calculateCurrentCash();
        $this->assertEquals('13000000.00', $currentCashPost);
    }

    public function test_10_business_income_does_not_alter_total_penjualan(): void
    {
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0005',
            'income_date' => '2026-10-15',
            'category'    => BusinessIncome::CATEGORY_NON_PENJUALAN,
            'amount'      => '5000000.00',
            'created_by'  => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords();

        $this->assertEquals('0.00', $data['sales_summary']['total_penjualan']);
    }

    public function test_11_and_12_business_income_does_not_alter_invoice_nor_create_customer_payment(): void
    {
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0006',
            'income_date' => '2026-10-15',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '1000000.00',
            'created_by'  => $this->admin->id,
        ]);

        $this->assertEquals(0, Invoice::count());
        $this->assertEquals(0, Payment::count());
    }

    public function test_13_business_income_does_not_alter_supervisor_roi(): void
    {
        $product = Product::create([
            'product_name' => 'Pashmina Silk',
            'product_code' => 'PRD-PS-001',
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'VAR-PS-DP-01',
            'base_selling_price'=> 100000,
        ]);

        // Setup Order & Confirmed Customer Payment: 5,000,000
        $order = Order::create([
            'order_no'     => 'SO-202610-0001',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-05',
            'total_amount' => '5000000.00',
            'status'       => OrderStatus::DEAL,
            'created_by'   => $this->admin->id,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => '100000.00',
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0001',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-05',
            'due_date'     => '2026-10-20',
            'total_amount' => '5000000.00',
            'paid_amount'  => '5000000.00',
            'status'       => InvoiceStatus::PAID,
            'created_by'   => $this->admin->id,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-05',
            'amount'         => '5000000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-05 10:00:00'),
        ]);

        // Business Expense: 2,500,000 -> ROI = (5,000,000 / 2,500,000) * 100 = 200.00%
        BusinessExpense::create([
            'expense_date' => '2026-10-06',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '2500000.00',
            'description'  => 'Operasional Workshop',
            'created_by'   => $this->admin->id,
        ]);

        $periodService = app(PeriodFinancialService::class);
        $resBefore = $periodService->calculatePeriodFinancials('2026-10-01', '2026-10-31');
        $this->assertEquals('200,00%', $resBefore['roi_label']);

        // Add BusinessIncome: 10,000,000 (Must NOT enter ROI numerator)
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0007',
            'income_date' => '2026-10-10',
            'category'    => BusinessIncome::CATEGORY_MODAL_TAMBAHAN,
            'amount'      => '10000000.00',
            'created_by'  => $this->admin->id,
        ]);

        $resAfter = $periodService->calculatePeriodFinancials('2026-10-01', '2026-10-31');
        // ROI must remain exactly 200,00%
        $this->assertEquals('200,00%', $resAfter['roi_label']);
    }

    // ==================================================
    // AS. TESTS — EXPENSE & SUPPLIER PAYMENT DISTINCT
    // ==================================================

    public function test_14_and_15_business_expense_form_does_not_contain_biaya_supplier_and_expenses_appear_as_keluar(): void
    {
        // 14. Expense create form does NOT contain Biaya Supplier
        $createPage = $this->actingAs($this->admin)->get(route('admin.expenses.create'));
        $createPage->assertStatus(200);
        $createPage->assertDontSee('Biaya Supplier');

        // 15. Standard business expense appears as KELUAR with its category
        $expense = BusinessExpense::create([
            'expense_date' => '2026-10-10',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '350000.00',
            'description'  => 'Biaya perlengkapan kantor',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords();

        $matching = $data['records']->first(fn($r) => $r['source_type'] === 'EXPENSE' && $r['source_id'] === $expense->id);
        $this->assertNotNull($matching);
        $this->assertEquals('KELUAR', $matching['type']);
        $this->assertEquals('Beban Operasional', $matching['category']);
        $this->assertEquals('350000.00', $matching['amount_out']);
    }

    public function test_16_17_18_purchase_payment_remains_pembayaran_supplier_no_duplicate_outflow(): void
    {
        $purchase = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-202610-0010',
            'purchase_date'   => '2026-10-01',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'created_by'      => $this->admin->id,
        ]);

        $poPayment = PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => '2026-10-02',
            'amount'         => '4000000.00',
            'payment_method' => 'BANK_TRANSFER',
            'reference_no'   => 'PAY-PO-0010',
            'created_by'     => $this->admin->id,
        ]);

        $expense = BusinessExpense::create([
            'expense_date' => '2026-10-03',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '250000.00',
            'description'  => 'Konsumsi operasional',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords();

        // 1. PO Payment has category Pembayaran Supplier
        $poRow = $data['records']->first(fn($r) => $r['source_type'] === 'PURCHASE_PAYMENT');
        $this->assertNotNull($poRow);
        $this->assertEquals('Pembayaran Supplier', $poRow['category']);
        $this->assertEquals('PO-202610-0010', $poRow['reference_code']);
        $this->assertEquals('KELUAR', $poRow['type']);

        // 2. Expense has category Beban Operasional
        $expRow = $data['records']->first(fn($r) => $r['source_type'] === 'EXPENSE');
        $this->assertNotNull($expRow);
        $this->assertEquals('Beban Operasional', $expRow['category']);

        // 3. No double count: total keluar must be 4,000,000 + 250,000 = 4,250,000.00
        $this->assertEquals('4250000.00', $data['summary']['total_keluar']);
    }

    // ==================================================
    // AT. TESTS — DEPENDENT FILTER
    // ==================================================

    public function test_19_20_21_flow_filters_in_out_all(): void
    {
        // 1 Inflow: BusinessIncome 1,000,000
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0011',
            'income_date' => '2026-10-10',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '1000000.00',
            'created_by'  => $this->admin->id,
        ]);

        // 1 Outflow: BusinessExpense 400,000
        BusinessExpense::create([
            'expense_date' => '2026-10-11',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '400000.00',
            'description'  => 'Listrik workshop',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);

        // flow=in
        $inRes = $service->getConsolidatedRecords(['flow' => 'in']);
        $this->assertEquals(1, $inRes['records']->total());
        $this->assertEquals('MASUK', $inRes['records']->items()[0]['type']);

        // flow=out
        $outRes = $service->getConsolidatedRecords(['flow' => 'out']);
        $this->assertEquals(1, $outRes['records']->total());
        $this->assertEquals('KELUAR', $outRes['records']->items()[0]['type']);

        // flow=all
        $allRes = $service->getConsolidatedRecords(['flow' => 'all']);
        $this->assertEquals(2, $allRes['records']->total());
    }

    public function test_22_flow_in_type_list_contains_only_income_types(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', ['flow' => 'in']));
        $response->assertStatus(200);

        $response->assertSee('Semua Jenis Pemasukan');
        $response->assertSee('<option value="customer_dp"', false);
        $response->assertSee('<option value="customer_settlement"', false);
        $response->assertSee('<option value="pemasukan_lain"', false);
        $response->assertSee('<option value="modal_tambahan"', false);
        $response->assertSee('<option value="pendapatan_non_penjualan"', false);

        // Outflow categories must NOT appear in flow=in
        $response->assertDontSee('<option value="pembayaran_supplier"', false);
        $response->assertDontSee('<option value="biaya_supplier"', false);
        $response->assertDontSee('<option value="operasional"', false);
    }

    public function test_23_flow_out_type_list_contains_only_expense_types(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', ['flow' => 'out']));
        $response->assertStatus(200);

        $response->assertSee('Semua Jenis Pengeluaran');
        $response->assertSee('<option value="pembayaran_supplier"', false);
        $response->assertDontSee('<option value="biaya_supplier"', false);
        $response->assertDontSee('Biaya Supplier');
        $response->assertSee('<option value="operasional"', false);
        $response->assertSee('<option value="marketing"', false);

        // Inflow categories must NOT appear in flow=out
        $response->assertDontSee('<option value="customer_dp"', false);
        $response->assertDontSee('<option value="customer_settlement"', false);
        $response->assertDontSee('<option value="pemasukan_lain"', false);
        $response->assertDontSee('<option value="modal_tambahan"', false);
    }

    public function test_24_flow_all_shows_all_grouped_types(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', ['flow' => 'all']));
        $response->assertStatus(200);

        $response->assertSee('optgroup label="PEMASUKAN"', false);
        $response->assertSee('optgroup label="PENGELUARAN"', false);
        $response->assertSee('Uang Muka (DP)');
        $response->assertSee('Pembayaran Supplier');
        $response->assertDontSee('Biaya Supplier');

        // Supplier category appears exactly once in the dropdown
        $content = $response->getContent();
        $this->assertEquals(1, substr_count($content, 'value="pembayaran_supplier"'));
    }

    public function test_25_and_26_server_resets_incompatible_type_on_flow_mismatch(): void
    {
        $service = app(FinancialRecordService::class);

        // Stale param: flow=in with type=pembayaran_supplier (which is outflow)
        $inMismatch = $service->getConsolidatedRecords(['flow' => 'in', 'type' => 'pembayaran_supplier']);
        $this->assertEquals('in', $inMismatch['filters']['flow']);
        $this->assertEquals('all', $inMismatch['filters']['type']);

        // Stale param: flow=out with type=customer_dp (which is inflow)
        $outMismatch = $service->getConsolidatedRecords(['flow' => 'out', 'type' => 'customer_dp']);
        $this->assertEquals('out', $outMismatch['filters']['flow']);
        $this->assertEquals('all', $outMismatch['filters']['type']);
    }

    // ==================================================
    // AU. TESTS — FILTER QUERY COMBINATIONS
    // ==================================================

    public function test_27_28_29_masuk_with_dp_pelunasan_pemasukan_lain(): void
    {
        $order = Order::create([
            'order_no'     => 'SO-202610-0020',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-01',
            'total_amount' => '10000000.00',
            'status'       => OrderStatus::DEAL,
            'created_by'   => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0020',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-01',
            'due_date'     => '2026-10-15',
            'total_amount' => '10000000.00',
            'paid_amount'  => '5000000.00',
            'status'       => InvoiceStatus::PARTIAL,
            'created_by'   => $this->admin->id,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-02',
            'amount'         => '3000000.00',
            'payment_type'   => PaymentType::DP,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-02 10:00:00'),
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-03',
            'amount'         => '2000000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-03 10:00:00'),
        ]);

        BusinessIncome::create([
            'income_no'   => 'INC-202610-0020',
            'income_date' => '2026-10-04',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '500000.00',
            'created_by'  => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);

        // DP only
        $dpRes = $service->getConsolidatedRecords(['flow' => 'in', 'type' => 'customer_dp']);
        $this->assertEquals(1, $dpRes['records']->total());
        $this->assertEquals('Uang Muka (DP)', $dpRes['records']->items()[0]['category']);

        // Pelunasan only
        $pelRes = $service->getConsolidatedRecords(['flow' => 'in', 'type' => 'customer_settlement']);
        $this->assertEquals(1, $pelRes['records']->total());
        $this->assertEquals('Pelunasan', $pelRes['records']->items()[0]['category']);

        // Pemasukan Lain only
        $incRes = $service->getConsolidatedRecords(['flow' => 'in', 'type' => 'pemasukan_lain']);
        $this->assertEquals(1, $incRes['records']->total());
        $this->assertEquals(BusinessIncome::CATEGORY_PEMASUKAN_LAIN, $incRes['records']->items()[0]['category']);
    }

    public function test_30_31_32_keluar_with_pembayaran_supplier_biaya_supplier_and_operating(): void
    {
        $purchase = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-202610-0030',
            'purchase_date'   => '2026-10-01',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'created_by'      => $this->admin->id,
        ]);

        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => '2026-10-02',
            'amount'         => '1000000.00',
            'payment_method' => 'BANK_TRANSFER',
            'reference_no'   => 'PAY-PO-0030',
            'created_by'     => $this->admin->id,
        ]);

        BusinessExpense::create([
            'expense_date' => '2026-10-03',
            'expense_type' => ExpenseType::MARKETING,
            'amount'       => '200000.00',
            'description'  => 'Iklan Instagram Nayara',
            'created_by'   => $this->admin->id,
        ]);

        BusinessExpense::create([
            'expense_date' => '2026-10-04',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '300000.00',
            'description'  => 'Internet kantor',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);

        // Pembayaran Supplier only
        $poRes = $service->getConsolidatedRecords(['flow' => 'out', 'type' => 'pembayaran_supplier']);
        $this->assertEquals(1, $poRes['records']->total());
        $this->assertEquals('Pembayaran Supplier', $poRes['records']->items()[0]['category']);

        // Marketing only
        $mktRes = $service->getConsolidatedRecords(['flow' => 'out', 'type' => 'marketing']);
        $this->assertEquals(1, $mktRes['records']->total());
        $this->assertEquals('Beban Marketing', $mktRes['records']->items()[0]['category']);

        // Operasional Usaha only
        $opRes = $service->getConsolidatedRecords(['flow' => 'out', 'type' => 'operasional']);
        $this->assertEquals(1, $opRes['records']->total());
        $this->assertEquals('Beban Operasional', $opRes['records']->items()[0]['category']);
    }

    // ==================================================
    // AV. TESTS — SEARCH MULTI-SOURCE & COMPOSABLE
    // ==================================================

    public function test_33_to_40_search_multi_source_and_composable_filters(): void
    {
        // 1. Customer Payment (Customer: Toko Cinay, SO: SO-202610-0099, INV: INV-202610-0099)
        $order = Order::create([
            'order_no'     => 'SO-202610-0099',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-05',
            'total_amount' => '4000000.00',
            'status'       => OrderStatus::DEAL,
            'created_by'   => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0099',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-05',
            'due_date'     => '2026-10-20',
            'total_amount' => '4000000.00',
            'paid_amount'  => '4000000.00',
            'status'       => InvoiceStatus::PAID,
            'created_by'   => $this->admin->id,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-05',
            'amount'         => '4000000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-05 11:00:00'),
            'notes'          => 'Pelunasan Pesanan Toko Cinay',
        ]);

        // 2. Purchase Payment (Supplier: Sumber Kain, PO: PO-202610-0088)
        $purchase = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-202610-0088',
            'purchase_date'   => '2026-10-06',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'created_by'      => $this->admin->id,
        ]);

        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => '2026-10-06',
            'amount'         => '2000000.00',
            'payment_method' => 'BANK_TRANSFER',
            'reference_no'   => 'REF-PO-0088',
            'notes'          => 'Bayar DP bahan kain',
            'created_by'     => $this->admin->id,
        ]);

        // 3. Business Income
        BusinessIncome::create([
            'income_no'   => 'INC-202610-0077',
            'income_date' => '2026-10-07',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '700000.00',
            'notes'       => 'Penjualan perca sisa',
            'created_by'  => $this->admin->id,
        ]);

        // 4. Business Expense: Operasional
        BusinessExpense::create([
            'expense_date' => '2026-10-08',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '150000.00',
            'description'  => 'Biaya perlengkapan kantor tambahan',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);

        // 33. Search customer: Toko Cinay
        $resCustomer = $service->getConsolidatedRecords(['search' => 'Toko Cinay']);
        $this->assertEquals(1, $resCustomer['records']->total());
        $this->assertEquals('Toko Cinay', $resCustomer['records']->items()[0]['customer_name']);

        // 34. Search supplier: Sumber Kain
        $resSupplier = $service->getConsolidatedRecords(['search' => 'Sumber Kain']);
        $this->assertEquals(1, $resSupplier['records']->total());
        $this->assertEquals('Sumber Kain', $resSupplier['records']->items()[0]['supplier_name']);

        // 35. Search SO number
        $resSo = $service->getConsolidatedRecords(['search' => 'SO-202610-0099']);
        $this->assertEquals(1, $resSo['records']->total());

        // 36. Search Invoice number
        $resInv = $service->getConsolidatedRecords(['search' => 'INV-202610-0099']);
        $this->assertEquals(1, $resInv['records']->total());

        // 37. Search PO number
        $resPo = $service->getConsolidatedRecords(['search' => 'PO-202610-0088']);
        $this->assertEquals(1, $resPo['records']->total());

        // 38. Search category: Pembayaran Supplier
        $resCat = $service->getConsolidatedRecords(['search' => 'Pembayaran Supplier']);
        $this->assertEquals(1, $resCat['records']->total());
        $this->assertEquals('Pembayaran Supplier', $resCat['records']->items()[0]['category']);

        // 39. Search reference / notes: perca sisa
        $resNote = $service->getConsolidatedRecords(['search' => 'perca sisa']);
        $this->assertEquals(1, $resNote['records']->total());
        $this->assertEquals('INC-202610-0077', $resNote['records']->items()[0]['reference_code']);

        // 40. Search + flow + type + period combination
        $resCombo = $service->getConsolidatedRecords([
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
            'flow'       => 'out',
            'type'       => 'pembayaran_supplier',
            'search'     => 'Sumber Kain',
        ]);
        $this->assertEquals(1, $resCombo['records']->total());
        $this->assertEquals('Pembayaran Supplier', $resCombo['records']->items()[0]['category']);
        $this->assertEquals('PO-202610-0088', $resCombo['records']->items()[0]['reference_code']);
    }

    // ==================================================
    // AW. TESTS — FINANCIAL SAFETY & FORMULA INTEGRITY
    // ==================================================

    public function test_41_to_47_financial_safety_single_count_and_no_duplicate_ledger(): void
    {
        // 41-44: Single counts
        $order = Order::create([
            'order_no'     => 'SO-202610-0050',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-01',
            'total_amount' => '1000000.00',
            'status'       => OrderStatus::DEAL,
            'created_by'   => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0050',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-01',
            'due_date'     => '2026-10-15',
            'total_amount' => '1000000.00',
            'paid_amount'  => '1000000.00',
            'status'       => InvoiceStatus::PAID,
            'created_by'   => $this->admin->id,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-01',
            'amount'         => '1000000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-01 10:00:00'),
        ]);

        BusinessIncome::create([
            'income_no'   => 'INC-202610-0050',
            'income_date' => '2026-10-02',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '500000.00',
            'created_by'  => $this->admin->id,
        ]);

        $purchase = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-202610-0050',
            'purchase_date'   => '2026-10-03',
            'status'          => PurchaseStatus::ORDERED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'created_by'      => $this->admin->id,
        ]);

        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => '2026-10-03',
            'amount'         => '600000.00',
            'payment_method' => 'BANK_TRANSFER',
            'reference_no'   => 'PAY-PO-0050',
            'created_by'     => $this->admin->id,
        ]);

        BusinessExpense::create([
            'expense_date' => '2026-10-04',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '300000.00',
            'description'  => 'Beli ATK',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords();

        // 45. Masuk = 1,000,000 + 500,000 = 1,500,000.00
        $this->assertEquals('1500000.00', $data['summary']['total_masuk']);
        // 46. Keluar = 600,000 + 300,000 = 900,000.00
        $this->assertEquals('900000.00', $data['summary']['total_keluar']);
        // Arus Bersih = 1,500,000 - 900,000 = 600,000.00
        $this->assertEquals('600000.00', $data['summary']['arus_bersih']);

        // 47. No generic duplicate ledger created in DB
        $this->assertFalse(Schema::hasTable('financial_records'));
        $this->assertFalse(Schema::hasTable('cash_transactions'));
        $this->assertFalse(Schema::hasTable('general_ledger'));
    }

    /**
     * 48. PurchasePayment detail loads Purchase items with material, natural unit quantity, unit price, and BCMath subtotal.
     * Supports multiple purchase items, handles partial payments safely without fake allocation.
     */
    public function test_purchase_payment_detail_loads_purchase_items_and_formats_quantities_prices_and_subtotals(): void
    {
        $rollUnit = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);
        $meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $pcsUnit = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $matBenang = Material::create([
            'unit_id'       => $rollUnit->id,
            'material_name' => 'Benang Jahit',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $matVoal = Material::create([
            'unit_id'       => $meterUnit->id,
            'material_name' => 'Kain Voal Premium',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $matLabel = Material::create([
            'unit_id'       => $pcsUnit->id,
            'material_name' => 'Label Brand Hijab',
            'min_stock'     => 50,
            'active'        => true,
        ]);

        $purchase = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-202610-0088',
            'purchase_date'   => '2026-10-05',
            'status'          => PurchaseStatus::RECEIVED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'created_by'      => $this->admin->id,
        ]);

        // Multiple purchase items:
        // Item 1: Benang Jahit, 39 roll @ Rp 5.000 = Rp 195.000
        PurchaseItem::create([
            'purchase_id'  => $purchase->id,
            'material_id'  => $matBenang->id,
            'qty'          => 39,
            'unit_price'   => 5000,
            'qty_received' => 39,
        ]);

        // Item 2: Kain Voal Premium, 20 m @ Rp 6.000 = Rp 120.000
        PurchaseItem::create([
            'purchase_id'  => $purchase->id,
            'material_id'  => $matVoal->id,
            'qty'          => 20,
            'unit_price'   => 6000,
            'qty_received' => 20,
        ]);

        // Item 3: Label Brand Hijab, 100 pcs @ Rp 600 = Rp 60.000
        PurchaseItem::create([
            'purchase_id'  => $purchase->id,
            'material_id'  => $matLabel->id,
            'qty'          => 100,
            'unit_price'   => 600,
            'qty_received' => 100,
        ]);

        // Total PO = 195.000 + 120.000 + 60.000 = 375.000
        // Partial Payment: Rp 150.000
        $payment = PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => '2026-10-06',
            'amount'         => '150000.00',
            'payment_method' => 'BANK_TRANSFER',
            'reference_no'   => 'PAY-PARTIAL-0088',
            'notes'          => 'Cicilan Pembayaran Tahap 1',
            'created_by'     => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords(['search' => 'PO-202610-0088']);

        $this->assertEquals(1, $data['records']->total());
        $record = $data['records']->first();

        // Check canonical fields
        $this->assertEquals('PURCHASE_PAYMENT', $record['source_type']);
        $this->assertEquals('PO-202610-0088', $record['reference_code']);

        // Invariant: The "Nominal" = amount of THIS payment transaction (150.000), not total PO
        $this->assertEquals('150000.00', $record['amount_out']);

        // Check purchase_items loaded from canonical relationship
        $this->assertArrayHasKey('purchase_items', $record);
        $this->assertCount(3, $record['purchase_items']);

        // Item 1 verification
        $item1 = $record['purchase_items'][0];
        $this->assertEquals('Benang Jahit', $item1['material_name']);
        $this->assertEquals('roll', $item1['unit']);
        $this->assertEquals('39 roll', $item1['qty_formatted']);
        $this->assertEquals('Rp 5.000', $item1['unit_price_formatted']);
        $this->assertEquals('195000.00', $item1['subtotal']);
        $this->assertEquals('Rp 195.000', $item1['subtotal_formatted']);

        // Item 2 verification
        $item2 = $record['purchase_items'][1];
        $this->assertEquals('Kain Voal Premium', $item2['material_name']);
        $this->assertEquals('m', $item2['unit']);
        $this->assertEquals('20 m', $item2['qty_formatted']);
        $this->assertEquals('Rp 6.000', $item2['unit_price_formatted']);
        $this->assertEquals('120000.00', $item2['subtotal']);
        $this->assertEquals('Rp 120.000', $item2['subtotal_formatted']);

        // Item 3 verification
        $item3 = $record['purchase_items'][2];
        $this->assertEquals('Label Brand Hijab', $item3['material_name']);
        $this->assertEquals('pcs', $item3['unit']);
        $this->assertEquals('100 pcs', $item3['qty_formatted']);
        $this->assertEquals('Rp 600', $item3['unit_price_formatted']);
        $this->assertEquals('60000.00', $item3['subtotal']);
        $this->assertEquals('Rp 60.000', $item3['subtotal_formatted']);

        // Test UI view rendering
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $response->assertStatus(200);
        $response->assertSee('Bahan Dibeli');
        $response->assertSee('Benang Jahit');
        $response->assertSee('Kain Voal Premium');
        $response->assertSee('Label Brand Hijab');
        $response->assertSee('39 roll');
        $response->assertSee('20 m');
        $response->assertSee('100 pcs');
    }

    /**
     * 49. Purchase with single material renders single row in purchase items.
     */
    public function test_purchase_payment_with_single_material_loads_single_item(): void
    {
        $meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $matCeruty = Material::create([
            'unit_id'       => $meterUnit->id,
            'material_name' => 'Kain Ceruty Premium',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        $purchase = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-202610-0099',
            'purchase_date'   => '2026-10-07',
            'status'          => PurchaseStatus::RECEIVED,
            'approval_status' => PurchaseApprovalStatus::APPROVED,
            'created_by'      => $this->admin->id,
        ]);

        PurchaseItem::create([
            'purchase_id'  => $purchase->id,
            'material_id'  => $matCeruty->id,
            'qty'          => 23.4,
            'unit_price'   => 10000,
            'qty_received' => 23.4,
        ]);

        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => '2026-10-07',
            'amount'         => '234000.00',
            'payment_method' => 'BANK_TRANSFER',
            'reference_no'   => 'PAY-SINGLE-0099',
            'created_by'     => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords(['search' => 'PO-202610-0099']);

        $record = $data['records']->first();
        $this->assertCount(1, $record['purchase_items']);
        $this->assertEquals('Kain Ceruty Premium', $record['purchase_items'][0]['material_name']);
        $this->assertEquals('23,4 m', $record['purchase_items'][0]['qty_formatted']);
        $this->assertEquals('Rp 10.000', $record['purchase_items'][0]['unit_price_formatted']);
        $this->assertEquals('234000.00', $record['purchase_items'][0]['subtotal']);
        $this->assertEquals('Rp 234.000', $record['purchase_items'][0]['subtotal_formatted']);
    }

    /**
     * 50. Non-purchase transactions (CustomerPayment, BusinessExpense, BusinessIncome) do NOT load purchase items.
     */
    public function test_non_purchase_transactions_have_empty_purchase_items(): void
    {
        $order = Order::create([
            'order_no'     => 'SO-202610-0089',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-08',
            'total_amount' => '1000000.00',
            'status'       => OrderStatus::DEAL,
            'created_by'   => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0089',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-08',
            'due_date'     => '2026-10-22',
            'total_amount' => '1000000.00',
            'paid_amount'  => '1000000.00',
            'status'       => InvoiceStatus::PAID,
            'created_by'   => $this->admin->id,
        ]);

        $customerPayment = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-08',
            'amount'         => '1000000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-08 10:00:00'),
        ]);

        $expense = BusinessExpense::create([
            'expense_date' => '2026-10-08',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '250000.00',
            'description'  => 'Biaya Listrik Kantor',
            'created_by'   => $this->admin->id,
        ]);

        $income = BusinessIncome::create([
            'income_no'   => 'INC-202610-0099',
            'income_date' => '2026-10-08',
            'category'    => BusinessIncome::CATEGORY_PEMASUKAN_LAIN,
            'amount'      => '500000.00',
            'created_by'  => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords(['start_date' => '2026-10-08', 'end_date' => '2026-10-08']);

        foreach ($data['records'] as $record) {
            $this->assertArrayHasKey('purchase_items', $record);
            $this->assertEmpty($record['purchase_items']);
            $this->assertArrayHasKey('order_items', $record);
            $this->assertEmpty($record['order_items']);
        }
    }

    /**
     * 51. Comprehensive test for CustomerPayment Item Pesanan section:
     * - Loads Invoice and related Sales Order.
     * - Displays all order items with product, variant, ordered quantity (pcs), price snapshot, and BCMath subtotal.
     * - Price snapshot is used even if variant base_selling_price subsequently changed.
     * - Quantity is ordered quantity, not production actual qty.
     * - DP and Pelunasan for same order both display full order items without distributing payment amounts.
     * - Payment nominal remains amount of current transaction.
     * - Total order remains separate from payment nominal.
     * - View renders modal with Item Pesanan, product name, variant name, and total order footer.
     */
    public function test_customer_payment_detail_loads_order_items_with_price_snapshot_and_totals(): void
    {
        $product1 = Product::create([
            'product_name' => 'Hijab Segi Empat Oval',
            'product_code' => 'PRD-HSO',
            'active'       => true,
        ]);
        $variant1 = ProductVariant::create([
            'product_id'         => $product1->id,
            'variant_name'       => 'Latte',
            'sku'                => 'HSO-LAT',
            'base_selling_price' => 89000,
            'active'             => true,
        ]);

        $product2 = Product::create([
            'product_name' => 'Hijab Voal Polos Premium',
            'product_code' => 'PRD-HVP',
            'active'       => true,
        ]);
        $variant2 = ProductVariant::create([
            'product_id'         => $product2->id,
            'variant_name'       => 'Navy',
            'sku'                => 'HVP-NAV',
            'base_selling_price' => 85000,
            'active'             => true,
        ]);

        $order = Order::create([
            'order_no'     => 'SO-202610-0003',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-01',
            'total_amount' => '3217000.00',
            'status'       => OrderStatus::PRODUKSI,
            'created_by'   => $this->admin->id,
        ]);

        // Item 1: 18 pcs @ 89.000 = 1.602.000
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant1->id,
            'qty_ordered'        => 18,
            'unit_selling_price' => 89000,
        ]);

        // Item 2: 19 pcs @ 85.000 = 1.615.000
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant2->id,
            'qty_ordered'        => 19,
            'unit_selling_price' => 85000,
        ]);

        // Mutation test: Update master variant price later to verify snapshot integrity
        $variant1->update(['base_selling_price' => 99000]);
        $variant2->update(['base_selling_price' => 95000]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0002',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-02',
            'due_date'     => '2026-10-16',
            'total_amount' => '3217000.00',
            'paid_amount'  => '3217000.00',
            'status'       => InvoiceStatus::PAID,
            'created_by'   => $this->admin->id,
        ]);

        // 1. DP Payment (Rp 1.608.500)
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-08',
            'amount'         => '1608500.00',
            'payment_type'   => PaymentType::DP,
            'payment_method' => 'TRANSFER_BCA',
            'reference_no'   => 'DP-CINAY-01',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-08 10:00:00'),
        ]);

        // 2. Pelunasan Payment (Rp 1.608.500)
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-12',
            'amount'         => '1608500.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => 'TRANSFER_BCA',
            'reference_no'   => 'PELUNASAN-CINAY-01',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-12 11:00:00'),
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords(['search' => 'INV-202610-0002']);

        $this->assertEquals(2, $data['records']->total());

        foreach ($data['records'] as $record) {
            // Source and references
            $this->assertEquals('PAYMENT', $record['source_type']);
            $this->assertEquals('INV-202610-0002', $record['reference_code']);
            $this->assertEquals('SO-202610-0003', $record['order_no']);

            // Payment nominal remains THIS payment amount (1.608.500)
            $this->assertEquals('1608500.00', $record['amount_in']);

            // Total order metrics
            $this->assertEquals('37 pcs', $record['total_order_qty_formatted']);
            $this->assertEquals('3217000.00', $record['total_order_amount']);
            $this->assertEquals('Rp 3.217.000', $record['total_order_amount_formatted']);

            // Items count
            $this->assertCount(2, $record['order_items']);

            // Item 1
            $it1 = $record['order_items'][0];
            $this->assertEquals('Hijab Segi Empat Oval', $it1['product_name']);
            $this->assertEquals('Latte', $it1['variant_name']);
            $this->assertEquals('18 pcs', $it1['qty_formatted']);
            // Price snapshot must remain 89000, NOT updated variant price 99000
            $this->assertEquals('89000.00', $it1['unit_price']);
            $this->assertEquals('Rp 89.000', $it1['unit_price_formatted']);
            $this->assertEquals('1602000.00', $it1['subtotal']);
            $this->assertEquals('Rp 1.602.000', $it1['subtotal_formatted']);

            // Item 2
            $it2 = $record['order_items'][1];
            $this->assertEquals('Hijab Voal Polos Premium', $it2['product_name']);
            $this->assertEquals('Navy', $it2['variant_name']);
            $this->assertEquals('19 pcs', $it2['qty_formatted']);
            // Price snapshot must remain 85000, NOT updated variant price 95000
            $this->assertEquals('85000.00', $it2['unit_price']);
            $this->assertEquals('Rp 85.000', $it2['unit_price_formatted']);
            $this->assertEquals('1615000.00', $it2['subtotal']);
            $this->assertEquals('Rp 1.615.000', $it2['subtotal_formatted']);

            // Non-purchase items empty
            $this->assertEmpty($record['purchase_items']);

            // Payment History & Summary Verification
            $this->assertCount(2, $record['payment_history']);

            // Oldest first: DP on 08/10, Pelunasan on 12/10
            $dpHist = $record['payment_history'][0];
            $this->assertEquals('Uang Muka (DP)', $dpHist['type_label']);
            $this->assertEquals('08/10/2026', $dpHist['date_formatted']);
            $this->assertEquals('Rp 1.608.500', $dpHist['amount_formatted']);
            $this->assertEquals('Terkonfirmasi', $dpHist['status_label']);
            $this->assertTrue($dpHist['is_confirmed']);

            $pelunasanHist = $record['payment_history'][1];
            $this->assertEquals('Pelunasan', $pelunasanHist['type_label']);
            $this->assertEquals('12/10/2026', $pelunasanHist['date_formatted']);
            $this->assertEquals('Rp 1.608.500', $pelunasanHist['amount_formatted']);
            $this->assertEquals('Terkonfirmasi', $pelunasanHist['status_label']);
            $this->assertTrue($pelunasanHist['is_confirmed']);

            // Current payment highlight check
            if ($record['category'] === 'Uang Muka (DP)') {
                $this->assertTrue($dpHist['is_current']);
                $this->assertFalse($pelunasanHist['is_current']);
            } else {
                $this->assertFalse($dpHist['is_current']);
                $this->assertTrue($pelunasanHist['is_current']);
            }

            // Summary
            $this->assertNotNull($record['payment_summary']);
            $this->assertEquals('3217000.00', $record['payment_summary']['total_invoice_amount']);
            $this->assertEquals('Rp 3.217.000', $record['payment_summary']['total_invoice_amount_formatted']);
            $this->assertEquals('3217000.00', $record['payment_summary']['total_paid']);
            $this->assertEquals('Rp 3.217.000', $record['payment_summary']['total_paid_formatted']);
            $this->assertEquals('0.00', $record['payment_summary']['outstanding']);
            $this->assertEquals('Lunas', $record['payment_summary']['status_label']);
            $this->assertTrue($record['payment_summary']['is_lunas']);
        }

        // Test UI view rendering
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $response->assertStatus(200);
        $response->assertSee('Item Pesanan');
        $response->assertSee('SO-202610-0003');
        $response->assertSee('Hijab Segi Empat Oval');
        $response->assertSee('Latte');
        $response->assertSee('Hijab Voal Polos Premium');
        $response->assertSee('Navy');
        $response->assertSee('18 pcs');
        $response->assertSee('19 pcs');
        $response->assertSee('Rp 89.000');
        $response->assertSee('Rp 85.000');
        $response->assertSee('Rp 1.602.000');
        $response->assertSee('Rp 1.615.000');
        $response->assertSee('Total Pesanan');
        $response->assertSee('37 pcs');
        $response->assertSee('Pembayaran');
        $response->assertDontSee('Riwayat Pembayaran');
        $response->assertSee('Dibayar');
        $response->assertSee('Sisa');
        $response->assertDontSee('Total Tagihan');
        $response->assertDontSee('Total Order');
        $response->assertSee('Saat ini');
        $response->assertDontSee('Pembayaran ini');
    }

    public function test_customer_payment_partial_and_unconfirmed_payments_in_history(): void
    {
        $product = Product::create([
            'product_name' => 'Gamis Silk Premium',
            'product_code' => 'PRD-GSP-001',
            'description'  => 'Test Gamis Silk',
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'VAR-GSP-DP-01',
            'base_selling_price'=> 200000,
        ]);

        $order = Order::create([
            'order_no'     => 'SO-202610-0010',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-01',
            'total_amount' => '2000000.00',
            'status'       => OrderStatus::DEAL,
            'created_by'   => $this->admin->id,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 200000,
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0010',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-01',
            'due_date'     => '2026-10-15',
            'total_amount' => '2000000.00',
            'paid_amount'  => '1000000.00',
            'status'       => InvoiceStatus::PARTIAL,
            'created_by'   => $this->admin->id,
        ]);

        // Confirmed DP
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-02',
            'amount'         => '1000000.00',
            'payment_type'   => PaymentType::DP,
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-02 09:00:00'),
        ]);

        // Pending Payment
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-05',
            'amount'         => '500000.00',
            'payment_type'   => PaymentType::LAINNYA,
            'status'         => PaymentStatus::PENDING,
        ]);

        // Cancelled Payment
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-06',
            'amount'         => '500000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'status'         => PaymentStatus::CANCELLED,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords(['search' => 'INV-202610-0010']);

        // Only the confirmed DP appears as an inflow row in the ledger
        $this->assertEquals(1, $data['records']->total());
        $record = $data['records']->first();

        // But its payment_history contains all 3 records for timeline transparency
        $this->assertCount(3, $record['payment_history']);

        // 1. Confirmed DP
        $this->assertEquals('CONFIRMED', $record['payment_history'][0]['status']);
        $this->assertEquals('Terkonfirmasi', $record['payment_history'][0]['status_label']);
        $this->assertTrue($record['payment_history'][0]['is_confirmed']);
        $this->assertTrue($record['payment_history'][0]['is_current']);

        // 2. Pending payment
        $this->assertEquals('PENDING', $record['payment_history'][1]['status']);
        $this->assertEquals('Menunggu Konfirmasi', $record['payment_history'][1]['status_label']);
        $this->assertFalse($record['payment_history'][1]['is_confirmed']);
        $this->assertFalse($record['payment_history'][1]['is_current']);

        // 3. Cancelled payment
        $this->assertEquals('CANCELLED', $record['payment_history'][2]['status']);
        $this->assertEquals('Dibatalkan', $record['payment_history'][2]['status_label']);
        $this->assertFalse($record['payment_history'][2]['is_confirmed']);
        $this->assertFalse($record['payment_history'][2]['is_current']);

        // Summary must only count confirmed payment (1.000.000), NOT pending or cancelled!
        $this->assertEquals('2000000.00', $record['payment_summary']['total_invoice_amount']);
        $this->assertEquals('1000000.00', $record['payment_summary']['total_paid']);
        $this->assertEquals('1000000.00', $record['payment_summary']['outstanding']);
        $this->assertEquals('Belum Lunas / Sebagian', $record['payment_summary']['status_label']);
        $this->assertFalse($record['payment_summary']['is_lunas']);
    }

    public function test_non_customer_transactions_do_not_have_customer_payment_history(): void
    {
        BusinessExpense::create([
            'expense_date' => '2026-10-05',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '350000.00',
            'description'  => 'Biaya Keperluan Kantor',
            'created_by'   => $this->admin->id,
        ]);

        $supplier = Supplier::create([
            'supplier_name' => 'PT Sumber Benang',
            'contact_person'=> 'Pak Sumber',
            'phone'         => '081299887766',
            'address'       => 'Solo',
        ]);

        $purchase = Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => 'PO-202610-1234',
            'purchase_date' => '2026-10-05',
            'status'        => PurchaseStatus::RECEIVED,
            'created_by'    => $this->admin->id,
        ]);

        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => '2026-10-06',
            'amount'         => '1200000.00',
            'payment_method' => 'BANK_TRANSFER',
            'reference_no'   => 'TRX-PO-1234',
            'notes'          => 'Pelunasan Bahan',
            'created_by'     => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $data = $service->getConsolidatedRecords(['flow' => 'out']);

        foreach ($data['records'] as $record) {
            $this->assertEmpty($record['payment_history']);
            $this->assertNull($record['payment_summary']);
        }
    }

    public function test_customer_payment_modal_deduplication_and_notes_presentation(): void
    {
        $product = Product::create([
            'product_name' => 'Mukena Silk Premium',
            'product_code' => 'PRD-MSP-001',
            'description'  => 'Test Mukena Silk',
        ]);

        $variant = ProductVariant::create([
            'product_id'        => $product->id,
            'variant_name'      => 'Broken White',
            'sku'               => 'VAR-MSP-BW-01',
            'base_selling_price'=> 350000,
        ]);

        $order = Order::create([
            'order_no'     => 'SO-202610-0099',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-05',
            'total_amount' => '700000.00',
            'status'       => OrderStatus::DEAL,
            'created_by'   => $this->admin->id,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 2,
            'unit_selling_price' => 350000,
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0099',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-05',
            'due_date'     => '2026-10-20',
            'total_amount' => '700000.00',
            'paid_amount'  => '700000.00',
            'status'       => InvoiceStatus::PAID,
            'created_by'   => $this->admin->id,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-05',
            'amount'         => '700000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-05 14:00:00'),
            'notes'          => 'Transfer via BCA an Siti',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $response->assertStatus(200);

        // Verification of compact, deduplicated presentation
        $response->assertSee('Catatan');
        $response->assertSee('Transfer via BCA an Siti');
        $response->assertSee('Saat ini');
        $response->assertDontSee('Pembayaran ini');
        $response->assertDontSee('Total Tagihan');
        $response->assertDontSee('Total Order');
        $response->assertDontSee('Riwayat Pembayaran');
    }
}
