<?php

namespace Tests\Feature\Finance;

use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchasePayment;
use App\Models\Master\Customer;
use App\Models\Master\Supplier;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinancialRecordFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $produksi;
    private User $gudang;
    private User $customerUser;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Keuangan',
        ]);

        $this->produksi = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->gudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Reseller A',
            'phone'         => '081234567890',
            'address'       => 'Jl. Hijab Cantik No. 1',
        ]);
    }

    private function createOrderWithInvoice(string $total = '10000000.00'): array
    {
        $order = Order::create([
            'order_no'     => 'SO-202610-0099',
            'customer_id'  => $this->customer->id,
            'order_date'   => '2026-10-01',
            'total_amount' => $total,
            'status'       => OrderStatus::DEAL,
            'created_by'   => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'invoice_no'   => 'INV-202610-0099',
            'order_id'     => $order->id,
            'invoice_date' => '2026-10-01',
            'due_date'     => '2026-10-15',
            'total_amount' => $total,
            'paid_amount'  => '0.00',
            'status'       => InvoiceStatus::UNPAID,
            'created_by'   => $this->admin->id,
        ]);

        return [$order, $invoice];
    }

    /**
     * 1. Confirmed DP appears as MASUK.
     * 2. Confirmed pelunasan appears as MASUK.
     * 3. Pending payment does not appear/count.
     * 4. Cancelled/rejected payment does not count.
     * 5. Multiple confirmed payments remain separate traceable entries.
     * 6. Total Masuk sums correctly.
     */
    public function test_money_in_confirmed_payments_appear_and_pending_or_cancelled_do_not_count(): void
    {
        [$order, $invoice] = $this->createOrderWithInvoice('5000000.00');

        // Confirmed DP
        $dp = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-02',
            'amount'         => '2000000.00',
            'payment_type'   => PaymentType::DP,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-02 10:00:00'),
            'notes'          => 'DP 40% Pesanan',
        ]);

        // Confirmed Pelunasan
        $pelunasan = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-05',
            'amount'         => '3000000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-05 14:00:00'),
            'notes'          => 'Pelunasan sisa 60%',
        ]);

        // Pending Payment (MUST NOT COUNT)
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-06',
            'amount'         => '500000.00',
            'payment_type'   => PaymentType::LAINNYA,
            'payment_method' => 'TRANSFER_MANDIRI',
            'status'         => PaymentStatus::PENDING,
            'notes'          => 'Menunggu konfirmasi mutasi',
        ]);

        // Cancelled Payment (MUST NOT COUNT)
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-07',
            'amount'         => '1000000.00',
            'payment_type'   => PaymentType::LAINNYA,
            'payment_method' => 'TRANSFER_BRI',
            'status'         => PaymentStatus::CANCELLED,
            'notes'          => 'Pembayaran dibatalkan karena salah nominal',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));

        $response->assertStatus(200);

        // Verify summary: Only 2,000,000 + 3,000,000 = 5,000,000
        $response->assertSee('Rp 5.000.000');
        $response->assertDontSee('Rp 5.500.000');
        $response->assertDontSee('Rp 6.500.000');

        // Verify traceable rows
        $response->assertSee('DP 40% Pesanan');
        $response->assertSee('Pelunasan sisa 60%');
        $response->assertDontSee('Menunggu konfirmasi mutasi');
        $response->assertDontSee('Pembayaran dibatalkan karena salah nominal');

        // Verify separate entries
        $response->assertSee('+ Rp 2.000.000');
        $response->assertSee('+ Rp 3.000.000');

        // Service level check
        $service = app(FinancialRecordService::class);
        $result = $service->getConsolidatedRecords();
        $this->assertEquals('5000000.00', $result['summary']['total_masuk']);
        $this->assertEquals(2, $result['records']->total());
    }

    /**
     * 7. Admin can add business expense.
     * 8. Expense appears as KELUAR.
     * 9. amount <= 0 rejected.
     * 10. expense actor recorded.
     * 11. Total Keluar sums correctly.
     * 12. Edited expense updates derived ledger.
     */
    public function test_business_expenses_appear_as_uang_keluar_and_validation(): void
    {
        // 9. Validation: amount <= 0 rejected
        $zeroResponse = $this->actingAs($this->admin)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-03',
            'expense_type' => ExpenseType::OPERATING->value,
            'amount'       => '0',
            'description'  => 'Biaya Nol Rupiah',
        ]);
        $zeroResponse->assertSessionHasErrors('amount');

        $negResponse = $this->actingAs($this->admin)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-03',
            'expense_type' => ExpenseType::OPERATING->value,
            'amount'       => '-50000',
            'description'  => 'Biaya Negatif',
        ]);
        $negResponse->assertSessionHasErrors('amount');

        // 7 & 10. Admin creates expense with redirect_to=financial-records
        $createResponse = $this->actingAs($this->admin)->post(route('admin.expenses.store'), [
            'expense_date' => '2026-10-04',
            'expense_type' => ExpenseType::OPERATING->value,
            'amount'       => '500000.00',
            'description'  => 'Pembayaran Listrik Workshop',
            'redirect_to'  => 'financial-records',
        ]);

        $createResponse->assertRedirect(route('admin.financial-records.index'));
        $createResponse->assertSessionHas('success');

        $this->assertDatabaseHas('business_expenses', [
            'amount'      => '500000.00',
            'description' => 'Pembayaran Listrik Workshop',
            'created_by'  => $this->admin->id,
        ]);

        $expense = BusinessExpense::where('description', 'Pembayaran Listrik Workshop')->first();

        // 8. Expense appears as KELUAR
        $viewResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Pembayaran Listrik Workshop');
        $viewResponse->assertSee('&minus; Rp 500.000', false);
        $viewResponse->assertSee('KELUAR');

        // 12. Edited expense updates derived ledger
        $this->actingAs($this->admin)->put(route('admin.expenses.update', $expense), [
            'expense_date' => '2026-10-04',
            'expense_type' => ExpenseType::OPERATING->value,
            'amount'       => '650000.00',
            'description'  => 'Pembayaran Listrik & Token Tambahan',
        ]);

        $updatedView = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $updatedView->assertStatus(200);
        $updatedView->assertSee('Pembayaran Listrik &amp; Token Tambahan', false);
        $updatedView->assertSee('&minus; Rp 650.000', false);
        $updatedView->assertDontSee('Pembayaran Listrik Workshop');
    }

    /**
     * 14. Verify chosen purchase cash-out semantics (Option A).
     * 15. No PO draft counted as cash out.
     * 16. No Confirmed/Ordered PO counted prematurely.
     * 17. No duplicate purchase/expense counting.
     */
    public function test_purchases_are_not_counted_as_cash_out_under_option_a(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'Supplier Kain Jaya',
            'contact_person' => 'Pak Budi',
            'phone' => '0812334455',
            'address' => 'Solo',
        ]);

        // Draft PO
        Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => 'PO-202610-0001',
            'purchase_date' => '2026-10-01',
            'status'        => PurchaseStatus::DRAFT,
            'notes'         => 'Draft PO Bahan',
            'created_by'    => $this->admin->id,
        ]);

        // Ordered PO
        Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => 'PO-202610-0002',
            'purchase_date' => '2026-10-02',
            'status'        => PurchaseStatus::ORDERED,
            'notes'         => 'Ordered PO Bahan Sutra',
            'created_by'    => $this->admin->id,
        ]);

        // Received PO
        Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => 'PO-202610-0003',
            'purchase_date' => '2026-10-03',
            'status'        => PurchaseStatus::RECEIVED,
            'received_at'   => Carbon::parse('2026-10-03 11:00:00'),
            'notes'         => 'Received PO Kain',
            'created_by'    => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $result = $service->getConsolidatedRecords();

        // Under Option A, purchases do not generate phantom cash out records
        $this->assertEquals('0.00', $result['summary']['total_keluar']);
        $this->assertEquals(0, $result['records']->total());

        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $response->assertStatus(200);
        $response->assertDontSee('PO-202610-0001');
        $response->assertDontSee('PO-202610-0002');
        $response->assertDontSee('PO-202610-0003');
    }

    /**
     * 18. Arus Bersih = Masuk - Keluar.
     * 19. Arus Bersih is not labeled Net Profit.
     * 20. Date range filter correct.
     * 21. Type filter correct.
     */
    public function test_arus_bersih_formula_and_filters(): void
    {
        [$order, $invoice] = $this->createOrderWithInvoice('10000000.00');

        // Payment 1: 3,000,000 on 2026-10-01
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-01',
            'amount'         => '3000000.00',
            'payment_type'   => PaymentType::DP,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-01 09:00:00'),
            'notes'          => 'DP Order 1',
        ]);

        // Payment 2: 2,000,000 on 2026-10-10
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-10',
            'amount'         => '2000000.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => 'TRANSFER_BCA',
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-10 11:00:00'),
            'notes'          => 'Pelunasan Order 1',
        ]);

        // Expense 1: 1,000,000 on 2026-10-05
        BusinessExpense::create([
            'expense_date' => '2026-10-05',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '1000000.00',
            'description'  => 'Sewa Tempat Workshop',
            'created_by'   => $this->admin->id,
        ]);

        // Expense 2: 500,000 on 2026-10-20
        BusinessExpense::create([
            'expense_date' => '2026-10-20',
            'expense_type' => ExpenseType::MARKETING,
            'amount'       => '500000.00',
            'description'  => 'Iklan Meta Ads',
            'created_by'   => $this->admin->id,
        ]);

        // Overall:
        // Masuk = 5,000,000
        // Keluar = 1,500,000
        // Arus Bersih = 3,500,000
        $allResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $allResponse->assertStatus(200);
        $allResponse->assertSee('Rp 5.000.000');
        $allResponse->assertSee('Rp 1.500.000');
        $allResponse->assertSee('Rp 3.500.000');

        // Invariant: Arus Bersih must NOT be labeled Net Profit or Laba Bersih
        $allResponse->assertSee('Arus Bersih Operasional');
        $allResponse->assertDontSee('Laba Bersih');
        $allResponse->assertDontSee('Net Profit');

        // Test Date Filter: 2026-10-01 to 2026-10-08
        // Includes: Payment 1 (3,000,000) and Expense 1 (1,000,000)
        // Excludes: Payment 2 and Expense 2
        // Arus Bersih = 2,000,000
        $dateFiltered = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-08',
        ]));
        $dateFiltered->assertStatus(200);
        $dateFiltered->assertSee('Rp 3.000.000');
        $dateFiltered->assertSee('Rp 1.000.000');
        $dateFiltered->assertSee('Rp 2.000.000');
        $dateFiltered->assertDontSee('Pelunasan Order 1');
        $dateFiltered->assertDontSee('Iklan Meta Ads');

        // Test Type Filter: Hanya Masuk
        $typeMasuk = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'type' => 'masuk',
        ]));
        $typeMasuk->assertStatus(200);
        $typeMasuk->assertSee('DP Order 1');
        $typeMasuk->assertSee('Pelunasan Order 1');
        $typeMasuk->assertDontSee('Sewa Tempat Workshop');
        $typeMasuk->assertDontSee('Iklan Meta Ads');

        // Test Type Filter: Hanya Keluar
        $typeKeluar = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'type' => 'keluar',
        ]));
        $typeKeluar->assertStatus(200);
        $typeKeluar->assertSee('Sewa Tempat Workshop');
        $typeKeluar->assertSee('Iklan Meta Ads');
        $typeKeluar->assertDontSee('DP Order 1');
        $typeKeluar->assertDontSee('Pelunasan Order 1');
    }

    /**
     * 22. Admin allowed.
     * 23. Produksi forbidden.
     * 24. Customer forbidden.
     * 25. Public route nonexistent/forbidden.
     * 26. Gudang forbidden to admin financial records.
     */
    public function test_security_and_role_authorization_on_financial_records(): void
    {
        // Unauthenticated -> redirected to login
        $guestResponse = $this->get(route('admin.financial-records.index'));
        $guestResponse->assertRedirect(route('login'));

        // Admin -> 200 OK
        $adminResponse = $this->actingAs($this->admin)->get(route('admin.financial-records.index'));
        $adminResponse->assertStatus(200);

        // Produksi -> 403 Forbidden
        $prodResponse = $this->actingAs($this->produksi)->get(route('admin.financial-records.index'));
        $prodResponse->assertStatus(403);

        // Gudang -> 403 Forbidden
        $gudangResponse = $this->actingAs($this->gudang)->get(route('admin.financial-records.index'));
        $gudangResponse->assertStatus(403);

        // Customer -> 403 Forbidden
        $custResponse = $this->actingAs($this->customerUser)->get(route('admin.financial-records.index'));
        $custResponse->assertStatus(403);
    }

    /**
     * Test search filter and source traceability.
     */
    public function test_search_filter_and_source_traceability(): void
    {
        [$order, $invoice] = $this->createOrderWithInvoice('2500000.00');

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-01',
            'amount'         => '1000000.00',
            'payment_type'   => PaymentType::DP,
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'notes'          => 'Kompensasi Khusus Reseller',
        ]);

        BusinessExpense::create([
            'expense_date' => '2026-10-02',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '250000.00',
            'description'  => 'Beli Kertas Thermal Resi Printer',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);

        // Search by payment note / customer
        $searchPay = $service->getConsolidatedRecords(['search' => 'Kompensasi Khusus']);
        $this->assertEquals(1, $searchPay['records']->total());
        $this->assertEquals('PAYMENT', $searchPay['records']->first()['source_type']);
        $this->assertNotNull($searchPay['records']->first()['reference_url']);

        // Search by expense description
        $searchExp = $service->getConsolidatedRecords(['search' => 'Kertas Thermal']);
        $this->assertEquals(1, $searchExp['records']->total());
        $this->assertEquals('EXPENSE', $searchExp['records']->first()['source_type']);
        $this->assertNotNull($searchExp['records']->first()['reference_url']);
    }

    /**
     * Invariant: Financial audit trail forbids physical deletion of expenses.
     */
    public function test_physical_destroy_is_not_allowed_for_expenses(): void
    {
        $expense = BusinessExpense::create([
            'expense_date' => '2026-10-05',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '150000.00',
            'description'  => 'Biaya Keamanan Lingkungan',
            'created_by'   => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/expenses/{$expense->id}");
        // Route doesn't exist for destroy -> 405 Method Not Allowed
        $this->assertEquals(405, $response->getStatusCode());
    }

    /**
     * Test navigation item appears in sidebar for Admin.
     */
    public function test_sidebar_displays_catatan_keuangan_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Catatan Keuangan');
        $response->assertSee(route('admin.financial-records.index'));
    }

    /**
     * Requirement 1: type=purchase_payment infers flow=out.
     * Requirement 11: flow=all + purchase_payment is normalized to effective flow=out.
     */
    public function test_type_purchase_payment_infers_flow_out(): void
    {
        $service = app(FinancialRecordService::class);

        // Without flow parameter
        $res1 = $service->getConsolidatedRecords(['type' => 'purchase_payment']);
        $this->assertEquals('out', $res1['filters']['flow']);
        $this->assertEquals('purchase_payment', $res1['filters']['type']);

        // With flow=all
        $res2 = $service->getConsolidatedRecords(['flow' => 'all', 'type' => 'purchase_payment']);
        $this->assertEquals('out', $res2['filters']['flow']);
        $this->assertEquals('purchase_payment', $res2['filters']['type']);

        // With Indonesian alias pembayaran_supplier
        $res3 = $service->getConsolidatedRecords(['type' => 'pembayaran_supplier']);
        $this->assertEquals('out', $res3['filters']['flow']);
        $this->assertEquals('pembayaran_supplier', $res3['filters']['type']);
    }

    /**
     * Requirement 2: Pembayaran Supplier selection changes UI flow to Uang Keluar.
     * Current Screenshot case:
     * Before: Semua Arus + Pembayaran Supplier
     * After: Uang Keluar + Pembayaran Supplier
     */
    public function test_pembayaran_supplier_selection_renders_ui_flow_uang_keluar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'type' => 'pembayaran_supplier',
        ]));

        $response->assertStatus(200);
        // Arus Kas has Uang Keluar selected
        $response->assertSee('<option value="out" selected>Uang Keluar</option>', false);
        // Jenis Transaksi has Pembayaran Supplier selected
        $response->assertSee('value="pembayaran_supplier" selected', false);
        // Alpine data initialized with flow = 'out'
        $response->assertSee("flow: 'out'", false);
        // Incompatible options like income types are not in the select
        $response->assertDontSee('<option value="customer_settlement"', false);
        $response->assertDontSee('<option value="customer_dp"', false);
    }

    /**
     * Requirement 3: type=customer_settlement infers flow=in.
     * Requirement 12: flow=all + settlement is normalized to effective flow=in.
     */
    public function test_type_customer_settlement_infers_flow_in(): void
    {
        $service = app(FinancialRecordService::class);

        // Without flow parameter
        $res1 = $service->getConsolidatedRecords(['type' => 'customer_settlement']);
        $this->assertEquals('in', $res1['filters']['flow']);
        $this->assertEquals('customer_settlement', $res1['filters']['type']);

        // flow=all + settlement
        $res2 = $service->getConsolidatedRecords(['flow' => 'all', 'type' => 'settlement']);
        $this->assertEquals('in', $res2['filters']['flow']);
        $this->assertEquals('settlement', $res2['filters']['type']);

        // pelunasan alias
        $res3 = $service->getConsolidatedRecords(['type' => 'pelunasan']);
        $this->assertEquals('in', $res3['filters']['flow']);
        $this->assertEquals('pelunasan', $res3['filters']['type']);
    }

    /**
     * Requirement 4: Pelunasan selection changes UI flow to Uang Masuk.
     */
    public function test_pelunasan_selection_renders_ui_flow_uang_masuk(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'type' => 'customer_settlement',
        ]));

        $response->assertStatus(200);
        // Arus Kas has Uang Masuk selected
        $response->assertSee('<option value="in" selected>Uang Masuk</option>', false);
        // Jenis Transaksi has Pelunasan selected
        $response->assertSee('value="customer_settlement" selected', false);
        // Alpine data initialized with flow = 'in'
        $response->assertSee("flow: 'in'", false);
        // Expense types hidden
        $response->assertDontSee('<option value="pembayaran_supplier"', false);
    }

    /**
     * Requirement 5: DP infers Uang Masuk.
     */
    public function test_dp_infers_uang_masuk(): void
    {
        $service = app(FinancialRecordService::class);

        $res1 = $service->getConsolidatedRecords(['type' => 'dp']);
        $this->assertEquals('in', $res1['filters']['flow']);
        $this->assertEquals('dp', $res1['filters']['type']);

        $res2 = $service->getConsolidatedRecords(['type' => 'customer_dp']);
        $this->assertEquals('in', $res2['filters']['flow']);
        $this->assertEquals('customer_dp', $res2['filters']['type']);

        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'type' => 'customer_dp',
        ]));
        $response->assertStatus(200);
        $response->assertSee('<option value="in" selected>Uang Masuk</option>', false);
        $response->assertSee('value="customer_dp" selected', false);
    }

    /**
     * Requirement 6: BusinessIncome type infers Uang Masuk.
     */
    public function test_business_income_type_infers_uang_masuk(): void
    {
        $service = app(FinancialRecordService::class);

        foreach (['pemasukan_lain', 'business_income_other', 'modal_tambahan', 'capital_injection', 'pendapatan_non_penjualan', 'non_sales_income'] as $incType) {
            $res = $service->getConsolidatedRecords(['type' => $incType]);
            $this->assertEquals('in', $res['filters']['flow'], "Failed asserting flow=in for income type {$incType}");
        }

        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'type' => 'pemasukan_lain',
        ]));
        $response->assertStatus(200);
        $response->assertSee('<option value="in" selected>Uang Masuk</option>', false);
    }

    /**
     * Requirement 7: BusinessExpense type infers Uang Keluar.
     */
    public function test_business_expense_type_infers_uang_keluar(): void
    {
        $service = app(FinancialRecordService::class);

        foreach (['operasional', 'operating', 'marketing', 'bunga', 'interest', 'pajak', 'tax', 'penyusutan', 'depreciation', 'depresiasi', 'amortisasi', 'amortization'] as $expType) {
            $res = $service->getConsolidatedRecords(['type' => $expType]);
            $this->assertEquals('out', $res['filters']['flow'], "Failed asserting flow=out for expense type {$expType}");
        }

        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'type' => 'marketing',
        ]));
        $response->assertStatus(200);
        $response->assertSee('<option value="out" selected>Uang Keluar</option>', false);
    }

    /**
     * Requirement 8: flow=in hides expense types.
     */
    public function test_flow_in_hides_expense_types(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'flow' => 'in',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Semua Jenis Pemasukan');
        $response->assertSee('value="customer_dp"', false);
        $response->assertSee('value="customer_settlement"', false);
        $response->assertDontSee('<option value="pembayaran_supplier"', false);
        $response->assertDontSee('<option value="operasional"', false);
        $response->assertDontSee('<option value="marketing"', false);
    }

    /**
     * Requirement 9: flow=out hides income types.
     */
    public function test_flow_out_hides_income_types(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'flow' => 'out',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Semua Jenis Pengeluaran');
        $response->assertSee('value="pembayaran_supplier"', false);
        $response->assertSee('value="operasional"', false);
        $response->assertDontSee('<option value="customer_dp"', false);
        $response->assertDontSee('<option value="customer_settlement"', false);
        $response->assertDontSee('<option value="pemasukan_lain"', false);
    }

    /**
     * Requirement 10: Changing flow invalidates incompatible selected type.
     */
    public function test_changing_flow_invalidates_incompatible_selected_type(): void
    {
        $service = app(FinancialRecordService::class);

        // flow=in but type is an outflow type -> type resets to 'all'
        $res1 = $service->getConsolidatedRecords(['flow' => 'in', 'type' => 'pembayaran_supplier']);
        $this->assertEquals('in', $res1['filters']['flow']);
        $this->assertEquals('all', $res1['filters']['type']);

        // flow=out but type is an inflow type -> type resets to 'all'
        $res2 = $service->getConsolidatedRecords(['flow' => 'out', 'type' => 'customer_settlement']);
        $this->assertEquals('out', $res2['filters']['flow']);
        $this->assertEquals('all', $res2['filters']['type']);

        // HTTP check: flow=in with type=pembayaran_supplier resets to Semua Jenis Pemasukan
        $response = $this->actingAs($this->admin)->get(route('admin.financial-records.index', [
            'flow' => 'in',
            'type' => 'pembayaran_supplier',
        ]));
        $response->assertStatus(200);
        $response->assertSee('Semua Jenis Pemasukan');
        $response->assertSee('value="all" selected', false);
    }

    /**
     * Requirement 13: Search + period + inferred flow + type still work together.
     */
    public function test_search_period_inferred_flow_and_type_work_together(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'PT Benang Jaya',
            'contact_person'=> 'Pak Benang',
            'phone'         => '081233445566',
            'address'       => 'Bandung',
        ]);

        $purchase = Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => 'PO-202610-9999',
            'purchase_date' => '2026-10-05',
            'status'        => PurchaseStatus::RECEIVED,
            'created_by'    => $this->admin->id,
        ]);

        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'payment_date'   => '2026-10-06',
            'amount'         => '1750000.00',
            'payment_method' => 'BANK_TRANSFER',
            'reference_no'   => 'TRX-PO-9999',
            'notes'          => 'Pembayaran Bahan Benang Bordir',
            'created_by'     => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $result = $service->getConsolidatedRecords([
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-10',
            'search'     => 'Benang Bordir',
            'type'       => 'purchase_payment',
        ]);

        $this->assertEquals('out', $result['filters']['flow']);
        $this->assertEquals('purchase_payment', $result['filters']['type']);
        $this->assertEquals(1, $result['records']->total());
        $this->assertEquals('PO-202610-9999', $result['records']->first()['reference_code']);
    }

    /**
     * Requirement 14: No financial totals changed by this UI/filter fix.
     */
    public function test_no_financial_totals_changed_by_filter_enhancement(): void
    {
        [$order, $invoice] = $this->createOrderWithInvoice('5000000.00');

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-02',
            'amount'         => '2000000.00',
            'payment_type'   => PaymentType::DP,
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
        ]);

        BusinessExpense::create([
            'expense_date' => '2026-10-03',
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '500000.00',
            'description'  => 'Biaya Listrik Workshop',
            'created_by'   => $this->admin->id,
        ]);

        $service = app(FinancialRecordService::class);
        $unfiltered = $service->getConsolidatedRecords(['flow' => 'all', 'type' => 'all']);

        $this->assertEquals('2000000.00', $unfiltered['summary']['total_masuk']);
        $this->assertEquals('500000.00', $unfiltered['summary']['total_keluar']);
        $this->assertEquals('1500000.00', $unfiltered['summary']['arus_bersih']);
    }

    /**
     * Test payment history displays both DP and Pelunasan without mutating any records or cash calculations.
     */
    public function test_customer_payment_history_safety_and_immutability(): void
    {
        [$order, $invoice] = $this->createOrderWithInvoice('3217000.00');

        $dp = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-08',
            'amount'         => '1608500.00',
            'payment_type'   => PaymentType::DP,
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-08 23:11:00'),
        ]);

        $pelunasan = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_date'   => '2026-10-12',
            'amount'         => '1608500.00',
            'payment_type'   => PaymentType::PELUNASAN,
            'status'         => PaymentStatus::CONFIRMED,
            'confirmed_by'   => $this->admin->id,
            'confirmed_at'   => Carbon::parse('2026-10-12 21:34:00'),
        ]);

        $paymentCountBefore = Payment::count();
        $invoiceBefore = $invoice->fresh()->toArray();

        $service = app(FinancialRecordService::class);
        $result = $service->getConsolidatedRecords(['search' => 'INV-202610-0099']);

        // Immutability checks: No new payment created, invoice unchanged
        $this->assertEquals($paymentCountBefore, Payment::count());
        $this->assertEquals($invoiceBefore, $invoice->fresh()->toArray());

        // Cash calculations unchanged
        $this->assertEquals('3217000.00', $result['summary']['total_masuk']);
        $this->assertEquals('0.00', $result['summary']['total_keluar']);
        $this->assertEquals('3217000.00', $result['summary']['arus_bersih']);

        // Check both records in the ledger
        $this->assertCount(2, $result['records']);

        // Record 1 (Pelunasan, newer date)
        $pelunasanRow = $result['records']->firstWhere('source_id', $pelunasan->id);
        $this->assertNotNull($pelunasanRow);
        $this->assertEquals('1608500.00', $pelunasanRow['amount_in']);
        $this->assertCount(2, $pelunasanRow['payment_history']);
        $this->assertEquals('08/10/2026', $pelunasanRow['payment_history'][0]['date_formatted']);
        $this->assertEquals('12/10/2026', $pelunasanRow['payment_history'][1]['date_formatted']);
        $this->assertFalse($pelunasanRow['payment_history'][0]['is_current']);
        $this->assertTrue($pelunasanRow['payment_history'][1]['is_current']);

        // Record 2 (DP, earlier date)
        $dpRow = $result['records']->firstWhere('source_id', $dp->id);
        $this->assertNotNull($dpRow);
        $this->assertEquals('1608500.00', $dpRow['amount_in']);
        $this->assertCount(2, $dpRow['payment_history']);
        $this->assertEquals('08/10/2026', $dpRow['payment_history'][0]['date_formatted']);
        $this->assertEquals('12/10/2026', $dpRow['payment_history'][1]['date_formatted']);
        $this->assertTrue($dpRow['payment_history'][0]['is_current']);
        $this->assertFalse($dpRow['payment_history'][1]['is_current']);

        // Both show full invoice summary
        $this->assertEquals('3217000.00', $dpRow['payment_summary']['total_invoice_amount']);
        $this->assertEquals('3217000.00', $dpRow['payment_summary']['total_paid']);
        $this->assertEquals('0.00', $dpRow['payment_summary']['outstanding']);
        $this->assertEquals('Lunas', $dpRow['payment_summary']['status_label']);
    }
}

