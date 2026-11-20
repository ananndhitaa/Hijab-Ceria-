<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\ExpenseType;
use App\Enums\InvoiceStatus;
use App\Enums\MovementType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\PurchasePayment;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\User;
use App\Services\Finance\PeriodFinancialService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerFinancialSummaryFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private User $gudang;
    private User $produksi;
    private User $customerUser;

    private Customer $customer;
    private Supplier $supplier;
    private Material $material;
    private ProductVariant $variant;
    private PeriodFinancialService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PeriodFinancialService::class);

        $this->owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->produksi = User::factory()->create(['role' => UserRole::PRODUKSI, 'status' => UserStatus::ACTIVE]);
        $this->customerUser = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);

        $this->customer = Customer::create(['customer_name' => 'Nayara Partner Store', 'phone' => '08123456789']);
        $this->supplier = Supplier::create(['supplier_name' => 'Tekstil Utama Jaya', 'phone' => '08987654321']);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->material = Material::create([
            'material_name' => 'Kain Woolpeach Premium',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => 1000,
            'avg_unit_cost' => 20000,
        ]);

        $product = Product::create(['product_name' => 'Gamis Nayara Signature']);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Hitam Pekat',
            'sku'                => 'GMS-BLK',
            'base_selling_price' => 150000,
        ]);

        OpeningCashBalance::create([
            'opening_date' => '2026-01-01',
            'amount'       => '100000000.00',
            'notes'        => 'Saldo Awal Operasional NAYARA',
            'created_by'   => $this->admin->id,
        ]);
    }

    private function createCompletedOrder(string $orderNo, string $orderDate, int $qty, float $price, float $laborCost = 0): Order
    {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => $orderNo,
            'order_date'   => $orderDate,
            'status'       => 'DEAL',
            'total_qty'    => $qty,
            'total_amount' => $qty * $price,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => $price,
            'subtotal'           => $qty * $price,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => "BAT-{$orderNo}",
            'target_qty'                                    => $qty,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => number_format($qty > 0 ? $laborCost / $qty : 0, 2, '.', ''),
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => $qty,
            'allocated_qty' => $qty,
            'used_qty'      => $qty,
        ]);

        StockMovement::create([
            'material_id'         => $this->material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => $qty,
            'unit_cost_snapshot'  => '20000.0000',
            'total_cost_snapshot' => number_format($qty * 20000, 2, '.', ''),
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => $orderDate,
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $qty,
            'result_date' => $orderDate,
        ]);

        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => $qty,
            'reject_qty'           => 0,
            'qc_date'              => $orderDate,
        ]);

        return $order;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGRESSION TESTS 1-4: MATHEMATICAL CASES FOR SUPERVISOR ROI
    // ─────────────────────────────────────────────────────────────────────────

    public function test_case_1_pendapatan_15m_pengeluaran_10m_yields_roi_150_percent(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        $order = $this->createCompletedOrder('ORD-CASE-1', '2026-09-05', 100, 150000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CASE-1',
            'invoice_date' => '2026-09-05',
            'total_amount' => 15000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        // Customer payment confirmed: Rp15.000.000
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '15000000.00',
            'payment_date'   => '2026-09-10',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // Supplier payment: Rp6.000.000
        $purchase = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-CASE-1',
            'purchase_date' => '2026-09-05',
            'status'        => PurchaseStatus::RECEIVED,
            'total_amount'  => '10000000.00',
            'created_by'    => $this->admin->id,
        ]);
        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => '6000000.00',
            'payment_date'   => '2026-09-12',
            'payment_method' => 'TRANSFER_BANK',
            'created_by'     => $this->admin->id,
        ]);

        // Business expense: Rp4.000.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '4000000.00',
            'expense_date' => '2026-09-15',
            'description'  => 'Operasional Workshop',
            'created_by'   => $this->admin->id,
        ]);

        // Total Pengeluaran = 6.000.000 + 4.000.000 = 10.000.000
        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertEquals('15000000.00', $res['payment_received']);
        $this->assertEquals('15000000.00', $res['pendapatan_diterima']);
        $this->assertEquals('10000000.00', $res['total_pengeluaran']);
        $this->assertEquals('150.0000', $res['roi']);
        $this->assertEquals('150,00%', $res['roi_label']);
    }

    public function test_case_2_pendapatan_10m_pengeluaran_10m_yields_roi_100_percent(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        $order = $this->createCompletedOrder('ORD-CASE-2', '2026-09-05', 100, 100000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CASE-2',
            'invoice_date' => '2026-09-05',
            'total_amount' => 10000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '10000000.00',
            'payment_date'   => '2026-09-10',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        BusinessExpense::create([
            'expense_type' => ExpenseType::MARKETING,
            'amount'       => '10000000.00',
            'expense_date' => '2026-09-15',
            'description'  => 'Digital Marketing Campaign',
            'created_by'   => $this->admin->id,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertEquals('10000000.00', $res['payment_received']);
        $this->assertEquals('10000000.00', $res['total_pengeluaran']);
        $this->assertEquals('100.0000', $res['roi']);
        $this->assertEquals('100,00%', $res['roi_label']);
    }

    public function test_case_3_pendapatan_5m_pengeluaran_10m_yields_roi_50_percent(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        $order = $this->createCompletedOrder('ORD-CASE-3', '2026-09-05', 50, 100000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CASE-3',
            'invoice_date' => '2026-09-05',
            'total_amount' => 5000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '5000000.00',
            'payment_date'   => '2026-09-10',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '10000000.00',
            'expense_date' => '2026-09-15',
            'description'  => 'Biaya Operasional',
            'created_by'   => $this->admin->id,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertEquals('5000000.00', $res['payment_received']);
        $this->assertEquals('10000000.00', $res['total_pengeluaran']);
        $this->assertEquals('50.0000', $res['roi']);
        $this->assertEquals('50,00%', $res['roi_label']);
    }

    public function test_case_4_pengeluaran_0_yields_belum_dapat_dihitung_and_null_roi(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // Order and receipt exist, but ZERO expenditure (no expenses, no purchase payments)
        $order = $this->createCompletedOrder('ORD-CASE-4', '2026-09-05', 50, 100000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CASE-4',
            'invoice_date' => '2026-09-05',
            'total_amount' => 5000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '2000000.00',
            'payment_date'   => '2026-09-06',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertEquals('0.00', $res['total_pengeluaran']);
        $this->assertNull($res['roi']);
        $this->assertEquals('Belum dapat dihitung', $res['roi_label']);
        $this->assertEquals('Belum ada pengeluaran pada periode ini.', $res['roi_helper']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGRESSION TESTS 5-7: PAYMENT STATUS FILTERING
    // ─────────────────────────────────────────────────────────────────────────

    public function test_case_5_pending_customer_payment_is_excluded(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        $order = $this->createCompletedOrder('ORD-CASE-5', '2026-09-05', 20, 100000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CASE-5',
            'invoice_date' => '2026-09-05',
            'total_amount' => 2000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '1000000.00',
            'payment_date'   => '2026-09-06',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::PENDING,
        ]);

        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '500000.00',
            'expense_date' => '2026-09-06',
            'description'  => 'Biaya Kirim',
            'created_by'   => $this->admin->id,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        // Pending payment is strictly excluded from cash receipts
        $this->assertEquals('0.00', $res['payment_received']);
        // Standardized ROI includes valid order revenue (2.000.000 / 500.000) * 100 = 400.0000%
        $this->assertEquals('400.0000', $res['roi']);
    }

    public function test_case_6_cancelled_and_rejected_customer_payments_are_excluded(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        $order = $this->createCompletedOrder('ORD-CASE-6', '2026-09-05', 20, 100000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CASE-6',
            'invoice_date' => '2026-09-05',
            'total_amount' => 2000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '500000.00',
            'payment_date'   => '2026-09-06',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CANCELLED,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertEquals('0.00', $res['payment_received']);
    }

    public function test_case_7_confirmed_customer_payment_is_included(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        $order = $this->createCompletedOrder('ORD-CASE-7', '2026-09-05', 20, 100000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CASE-7',
            'invoice_date' => '2026-09-05',
            'total_amount' => 2000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '1200000.00',
            'payment_date'   => '2026-09-06',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertEquals('1200000.00', $res['payment_received']);
        $this->assertEquals('1200000.00', $res['pendapatan_diterima']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGRESSION TESTS 8-11: EXPENDITURE COMPOSITION & DOUBLE COUNTING SAFEGUARDS
    // ─────────────────────────────────────────────────────────────────────────

    public function test_case_8_purchase_payment_is_included_as_expenditure(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        $purchase = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-CASE-8',
            'purchase_date' => '2026-09-02',
            'status'        => PurchaseStatus::RECEIVED,
            'total_amount'  => '5000000.00',
            'created_by'    => $this->admin->id,
        ]);

        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => '3500000.00',
            'payment_date'   => '2026-09-04',
            'payment_method' => 'TRANSFER_BANK',
            'created_by'     => $this->admin->id,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertEquals('3500000.00', $res['supplier_payments']);
        $this->assertEquals('3500000.00', $res['total_pengeluaran']);
    }

    public function test_case_9_business_expense_is_included_as_expenditure(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '1500000.00',
            'expense_date' => '2026-09-10',
            'description'  => 'Gaji Operator',
            'created_by'   => $this->admin->id,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertEquals('1500000.00', $res['business_expenses']);
        $this->assertEquals('1500000.00', $res['total_pengeluaran']);
    }

    public function test_case_10_stock_receiving_is_not_directly_counted_as_expenditure(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // Material received on PO, but NOT paid yet
        $purchase = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-CASE-10',
            'purchase_date' => '2026-09-02',
            'status'        => PurchaseStatus::RECEIVED,
            'total_amount'  => '8000000.00',
            'created_by'    => $this->admin->id,
        ]);

        // Stock movement IN recorded for receiving materials
        StockMovement::create([
            'material_id'         => $this->material->id,
            'movement_type'       => MovementType::IN,
            'qty'                 => 400,
            'unit_cost_snapshot'  => '20000.0000',
            'total_cost_snapshot' => '8000000.00',
            'reference_type'      => 'purchases',
            'reference_id'        => $purchase->id,
            'movement_date'       => '2026-09-02',
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        // Receiving material physically is NOT a cash outflow event
        $this->assertEquals('0.00', $res['supplier_payments']);
        $this->assertEquals('0.00', $res['total_pengeluaran']);
    }

    public function test_case_11_hpp_is_not_added_again_to_roi_expense_denominator(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // Order completed with HPP 2.000.000 (material 100 * 20.000)
        $order = $this->createCompletedOrder('ORD-CASE-11', '2026-09-05', 100, 50000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-CASE-11',
            'invoice_date' => '2026-09-05',
            'total_amount' => 5000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '5000000.00',
            'payment_date'   => '2026-09-06',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // Supplier payment of 2.000.000
        $purchase = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-CASE-11',
            'purchase_date' => '2026-09-01',
            'status'        => PurchaseStatus::RECEIVED,
            'total_amount'  => '2000000.00',
            'created_by'    => $this->admin->id,
        ]);
        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => '2000000.00',
            'payment_date'   => '2026-09-02',
            'payment_method' => 'TRANSFER_BANK',
            'created_by'     => $this->admin->id,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        // HPP exists as production profitability analytic: 2.000.000
        $this->assertEquals('2000000.00', $res['hpp']);

        // Denominator of ROI is strictly Supplier Payment (2.000.000), HPP is NOT added again!
        // If HPP were added again, denominator would be 4.000.000.
        $this->assertEquals('2000000.00', $res['total_pengeluaran']);
        // ROI = 5.000.000 / 2.000.000 * 100 = 250.0000%
        $this->assertEquals('250.0000', $res['roi']);
    }

    public function test_case_12_roi_respects_selected_period(): void
    {
        // Period A: September
        $orderA = $this->createCompletedOrder('ORD-SEP', '2026-09-05', 50, 100000);
        $invA = Invoice::create([
            'order_id'     => $orderA->id,
            'invoice_no'   => 'INV-SEP',
            'invoice_date' => '2026-09-05',
            'total_amount' => 5000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);
        Payment::create([
            'invoice_id'     => $invA->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '5000000.00',
            'payment_date'   => '2026-09-10',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '2500000.00',
            'expense_date' => '2026-09-15',
            'description'  => 'Beban September',
            'created_by'   => $this->admin->id,
        ]);

        // Period B: October
        $orderB = $this->createCompletedOrder('ORD-OCT', '2026-10-05', 50, 100000);
        $invB = Invoice::create([
            'order_id'     => $orderB->id,
            'invoice_no'   => 'INV-OCT',
            'invoice_date' => '2026-10-05',
            'total_amount' => 5000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);
        Payment::create([
            'invoice_id'     => $invB->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '10000000.00',
            'payment_date'   => '2026-10-10',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '10000000.00',
            'expense_date' => '2026-10-15',
            'description'  => 'Beban Oktober',
            'created_by'   => $this->admin->id,
        ]);

        // Query September only: ROI = 5m / 2.5m = 200%
        $resSep = $this->service->calculatePeriodFinancials('2026-09-01', '2026-09-30');
        $this->assertEquals('5000000.00', $resSep['payment_received']);
        $this->assertEquals('2500000.00', $resSep['total_pengeluaran']);
        $this->assertEquals('200.0000', $resSep['roi']);

        // Query October only: Standardized ROI = 5m revenue / 10m expense = 50%
        $resOct = $this->service->calculatePeriodFinancials('2026-10-01', '2026-10-31');
        $this->assertEquals('10000000.00', $resOct['payment_received']);
        $this->assertEquals('10000000.00', $resOct['total_pengeluaran']);
        $this->assertEquals('50.0000', $resOct['roi']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGRESSION TESTS 13-21: OWNER VIEW & MINIMAL METRICS
    // ─────────────────────────────────────────────────────────────────────────

    public function test_case_13_owner_can_view_financial_summary_page_and_alias(): void
    {
        $this->actingAs($this->owner)->get(route('owner.performance.index'))->assertOk();
        $this->actingAs($this->owner)->get(route('owner.financial-summary.index'))->assertOk();
    }

    public function test_cases_14_to_21_owner_sees_all_minimal_financial_metrics_on_page(): void
    {
        // Setup realistic financial data for the month
        $today = now('Asia/Jakarta')->toDateString();

        // 1. Total Penjualan: 10 pcs * 150.000 = 1.500.000
        $order = $this->createCompletedOrder('ORD-VIEW-01', $today, 10, 150000);

        // 2. Pendapatan Diterima: 1.000.000
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-VIEW-01',
            'invoice_date' => $today,
            'total_amount' => 1500000,
            'status'       => InvoiceStatus::UNPAID,
        ]);
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '1000000.00',
            'payment_date'   => $today,
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // 3. Purchase Payment (Supplier): 400.000
        $purchase = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-VIEW-01',
            'purchase_date' => $today,
            'status'        => PurchaseStatus::RECEIVED,
            'created_by'    => $this->admin->id,
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'material_id' => $this->material->id,
            'qty'         => 50,
            'unit_price'  => 20000,
        ]);
        PurchasePayment::create([
            'purchase_id'    => $purchase->id,
            'amount'         => '400000.00',
            'payment_date'   => $today,
            'payment_method' => 'TRANSFER_BANK',
            'created_by'     => $this->admin->id,
        ]);

        // 4. Business Expense: 100.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '100000.00',
            'expense_date' => $today,
            'description'  => 'Biaya Listrik',
            'created_by'   => $this->admin->id,
        ]);

        // Total Pengeluaran = 400.000 + 100.000 = 500.000
        // ROI = 1.000.000 / 500.000 * 100 = 200,00%
        // HPP = 10 * 20.000 = 200.000
        // Gross Profit = 1.500.000 - 200.000 = 1.300.000
        // Net Profit = 1.300.000 - 100.000 = 1.200.000
        // Piutang = 1.500.000 - 1.000.000 = 500.000
        // Komitmen PO = 1.000.000 - 400.000 = 600.000

        $response = $this->actingAs($this->owner)->get(route('owner.performance.index'));

        $response->assertOk();

        // 14. Owner sees Total Sales
        $response->assertSee('Total Penjualan');
        $response->assertSee('1.500.000');

        // 15. Owner sees Pendapatan Diterima
        $response->assertSee('Pendapatan Diterima');
        $response->assertSee('1.000.000');

        // Piutang Customer
        $response->assertSee('Piutang Pelanggan');
        $response->assertSee('500.000');

        // 16. Owner sees Total Pengeluaran
        $response->assertSee('Total Pengeluaran');
        $response->assertSee('500.000');

        // 17. Owner sees Current Cash (starts with Rp100.000.000 opening cash + delta)
        $response->assertSee('Kas Saat Ini');
        $response->assertDontSee('Saldo kas operasional terkini');

        // 18. Owner sees HPP / Modal Produksi
        $response->assertSee('HPP / Modal Produksi');
        $response->assertSee('200.000');

        // 19. Owner sees Gross Profit
        $response->assertSee('Laba Kotor');
        $response->assertSee('1.300.000');

        // 20. Owner sees Net Profit
        $response->assertSee('Laba Bersih');
        $response->assertSee('1.200.000');

        // 21. Owner sees ROI: (1.500.000 / 500.000) * 100 = 300,00%
        $response->assertSee('ROI');
        $response->assertSee('300,00%');

        // Komitmen PO
        $response->assertSee('Komitmen PO (Sisa Kewajiban)');
        $response->assertSee('600.000');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGRESSION TESTS 22-25: OWNER MUTATION RESTRICTIONS (READ-ONLY SECURITY)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_case_22_owner_cannot_create_supplier_payment(): void
    {
        $purchase = Purchase::create([
            'supplier_id'   => $this->supplier->id,
            'purchase_no'   => 'PO-SEC-01',
            'purchase_date' => now()->toDateString(),
            'status'        => PurchaseStatus::RECEIVED,
            'total_amount'  => '2000000.00',
            'created_by'    => $this->admin->id,
        ]);

        // GET create form must be 403
        $this->actingAs($this->owner)
            ->get(route('admin.purchases.payments.create', $purchase))
            ->assertForbidden();

        // POST store action must be 403
        $this->actingAs($this->owner)
            ->post(route('admin.purchases.payments.store', $purchase), [
                'amount'         => '1000000.00',
                'payment_date'   => now()->toDateString(),
                'payment_method' => 'TRANSFER_BANK',
            ])
            ->assertForbidden();
    }

    public function test_case_23_owner_cannot_edit_supplier_payment(): void
    {
        $order = $this->createCompletedOrder('ORD-MUT-01', now()->toDateString(), 1, 100000);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-MUT-01',
            'invoice_date' => now()->toDateString(),
            'total_amount' => 100000,
            'status'       => InvoiceStatus::UNPAID,
        ]);
        $payment = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 100000,
            'payment_date'   => now()->toDateString(),
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // Owner cannot cancel/mutate payment
        $this->actingAs($this->owner)
            ->post(route('admin.payments.cancel', $payment), ['cancellation_reason' => 'Unauthorized'])
            ->assertForbidden();

        // Owner cannot record payment
        $this->actingAs($this->owner)
            ->post(route('admin.invoices.payments.store', $invoice), [
                'amount'         => 50000,
                'payment_date'   => now()->toDateString(),
                'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            ])
            ->assertForbidden();
    }

    public function test_case_24_owner_cannot_create_business_expense(): void
    {
        $this->actingAs($this->owner)
            ->get(route('admin.expenses.create'))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->post(route('admin.expenses.store'), [
                'expense_type' => ExpenseType::OPERATING->value,
                'amount'       => '500000.00',
                'expense_date' => now()->toDateString(),
                'description'  => 'Expense By Owner Attempt',
            ])
            ->assertForbidden();
    }

    public function test_case_25_owner_cannot_edit_opening_cash(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('admin.opening-cash.edit'));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(route('owner.opening-cash.create')),
            'Owner must be forbidden or redirected away from admin opening cash edit.'
        );

        $this->actingAs($this->owner)
            ->post(route('admin.opening-cash.store'), [
                'opening_date' => now()->toDateString(),
                'amount'       => '200000000.00',
                'notes'        => 'Mutation Attempt',
            ])
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGRESSION TESTS 26-29: ROLE SEPARATION AND ACCESS CONTROL
    // ─────────────────────────────────────────────────────────────────────────

    public function test_case_26_admin_finance_capabilities_remain_intact(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.financial-records.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.expenses.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.opening-cash.edit'))
            ->assertOk();
    }

    public function test_case_27_gudang_cannot_see_owner_financial_summary(): void
    {
        $this->actingAs($this->gudang)
            ->get(route('owner.performance.index'))
            ->assertForbidden();

        $this->actingAs($this->gudang)
            ->get(route('owner.financial-summary.index'))
            ->assertForbidden();
    }

    public function test_case_28_produksi_cannot_see_owner_financial_summary(): void
    {
        $this->actingAs($this->produksi)
            ->get(route('owner.performance.index'))
            ->assertForbidden();

        $this->actingAs($this->produksi)
            ->get(route('owner.financial-summary.index'))
            ->assertForbidden();
    }

    public function test_case_29_customer_cannot_see_internal_finance(): void
    {
        $this->actingAs($this->customerUser)
            ->get(route('owner.performance.index'))
            ->assertForbidden();

        $this->actingAs($this->customerUser)
            ->get(route('owner.financial-summary.index'))
            ->assertForbidden();

        $this->actingAs($this->customerUser)
            ->get(route('admin.financial-records.index'))
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGRESSION TEST 30: CROSS-PERIOD SALES & AUTHORITATIVE PIUTANG SEMANTICS
    // ─────────────────────────────────────────────────────────────────────────

    public function test_case_30_cross_period_and_authoritative_piutang_semantics(): void
    {
        // 1. Cross-Period Scenario:
        // Invoice created in September (2026-09-10), total 10.000.000
        $orderSep = $this->createCompletedOrder('ORD-SEP-CROSS', '2026-09-10', 100, 100000);
        $invSep = Invoice::create([
            'order_id'     => $orderSep->id,
            'invoice_no'   => 'INV-SEP-CROSS',
            'invoice_date' => '2026-09-10',
            'total_amount' => '10000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        // Customer payment received in October (2026-10-05) for September invoice
        Payment::create([
            'invoice_id'     => $invSep->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '10000000.00',
            'payment_date'   => '2026-10-05',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // In October, an expense of 2.500.000 is recorded
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '2500000.00',
            'expense_date' => '2026-10-15',
            'description'  => 'Operasional Workshop Oktober',
            'created_by'   => $this->admin->id,
        ]);

        // In October, a new order is created: 4.000.000, which is UNPAID
        $orderOct = $this->createCompletedOrder('ORD-OCT-UNPAID', '2026-10-12', 40, 100000);
        $invOct = Invoice::create([
            'order_id'     => $orderOct->id,
            'invoice_no'   => 'INV-OCT-UNPAID',
            'invoice_date' => '2026-10-12',
            'total_amount' => '4000000.00',
            'status'       => InvoiceStatus::UNPAID,
        ]);

        // ── SEPTEMBER FILTER ──────────────────────────────────────────────────
        $resSep = $this->service->calculatePeriodFinancials('2026-09-01', '2026-09-30');
        // September sales remains September revenue
        $this->assertEquals('10000000.00', $resSep['total_penjualan']);
        // October payment was not received in September
        $this->assertEquals('0.00', $resSep['pendapatan_diterima']);
        // Customer outstanding for September order is 0.00 because it is fully paid as of current data
        $this->assertEquals('0.00', $resSep['piutang_customer']);

        // ── OCTOBER FILTER ────────────────────────────────────────────────────
        $resOct = $this->service->calculatePeriodFinancials('2026-10-01', '2026-10-31');
        // October sales is strictly October order
        $this->assertEquals('4000000.00', $resOct['total_penjualan']);
        // October payment received from September invoice counts in October cash receipt
        $this->assertEquals('10000000.00', $resOct['pendapatan_diterima']);
        // Customer outstanding for October orders is exactly 4.000.000 (NOT distorted to 0 by subtraction!)
        $this->assertEquals('4000000.00', $resOct['piutang_customer']);
        // Total Pengeluaran = 2.500.000
        $this->assertEquals('2500000.00', $resOct['total_pengeluaran']);
        // ROI October uses October revenue (including unpaid piutang): (4.000.000 / 2.500.000) * 100% = 160.00%
        $this->assertEquals('160.0000', $resOct['roi']);
        $this->assertEquals('160,00%', $resOct['roi_label']);

        // ── PAYMENT STATUS VARIATIONS ON PIUTANG ──────────────────────────────
        // 2. Partial Payment: 10m invoice with 4m confirmed payment -> 6m outstanding
        $orderPart = $this->createCompletedOrder('ORD-OCT-PART', '2026-10-20', 100, 100000);
        $invPart = Invoice::create([
            'order_id'     => $orderPart->id,
            'invoice_no'   => 'INV-OCT-PART',
            'invoice_date' => '2026-10-20',
            'total_amount' => '10000000.00',
            'status'       => InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'invoice_id'     => $invPart->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '4000000.00',
            'payment_date'   => '2026-10-21',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // 3. Pending Payment: must NOT reduce piutang
        Payment::create([
            'invoice_id'     => $invPart->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '3000000.00',
            'payment_date'   => '2026-10-22',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::PENDING,
        ]);

        // 4. Cancelled Payment: must NOT reduce piutang
        Payment::create([
            'invoice_id'     => $invPart->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => '3000000.00',
            'payment_date'   => '2026-10-23',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CANCELLED,
        ]);

        // Single check for Partial order date range (2026-10-20)
        $resPart = $this->service->calculatePeriodFinancials('2026-10-20', '2026-10-20');
        $this->assertEquals('10000000.00', $resPart['total_penjualan']);
        // 10m total - 4m confirmed = 6m outstanding (pending and cancelled ignored)
        $this->assertEquals('6000000.00', $resPart['piutang_customer']);
        // Pendapatan diterima on 2026-10-20 is 0.00 (payment date was 2026-10-21)
        $this->assertEquals('0.00', $resPart['pendapatan_diterima']);

        // Check date 2026-10-21 when payment was confirmed
        $resPartPay = $this->service->calculatePeriodFinancials('2026-10-21', '2026-10-21');
        // No orders created on 2026-10-21
        $this->assertEquals('0.00', $resPartPay['total_penjualan']);
        $this->assertEquals('4000000.00', $resPartPay['pendapatan_diterima']);
        $this->assertEquals('0.00', $resPartPay['piutang_customer']);
    }
}
