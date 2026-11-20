<?php

namespace Tests\Unit\Finance;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\ExpenseType;
use App\Enums\MovementType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Finance\BatchCost;
use App\Models\Finance\BusinessExpense;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodFinancialServiceTest extends TestCase
{
    use RefreshDatabase;

    private PeriodFinancialService $service;
    private User $admin;
    private Material $kain;
    private ProductVariant $variant;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PeriodFinancialService::class);
        $this->admin = User::factory()->create();

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Sutra Balotelli',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 30000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 10000,
            'avg_unit_cost' => 30000,
        ]);

        $this->customer = Customer::create(['customer_name' => 'Nayara Hijab Store', 'phone' => '081234567890']);

        $product = Product::create(['product_name' => 'Khimar Elegan']);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Maroon Glow',
            'sku'                => 'KHM-MRN-01',
            'base_selling_price' => 100000,
        ]);
    }

    private function createOrderWithCompletedBatch(
        string $orderNo,
        string $orderDate,
        int $qty,
        float $unitPrice,
        float $materialQtyUsed,
        float $materialUnitSnapshot,
        array $directCosts = []
    ): Order {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => $orderNo,
            'order_date'   => $orderDate,
            'status'       => 'DEAL',
            'total_qty'    => $qty,
            'total_amount' => $qty * $unitPrice,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $qty,
            'unit_selling_price' => $unitPrice,
            'subtotal'           => $qty * $unitPrice,
        ]);

        $laborAmt = (float) ($directCosts[CostType::DIRECT_LABOR->value] ?? 0);
        $packAmt  = (float) ($directCosts[CostType::PACKAGING->value] ?? 0);
        $printAmt = (float) ($directCosts[CostType::PRINTING->value] ?? 0);
        $otherAmt = (float) ($directCosts[CostType::OTHER_DIRECT->value] ?? 0);

        $laborSnapshot = $qty > 0 ? ($laborAmt / $qty) : 0;
        $packSnapshot  = $qty > 0 ? ($packAmt / $qty) : 0;
        $printSnapshot = $qty > 0 ? ($printAmt / $qty) : 0;
        $otherSnapshot = $qty > 0 ? ($otherAmt / $qty) : 0;

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => "BAT-{$orderNo}",
            'target_qty'                                    => $qty,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => $printAmt > 0,
            'printing_cost_per_unit_snapshot'               => number_format($printSnapshot, 2, '.', ''),
            'direct_labor_cost_per_unit_snapshot'           => number_format($laborSnapshot, 2, '.', ''),
            'packaging_process_cost_per_good_unit_snapshot' => number_format($packSnapshot, 2, '.', ''),
            'other_direct_cost_per_unit_snapshot'           => number_format($otherSnapshot, 2, '.', ''),
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => $materialQtyUsed,
            'allocated_qty' => $materialQtyUsed,
            'used_qty'      => $materialQtyUsed,
        ]);

        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => $materialQtyUsed,
            'unit_cost_snapshot'  => number_format($materialUnitSnapshot, 4, '.', ''),
            'total_cost_snapshot' => number_format($materialQtyUsed * $materialUnitSnapshot, 2, '.', ''),
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
    // SECTION 20 & 46: CORE PERIOD FINANCIAL METRICS (EXACT SPECIFICATION)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_period_financials_matches_exact_specification_and_reconciliation(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // 1. Order: Revenue = 100 pcs * 100.000 = 10.000.000
        // HPP: Material (100 * 30.000 = 3.000.000) + Direct Labor (3.000.000) = 6.000.000
        $this->createOrderWithCompletedBatch(
            'ORD-202609-EXACT',
            '2026-09-15',
            100,
            100000,
            100,
            30000,
            [CostType::DIRECT_LABOR->value => '3000000.00']
        );

        // 2. Business Expenses:
        // Marketing: 500.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::MARKETING,
            'amount'       => '500000.00',
            'expense_date' => '2026-09-10',
            'description'  => 'Iklan Meta Ads September',
            'created_by'   => $this->admin->id,
        ]);
        // Operating: 700.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '700000.00',
            'expense_date' => '2026-09-12',
            'description'  => 'Operasional Workshop',
            'created_by'   => $this->admin->id,
        ]);
        // Interest: 100.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::INTEREST,
            'amount'       => '100000.00',
            'expense_date' => '2026-09-20',
            'description'  => 'Bunga Bank',
            'created_by'   => $this->admin->id,
        ]);
        // Tax: 200.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::TAX,
            'amount'       => '200000.00',
            'expense_date' => '2026-09-25',
            'description'  => 'Pajak PPh Final',
            'created_by'   => $this->admin->id,
        ]);
        // Depreciation: 150.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::DEPRECIATION,
            'amount'       => '150000.00',
            'expense_date' => '2026-09-28',
            'description'  => 'Depresiasi Mesin Jahit',
            'created_by'   => $this->admin->id,
        ]);
        // Amortization: 50.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::AMORTIZATION,
            'amount'       => '50000.00',
            'expense_date' => '2026-09-29',
            'description'  => 'Amortisasi Software',
            'created_by'   => $this->admin->id,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        $this->assertTrue($res['is_ready']);
        $this->assertEquals('READY', $res['status']);

        // Revenue = 10.000.000
        $this->assertEquals('10000000.00', $res['revenue']);
        // HPP = 6.000.000
        $this->assertEquals('6000000.00', $res['hpp']);

        // Gross Profit = 10.000.000 - 6.000.000 = 4.000.000
        $this->assertEquals('4000000.00', $res['gross_profit']);
        // Gross Margin = (4.000.000 / 10.000.000) * 100 = 40.0000%
        $this->assertEquals('40.0000', $res['gross_margin']);

        // Expenses breakdown
        $this->assertEquals('500000.00', $res['expenses']['marketing']);
        $this->assertEquals('700000.00', $res['expenses']['operating']);
        $this->assertEquals('100000.00', $res['expenses']['interest']);
        $this->assertEquals('200000.00', $res['expenses']['tax']);
        $this->assertEquals('150000.00', $res['expenses']['depreciation']);
        $this->assertEquals('50000.00', $res['expenses']['amortization']);
        // Total Expenses = 1.700.000
        $this->assertEquals('1700000.00', $res['expenses']['total']);

        // Net Profit = 4.000.000 - 1.700.000 = 2.300.000
        $this->assertEquals('2300000.00', $res['net_profit']);
        // Net Margin = (2.300.000 / 10.000.000) * 100 = 23.0000%
        $this->assertEquals('23.0000', $res['net_margin']);

        // Total Cost = 6.000.000 + 1.700.000 = 7.700.000
        $this->assertEquals('7700000.00', $res['total_cost']);

        // Standardized ROI = (Total Revenue / Total Pengeluaran) * 100
        // Total Revenue = 10.000.000, Total Pengeluaran = 1.700.000 -> 10.000.000 / 1.700.000 * 100 = 588.2352%
        $this->assertEquals('588.2352', $res['roi']);

        // With confirmed payment recorded, Revenue remains 10.000.000 -> ROI remains 588.2352%
        $inv = Invoice::create([
            'order_id'     => Order::where('order_no', 'ORD-202609-EXACT')->first()->id,
            'invoice_no'   => 'INV-202609-EXACT',
            'invoice_date' => '2026-09-15',
            'total_amount' => 10000000,
            'status'       => \App\Enums\InvoiceStatus::UNPAID,
        ]);
        Payment::create([
            'invoice_id'     => $inv->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '2550000.00',
            'payment_date'   => '2026-09-16',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        $resWithPayment = $this->service->calculatePeriodFinancials($startDate, $endDate);
        $this->assertEquals('2550000.00', $resWithPayment['payment_received']);
        $this->assertEquals('588.2352', $resWithPayment['roi']);

        // MER = 10.000.000 / 500.000 = 20.0000
        $this->assertEquals('20.0000', $res['mer']);

        // EBITDA = 10.000.000 - 6.000.000 - 500.000 - 700.000 = 2.800.000
        $this->assertEquals('2800000.00', $res['ebitda']);

        // EBITDA Reconciliation test: Net Profit + Interest + Tax + Depreciation + Amortization
        $reconciledEbitda = bcadd(
            bcadd(
                bcadd(
                    bcadd($res['net_profit'], $res['expenses']['interest'], 2),
                    $res['expenses']['tax'],
                    2
                ),
                $res['expenses']['depreciation'],
                2
            ),
            $res['expenses']['amortization'],
            2
        );
        $this->assertEquals('2800000.00', $reconciledEbitda);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 47: PERIOD READINESS WITH UNREADY ORDERS
    // ─────────────────────────────────────────────────────────────────────────

    public function test_period_profit_is_not_ready_when_any_order_in_period_has_unready_hpp(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // Order 1: Completed & Ready (Revenue 5m, HPP 3m)
        $this->createOrderWithCompletedBatch(
            'ORD-202609-RDY',
            '2026-09-05',
            50,
            100000,
            50,
            30000,
            [CostType::DIRECT_LABOR->value => '1500000.00']
        );

        // Order 2: In PRODUKSI status (Revenue 5m, HPP UNREADY)
        $order2 = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-202609-UNRDY',
            'order_date'   => '2026-09-10',
            'status'       => 'PRODUKSI',
            'total_qty'    => 50,
            'total_amount' => 5000000,
        ]);
        $item2 = OrderItem::create([
            'order_id'           => $order2->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 100000,
            'subtotal'           => 5000000,
        ]);
        $batch2 = ProductionBatch::create([
            'order_item_id'                                 => $item2->id,
            'batch_no'                                      => 'BAT-ORD-202609-UNRDY',
            'target_qty'                                    => 50,
            'status'                                        => BatchStatus::PRODUKSI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '30000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        // Total Revenue includes both orders = 10.000.000
        $this->assertEquals('10000000.00', $res['revenue']);

        // Readiness flags
        $this->assertFalse($res['is_ready']);
        $this->assertEquals('NOT_READY', $res['status']);
        $this->assertEquals(1, $res['ready_orders_count']);
        $this->assertEquals(1, $res['unready_orders_count']);

        // HPP available diagnostic
        $this->assertEquals('3000000.00', $res['hpp_final_available']);

        // Profit metrics MUST be NULL (not fake 0)
        $this->assertNull($res['hpp']);
        $this->assertNull($res['gross_profit']);
        $this->assertNull($res['gross_margin']);
        $this->assertNull($res['net_profit']);
        $this->assertNull($res['net_margin']);
        $this->assertNull($res['total_cost']);
        $this->assertNull($res['roi']);
        $this->assertNull($res['ebitda']);

        // Now complete batch 2
        $batch2->update([
            'status'                     => BatchStatus::SELESAI,
            'material_usage_recorded_at' => now(),
        ]);
        $bm2 = BatchMaterial::create([
            'batch_id'      => $batch2->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 50,
            'allocated_qty' => 50,
            'used_qty'      => 50,
        ]);
        StockMovement::create([
            'material_id'         => $this->kain->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 50,
            'unit_cost_snapshot'  => '30000.0000',
            'total_cost_snapshot' => '1500000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm2->id,
            'movement_date'       => '2026-09-10',
        ]);
        $pRes2 = ProductionResult::create([
            'batch_id'    => $batch2->id,
            'actual_qty'  => 50,
            'result_date' => '2026-09-10',
        ]);
        QcResult::create([
            'production_result_id' => $pRes2->id,
            'good_qty'             => 50,
            'reject_qty'           => 0,
            'qc_date'              => '2026-09-10',
        ]);

        // Re-calculate: now all are ready
        $resAfter = $this->service->calculatePeriodFinancials($startDate, $endDate);
        $this->assertTrue($resAfter['is_ready']);
        $this->assertEquals('READY', $resAfter['status']);
        $this->assertEquals('6000000.00', $resAfter['hpp']); // 3m + 3m
        $this->assertEquals('4000000.00', $resAfter['gross_profit']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 49 & 50: MER & ROI EDGE CASES
    // ─────────────────────────────────────────────────────────────────────────

    public function test_mer_calculation_edge_cases(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // 1. Marketing Spend = 0 -> MER is NULL
        $resNoMkt = $this->service->calculatePeriodFinancials($startDate, $endDate);
        $this->assertNull($resNoMkt['mer']);

        // 2. Revenue = 0, Marketing Spend > 0 -> MER is 0.0000
        BusinessExpense::create([
            'expense_type' => ExpenseType::MARKETING,
            'amount'       => '200000.00',
            'expense_date' => '2026-09-05',
            'description'  => 'Iklan Tik-Tok',
            'created_by'   => $this->admin->id,
        ]);
        $resZeroRev = $this->service->calculatePeriodFinancials($startDate, $endDate);
        $this->assertEquals('0.0000', $resZeroRev['mer']);
    }

    public function test_roi_calculation_with_supervisor_definition_and_zero_expenditure(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // 1. Zero Expenditure Period (No expenses, no supplier payments) -> ROI is NULL
        $resZeroCost = $this->service->calculatePeriodFinancials($startDate, $endDate);
        $this->assertNull($resZeroCost['roi']);
        $this->assertEquals('Belum dapat dihitung', $resZeroCost['roi_label']);
        $this->assertEquals('Belum ada pengeluaran pada periode ini.', $resZeroCost['roi_helper']);

        // 2. Positive Expenditure & Payment:
        // Order Revenue 1.000.000, Expense 500.000, Payment Received 750.000
        // Total Pengeluaran = 500.000
        // ROI = (750.000 / 500.000) * 100 = 150.0000%
        $order = $this->createOrderWithCompletedBatch(
            'ORD-202609-LOSS',
            '2026-09-05',
            10,
            100000,
            10,
            30000,
            [CostType::DIRECT_LABOR->value => '1200000.00']
        );
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-202609-LOSS',
            'invoice_date' => '2026-09-05',
            'total_amount' => 1000000,
            'status'       => \App\Enums\InvoiceStatus::UNPAID,
        ]);
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => '750000.00',
            'payment_date'   => '2026-09-06',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '500000.00',
            'expense_date' => '2026-09-05',
            'description'  => 'Biaya Gudang',
            'created_by'   => $this->admin->id,
        ]);

        $resLoss = $this->service->calculatePeriodFinancials($startDate, $endDate);
        $this->assertTrue($resLoss['is_ready']);
        $this->assertEquals('-1000000.00', $resLoss['net_profit']); // Accounting net profit remains negative
        $this->assertEquals('2000000.00', $resLoss['total_cost']); // Accounting total cost = HPP 1.5m + Exp 500k
        $this->assertEquals('500000.00', $resLoss['total_pengeluaran']); // Actual cash outflow = Exp 500k
        $this->assertEquals('750000.00', $resLoss['payment_received']);
        $this->assertEquals('200.0000', $resLoss['roi']); // Standardized ROI = 1.000.000 / 500.000 * 100 = 200.0000%
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 51 & 52: DATE BOUNDARIES & CASH COLLECTION
    // ─────────────────────────────────────────────────────────────────────────

    public function test_date_boundaries_are_strictly_inclusive(): void
    {
        $startDate = '2026-09-10';
        $endDate = '2026-09-20';

        // Inside boundary: On start date 2026-09-10
        $this->createOrderWithCompletedBatch('ORD-IN-1', '2026-09-10', 10, 100000, 10, 30000);
        // Inside boundary: On end date 2026-09-20
        $this->createOrderWithCompletedBatch('ORD-IN-2', '2026-09-20', 10, 100000, 10, 30000);
        // Outside boundary: Before start date
        $this->createOrderWithCompletedBatch('ORD-OUT-BEFORE', '2026-09-09', 10, 100000, 10, 30000);
        // Outside boundary: After end date
        $this->createOrderWithCompletedBatch('ORD-OUT-AFTER', '2026-09-21', 10, 100000, 10, 30000);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        // Only ORD-IN-1 (1m) and ORD-IN-2 (1m) included = 2.000.000
        $this->assertEquals('2000000.00', $res['revenue']);
        $this->assertEquals(2, count($res['valid_orders']));
    }

    public function test_payment_received_counts_confirmed_only_and_does_not_mutate_profit(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        $order = $this->createOrderWithCompletedBatch('ORD-PAY', '2026-09-05', 10, 100000, 10, 30000);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-PAY-01',
            'invoice_date' => '2026-09-05',
            'total_amount' => 1000000,
            'status'       => \App\Enums\InvoiceStatus::UNPAID,
        ]);

        // Confirmed payment: 600.000
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 600000,
            'payment_date'   => '2026-09-06',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // Pending payment (excluded from cash collection): 400.000
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 400000,
            'payment_date'   => '2026-09-07',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::PENDING,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        // Cash collection is 600.000 (confirmed only)
        $this->assertEquals('600000.00', $res['payment_received']);
        // Revenue is 1.000.000 (not mutated by payment amount)
        $this->assertEquals('1000000.00', $res['revenue']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 53: NO DOUBLE COUNTING
    // ─────────────────────────────────────────────────────────────────────────

    public function test_direct_costs_inside_hpp_are_not_double_counted_in_business_expenses(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // Batch direct labor: 300.000 (Part of HPP)
        $this->createOrderWithCompletedBatch(
            'ORD-NO-DOUBLE',
            '2026-09-05',
            10,
            100000,
            10,
            30000,
            [CostType::DIRECT_LABOR->value => '300000.00']
        );

        // Operating Business Expense: 100.000 (Part of Period Expenses)
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '100000.00',
            'expense_date' => '2026-09-05',
            'description'  => 'Biaya ATK',
            'created_by'   => $this->admin->id,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        // HPP = Material 300k + Direct Labor 300k = 600.000
        $this->assertEquals('600000.00', $res['hpp']);
        // Period expenses = 100.000 (Operating only, no duplicated direct labor)
        $this->assertEquals('100000.00', $res['expenses']['operating']);
        $this->assertEquals('100000.00', $res['expenses']['total']);
        // Total Cost = 600k + 100k = 700.000
        $this->assertEquals('700000.00', $res['total_cost']);
    }

    public function test_hpp_final_available_diagnostic_is_isolated_and_never_feeds_profit_metrics_when_unready(): void
    {
        $startDate = '2026-09-01';
        $endDate = '2026-09-30';

        // Order 1: Completed & Ready (Revenue 10m, HPP 6m)
        $this->createOrderWithCompletedBatch(
            'ORD-READY-1',
            '2026-09-05',
            100,
            100000,
            100,
            30000,
            [CostType::DIRECT_LABOR->value => '3000000.00']
        );

        // Order 2: In PRODUKSI (Revenue 5m, HPP NOT READY)
        $order2 = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-UNREADY-2',
            'order_date'   => '2026-09-10',
            'status'       => 'PRODUKSI',
            'total_qty'    => 50,
            'total_amount' => 5000000,
        ]);
        $item2 = OrderItem::create([
            'order_id'           => $order2->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 100000,
            'subtotal'           => 5000000,
        ]);
        ProductionBatch::create([
            'order_item_id' => $item2->id,
            'batch_no'      => 'BAT-ORD-UNREADY-2',
            'target_qty'    => 50,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $res = $this->service->calculatePeriodFinancials($startDate, $endDate);

        // Revenue sums both orders (15m)
        $this->assertEquals('15000000.00', $res['revenue']);

        // Diagnostic value is populated with ready order's HPP (6m)
        $this->assertEquals('6000000.00', $res['hpp_final_available']);

        // Period HPP and all profit/margin/efficiency metrics MUST be NULL (strictly isolated)
        $this->assertNull($res['hpp']);
        $this->assertNull($res['gross_profit']);
        $this->assertNull($res['gross_margin']);
        $this->assertNull($res['net_profit']);
        $this->assertNull($res['net_margin']);
        $this->assertNull($res['total_cost']);
        $this->assertNull($res['roi']);
        $this->assertNull($res['ebitda']);
    }
}
