<?php

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\PaymentService;
use App\Services\Sales\SalesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private InvoiceService $invoiceService;
    private PaymentService $paymentService;
    private SalesReportService $salesReportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->invoiceService = app(InvoiceService::class);
        $this->paymentService = app(PaymentService::class);
        $this->salesReportService = app(SalesReportService::class);
    }

    private function createOrderWithItem(
        string $orderDate,
        OrderStatus $status,
        int $qty = 50,
        int $unitPrice = 150000,
        ?string $customerName = 'Test Customer'
    ): Order {
        $customer = Customer::create([
            'customer_name' => $customerName,
            'phone'         => '081234567890',
            'address'       => 'Jl. Riau No. 10 Bandung',
        ]);

        $product = Product::create([
            'product_name' => 'Voal Syar\'i ' . uniqid(),
            'active'       => true,
        ]);

        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Black',
            'sku'                => 'SKU-' . uniqid(),
            'base_selling_price' => $unitPrice,
        ]);

        $totalAmount = $qty * $unitPrice;

        $order = Order::create([
            'order_no'    => 'SO-202608-' . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'order_date'  => $orderDate,
            'status'      => $status,
            'total_qty'   => $qty,
            'grand_total' => $totalAmount,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => $unitPrice,
            'subtotal'           => $totalAmount,
        ]);

        return $order;
    }

    public function test_komunikasi_order_is_strictly_excluded_from_revenue(): void
    {
        // KOMUNIKASI order
        $this->createOrderWithItem('2026-08-15', OrderStatus::KOMUNIKASI, 50, 150000);

        $report = $this->salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('0.00', $report['totalRevenue']);
        $this->assertCount(0, $report['rows']);
    }

    public function test_deal_order_is_included_in_revenue(): void
    {
        $this->createOrderWithItem('2026-08-15', OrderStatus::DEAL, 50, 150000);

        $report = $this->salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('7500000.00', $report['totalRevenue']);
        $this->assertEquals(50, $report['totalQty']);
        $this->assertCount(1, $report['rows']);
    }

    public function test_multi_order_revenue_calculation_is_derived_from_items_not_payments(): void
    {
        // Order A: 50 pcs x 150.000 = 7.500.000
        $this->createOrderWithItem('2026-08-10', OrderStatus::DEAL, 50, 150000);

        // Order B: 30 pcs x 200.000 = 6.000.000
        $this->createOrderWithItem('2026-08-12', OrderStatus::PRODUKSI, 30, 200000);

        $report = $this->salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('13500000.00', $report['totalRevenue']);
        $this->assertEquals(80, $report['totalQty']);
        $this->assertCount(2, $report['rows']);
    }

    public function test_order_without_invoice_is_included_in_revenue_with_unbilled_status(): void
    {
        $order = $this->createOrderWithItem('2026-08-15', OrderStatus::DEAL, 50, 150000);

        $report = $this->salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('7500000.00', $report['totalRevenue']);
        $this->assertEquals('0.00', $report['totalPaid']);
        $this->assertEquals('0.00', $report['totalOutstanding']);

        $row = $report['rows'][0];
        $this->assertFalse($row['has_invoice']);
        $this->assertEquals('Belum dibuat', $row['invoice_no']);
        $this->assertEquals('—', $row['invoice_status']);
        $this->assertEquals('0.00', $row['paid_amount']);
        $this->assertNull($row['outstanding']);
    }

    public function test_cancelled_payment_is_excluded_from_received_cash(): void
    {
        $order = $this->createOrderWithItem('2026-08-15', OrderStatus::DEAL, 90, 150000); // 13.500.000
        $invoice = $this->invoiceService->createForOrder($order);

        // Confirmed payment: 5.000.000 via LAINNYA
        $payment1 = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 5000000,
            'payment_date'   => '2026-08-16',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        // Second payment: 8.500.000 then CANCELLED
        $payment2 = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 8500000,
            'payment_date'   => '2026-08-17',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);
        $this->paymentService->cancelPayment($payment2);

        $report = $this->salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('13500000.00', $report['totalRevenue']);
        $this->assertEquals('5000000.00', $report['totalPaid']);
        $this->assertEquals('8500000.00', $report['totalOutstanding']);
    }

    public function test_fully_paid_order_case(): void
    {
        $order = $this->createOrderWithItem('2026-08-15', OrderStatus::DEAL, 90, 150000); // 13.500.000
        $invoice = $this->invoiceService->createForOrder($order);

        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-16',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-17',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        $report = $this->salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('13500000.00', $report['totalRevenue']);
        $this->assertEquals('13500000.00', $report['totalPaid']);
        $this->assertEquals('0.00', $report['totalOutstanding']);
        $this->assertEquals('Lunas', $report['rows'][0]['invoice_status']);
    }

    public function test_confirmed_payment_belonging_to_selected_order_counts_even_if_payment_date_is_later(): void
    {
        // Order date is in August (selected period)
        $order = $this->createOrderWithItem('2026-08-25', OrderStatus::DEAL, 50, 150000);
        $invoice = $this->invoiceService->createForOrder($order);

        // Payment date is in September (later than August)
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 3750000,
            'payment_date'   => '2026-09-02',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        // Filter August: The order is in August, so its invoice confirmed payments must count
        $report = $this->salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('7500000.00', $report['totalRevenue']);
        $this->assertEquals('3750000.00', $report['totalPaid']);
        $this->assertEquals('3750000.00', $report['totalOutstanding']);
    }

    public function test_order_outside_selected_period_does_not_count(): void
    {
        // Order in July
        $orderJuly = $this->createOrderWithItem('2026-07-20', OrderStatus::DEAL, 50, 150000);
        $invoiceJuly = $this->invoiceService->createForOrder($orderJuly);
        $this->paymentService->recordPayment($invoiceJuly, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 7500000,
            'payment_date'   => '2026-08-05',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        // Order in August
        $orderAug = $this->createOrderWithItem('2026-08-10', OrderStatus::DEAL, 20, 100000);

        // Report for August only
        $report = $this->salesReportService->generateReport('2026-08-01', '2026-08-31');

        $this->assertEquals('2000000.00', $report['totalRevenue']);
        $this->assertEquals('0.00', $report['totalPaid']);
        $this->assertCount(1, $report['rows']);
        $this->assertEquals($orderAug->order_no, $report['rows'][0]['order_no']);
    }

    public function test_admin_can_view_sales_report_page(): void
    {
        $order = $this->createOrderWithItem('2026-08-15', OrderStatus::DEAL, 50, 150000);
        $this->invoiceService->createForOrder($order);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.sales', [
            'start_date' => '2026-08-01',
            'end_date'   => '2026-08-31',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Laporan Penjualan');
        $response->assertSee('Total Pendapatan');
        $response->assertSee('Pembayaran Diterima');
        $response->assertSee('Sisa Tagihan');
        $response->assertSee($order->order_no);
        $response->assertSee('Unduh CSV');
    }

    public function test_web_report_and_csv_export_totals_match_exactly_with_formula_injection_defense(): void
    {
        // Malicious customer name with formula injection '=1+2'
        $order = $this->createOrderWithItem('2026-08-15', OrderStatus::DEAL, 50, 150000, '=MaliciousCustomer');
        $invoice = $this->invoiceService->createForOrder($order);
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 3750000,
            'payment_date'   => '2026-08-16',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.sales.export-csv', [
            'start_date' => '2026-08-01',
            'end_date'   => '2026-08-31',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('laporan_penjualan_2026-08-01_2026-08-31.csv', $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();

        // UTF-8 BOM check
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Header check
        $this->assertStringContainsString('Tanggal Order', $content);
        $this->assertStringContainsString('No Sales Order', $content);
        $this->assertStringContainsString('Customer', $content);
        $this->assertStringContainsString('Total Qty', $content);
        $this->assertStringContainsString('Total Pendapatan', $content);

        // Formula injection protection: '=MaliciousCustomer' must be escaped as "'=MaliciousCustomer"
        $this->assertStringContainsString("'=MaliciousCustomer", $content);

        // Machine readable values
        $this->assertStringContainsString('7500000.00', $content);
        $this->assertStringContainsString('3750000.00', $content);
        $this->assertStringNotContainsString('Rp', $content);

        // Summary row check
        $this->assertStringContainsString('TOTAL', $content);
    }

    public function test_non_admin_cannot_access_sales_report(): void
    {
        $roles = [UserRole::GUDANG, UserRole::PRODUKSI, UserRole::CUSTOMER];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.reports.sales'))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.reports.sales.export-csv'))->assertStatus(403);
        }
    }
}
