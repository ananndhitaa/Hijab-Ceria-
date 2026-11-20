<?php

namespace Tests\Unit\Finance;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\ExpenseType;
use App\Enums\MovementType;
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
use App\Services\Finance\OrderProfitabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProfitabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderProfitabilityService $profitabilityService;
    private User $admin;
    private Material $kain;
    private ProductVariant $variant;
    private Order $order;
    private OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profitabilityService = app(OrderProfitabilityService::class);

        $this->admin = User::factory()->create();

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Premium',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 1000,
            'avg_unit_cost' => 20000,
        ]);

        $cust = Customer::create(['customer_name' => 'PT Profit Prima', 'phone' => '081234567890']);
        $this->order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-PRF01',
            'order_date'   => now()->toDateString(),
            'status'       => 'DEAL',
            'total_qty'    => 30,
            'total_amount' => 3000000,
        ]);

        $product = Product::create(['product_name' => 'Hijab Pashmina']);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Emerald Glow',
            'sku'                => 'HJB-EMR-01',
            'base_selling_price' => 100000,
        ]);

        $this->orderItem = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 100000,
            'subtotal'           => 3000000,
        ]);
    }

    private function createCompletedBatchWithCost(
        OrderItem $item,
        string $batchNo,
        int $targetQty,
        int $actualQty,
        int $goodQty,
        int $rejectQty,
        float $materialQtyUsed,
        float $materialUnitSnapshot,
        array $directCosts = []
    ): ProductionBatch {
        $laborAmt = (float) ($directCosts[CostType::DIRECT_LABOR->value] ?? 0);
        $packAmt  = (float) ($directCosts[CostType::PACKAGING->value] ?? 0);
        $printAmt = (float) ($directCosts[CostType::PRINTING->value] ?? 0);
        $otherAmt = (float) ($directCosts[CostType::OTHER_DIRECT->value] ?? 0);

        $laborSnapshot = $actualQty > 0 ? ($laborAmt / $actualQty) : 0;
        $packSnapshot  = $goodQty > 0 ? ($packAmt / $goodQty) : 0;
        $printSnapshot = $actualQty > 0 ? ($printAmt / $actualQty) : 0;
        $otherSnapshot = $actualQty > 0 ? ($otherAmt / $actualQty) : 0;

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => $batchNo,
            'target_qty'                                    => $targetQty,
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
            'movement_date'       => now()->toDateString(),
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => $actualQty,
            'result_date' => now()->toDateString(),
        ]);

        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => $goodQty,
            'reject_qty'           => $rejectQty,
            'qc_date'              => now()->toDateString(),
        ]);

        return $batch;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 36: REVENUE TESTS
    // ─────────────────────────────────────────────────────────────────────────

    public function test_revenue_uses_qty_ordered_multiplied_by_transaction_unit_selling_price(): void
    {
        $revenue = $this->profitabilityService->calculateRevenue($this->order);

        $this->assertEquals('READY', $revenue['status']);
        $this->assertEquals('3000000.00', $revenue['amount']);
    }

    public function test_changes_in_master_base_selling_price_do_not_alter_historical_revenue(): void
    {
        // Change master catalog price from 100k to 150k
        $this->variant->update(['base_selling_price' => 150000]);

        $revenue = $this->profitabilityService->calculateRevenue($this->order);

        // Historical order revenue MUST remain 3.000.000
        $this->assertEquals('3000000.00', $revenue['amount']);
    }

    public function test_invoice_creation_or_amount_does_not_change_order_revenue(): void
    {
        Invoice::create([
            'order_id'       => $this->order->id,
            'invoice_no'     => 'INV-202609-001',
            'invoice_date'   => now()->toDateString(),
            'total_amount'   => 9999999, // Arbitrary invoice figure
            'status'         => \App\Enums\InvoiceStatus::UNPAID,
        ]);

        $revenue = $this->profitabilityService->calculateRevenue($this->order);

        $this->assertEquals('3000000.00', $revenue['amount']);
    }

    public function test_confirmed_payment_amount_does_not_change_order_revenue(): void
    {
        $invoice = Invoice::create([
            'order_id'       => $this->order->id,
            'invoice_no'     => 'INV-202609-002',
            'invoice_date'   => now()->toDateString(),
            'total_amount'   => 3000000,
            'status'         => \App\Enums\InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => \App\Enums\PaymentType::DP,
            'amount'         => 1000000, // Partial DP payment
            'payment_date'   => now()->toDateString(),
            'payment_method' => \App\Enums\PaymentMethod::TRANSFER_BANK,
            'status'         => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        $revenue = $this->profitabilityService->calculateRevenue($this->order);

        // Revenue is transactional accrual: 3.000.000, NOT 1.000.000 payment
        $this->assertEquals('3000000.00', $revenue['amount']);
    }

    public function test_good_surplus_does_not_alter_order_revenue(): void
    {
        // Produce 35 good pcs on a 30 pcs order
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-SURPLUS',
            35,
            35,
            35,
            0,
            35,
            20000
        );

        $revenue = $this->profitabilityService->calculateRevenue($this->order);

        // Revenue remains 30 pcs * 100k = 3.000.000 (NOT 3.500.000)
        $this->assertEquals('3000000.00', $revenue['amount']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 37 & 38: PROFIT & EXPENSE ATTRIBUTION TESTS
    // ─────────────────────────────────────────────────────────────────────────

    public function test_order_profitability_with_operating_and_non_operating_expenses(): void
    {
        // 1. Setup completed batch: Target 30, Good 30.
        // Material: 30 * 20000 = 600.000. Direct Labor: 30 * 50.000 = 1.500.000.
        // Total HPP = 2.100.000
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-PRF1',
            30,
            30,
            30,
            0,
            30,
            20000,
            [CostType::DIRECT_LABOR->value => '1500000.00']
        );

        // 2. Add Attributed OPERATING Expense: 150.000
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '150000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Biaya kurir sampel',
            'created_by'   => $this->admin->id,
        ]);

        // 3. Add Non-Operating Expenses linked to order (MARKETING, INTEREST, TAX, DEPRECIATION, AMORTIZATION)
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::MARKETING,
            'amount'       => '100000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Iklan campaign Instagram khusus order ini',
            'created_by'   => $this->admin->id,
        ]);
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::INTEREST,
            'amount'       => '20000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Bunga pinjaman modal order',
            'created_by'   => $this->admin->id,
        ]);
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::TAX,
            'amount'       => '30000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Pajak final transaksi',
            'created_by'   => $this->admin->id,
        ]);
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::DEPRECIATION,
            'amount'       => '15000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Depresiasi mesin jahit order',
            'created_by'   => $this->admin->id,
        ]);
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::AMORTIZATION,
            'amount'       => '10000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Amortisasi software desain',
            'created_by'   => $this->admin->id,
        ]);

        // 4. Add Global Operating Expense (order_id = null)
        BusinessExpense::create([
            'order_id'     => null,
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '500000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Listrik kantor pusat bulanan',
            'created_by'   => $this->admin->id,
        ]);

        $result = $this->profitabilityService->calculateOrderProfitability($this->order);

        $this->assertTrue($result['is_ready']);
        $this->assertEquals('READY', $result['status']);
        $this->assertEquals('3000000.00', $result['revenue_amount']);
        $this->assertEquals('2100000.00', $result['hpp_amount']);

        // Gross Profit = 3.000.000 - 2.100.000 = 900.000
        $this->assertEquals('900000.00', $result['gross_profit']);
        // Gross Margin = (900.000 / 3.000.000) * 100 = 30.0000%
        $this->assertEquals('30.0000', $result['gross_margin']);

        // Operating Expense ONLY 150.000 (Marketing, Interest, Tax, Depr, Amort, & Global expenses EXCLUDED)
        $this->assertEquals('150000.00', $result['operating_expense']);

        // Net Profit = 900.000 - 150.000 = 750.000
        $this->assertEquals('750000.00', $result['net_profit']);
        // Net Margin = (750.000 / 3.000.000) * 100 = 25.0000%
        $this->assertEquals('25.0000', $result['net_margin']);
    }

    public function test_profitability_is_not_ready_when_hpp_is_not_ready(): void
    {
        // Batch in PRODUKSI status (HPP unready)
        ProductionBatch::create([
            'order_item_id' => $this->orderItem->id,
            'batch_no'      => 'BAT-202609-PROD',
            'target_qty'    => 30,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '50000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Biaya operasional sampel',
            'created_by'   => $this->admin->id,
        ]);

        $result = $this->profitabilityService->calculateOrderProfitability($this->order);

        $this->assertFalse($result['is_ready']);
        $this->assertEquals('HPP_NOT_READY', $result['status']);
        $this->assertEquals('3000000.00', $result['revenue_amount']);
        $this->assertNull($result['hpp_amount']);
        $this->assertNull($result['gross_profit']);
        $this->assertNull($result['gross_margin']);
        $this->assertEquals('50000.00', $result['operating_expense']);
        $this->assertNull($result['net_profit']);
        $this->assertNull($result['net_margin']);
    }

    public function test_reopening_batch_direct_costing_immediately_invalidates_profitability(): void
    {
        $batch = $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-REOPEN',
            30,
            30,
            30,
            0,
            30,
            20000
        );

        // 1. Initial State: Profit is READY
        $initialProf = $this->profitabilityService->calculateOrderProfitability($this->order);
        $this->assertTrue($initialProf['is_ready']);

        // 2. Batch status becomes active again (e.g. PRODUKSI)
        $batch->update([
            'status' => BatchStatus::PRODUKSI,
        ]);

        // 3. Next read immediately returns HPP_NOT_READY (no stale profit displayed)
        $reopenedProf = $this->profitabilityService->calculateOrderProfitability($this->order);
        $this->assertFalse($reopenedProf['is_ready']);
        $this->assertEquals('HPP_NOT_READY', $reopenedProf['status']);
        $this->assertNull($reopenedProf['gross_profit']);
        $this->assertNull($reopenedProf['net_profit']);

        // 4. Batch finishes back to SELESAI
        $batch->update([
            'status' => BatchStatus::SELESAI,
        ]);

        // 5. Profit is READY again
        $reconfirmedProf = $this->profitabilityService->calculateOrderProfitability($this->order);
        $this->assertTrue($reconfirmedProf['is_ready']);
    }

    public function test_negative_gross_profit_and_negative_net_profit_are_allowed(): void
    {
        // Revenue: 30 * 100k = 3.000.000
        // HPP: Material 600k + Direct 3.600.000 (30 * 120.000) = 4.200.000 (Cost exceeds Revenue)
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-LOSS',
            30,
            30,
            30,
            0,
            30,
            20000,
            [CostType::DIRECT_LABOR->value => '3600000.00']
        );

        // Attributed Operating Expense: 200.000
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '200000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Biaya logistik retur',
            'created_by'   => $this->admin->id,
        ]);

        $result = $this->profitabilityService->calculateOrderProfitability($this->order);

        $this->assertTrue($result['is_ready']);
        // Gross Profit = 3.000.000 - 4.200.000 = -1.200.000
        $this->assertEquals('-1200000.00', $result['gross_profit']);
        // Gross Margin = (-1.200.000 / 3.000.000) * 100 = -40.0000%
        $this->assertEquals('-40.0000', $result['gross_margin']);

        // Net Profit = -1.200.000 - 200.000 = -1.400.000
        $this->assertEquals('-1400000.00', $result['net_profit']);
        // Net Margin = (-1.400.000 / 3.000.000) * 100 = -46.6666%
        $this->assertEquals('-46.6666', $result['net_margin']);
    }

    public function test_zero_revenue_order_returns_null_margins_without_divide_by_zero(): void
    {
        // Sample order where unit selling price is 0 (free sample)
        $this->orderItem->update(['unit_selling_price' => 0, 'subtotal' => 0]);
        $this->order->update(['total_amount' => 0]);

        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-FREE',
            30,
            30,
            30,
            0,
            30,
            20000
        );

        $result = $this->profitabilityService->calculateOrderProfitability($this->order);

        $this->assertTrue($result['is_ready']);
        $this->assertEquals('0.00', $result['revenue_amount']);
        $this->assertEquals('600000.00', $result['hpp_amount']);
        $this->assertEquals('-600000.00', $result['gross_profit']);
        $this->assertNull($result['gross_margin']); // Div by zero avoided
        $this->assertEquals('-600000.00', $result['net_profit']);
        $this->assertNull($result['net_margin']);   // Div by zero avoided
    }

    public function test_corrupt_negative_order_item_triggers_data_integrity_error(): void
    {
        $this->orderItem->update(['unit_selling_price' => -50000]);

        $result = $this->profitabilityService->calculateOrderProfitability($this->order);

        $this->assertFalse($result['is_ready']);
        $this->assertEquals('DATA_INTEGRITY_ERROR', $result['status']);
        $this->assertNull($result['revenue_amount']);
        $this->assertNull($result['gross_profit']);
        $this->assertNull($result['net_profit']);
        $this->assertStringContainsString('negatif', $result['error']);
    }

    public function test_cancelled_order_returns_order_cancelled_status(): void
    {
        // Cancel the order in database
        \Illuminate\Support\Facades\DB::table('orders')->where('id', $this->order->id)->update(['status' => 'CANCELLED']);
        $this->order->refresh();

        $result = $this->profitabilityService->calculateOrderProfitability($this->order);

        $this->assertFalse($result['is_ready']);
        $this->assertEquals('ORDER_CANCELLED', $result['status']);
        $this->assertNull($result['revenue_amount']);
        $this->assertNull($result['hpp_amount']);
        $this->assertNull($result['gross_profit']);
        $this->assertNull($result['gross_margin']);
        $this->assertEquals('0.00', $result['operating_expense']);
        $this->assertNull($result['net_profit']);
        $this->assertNull($result['net_margin']);
        $this->assertStringContainsString('dibatalkan', $result['error']);
    }

    public function test_operating_expense_live_update_reflects_immediately_in_net_profit(): void
    {
        // Setup ready batch: Material 600k + Labor 1.500k = 2.100k HPP
        $this->createCompletedBatchWithCost(
            $this->orderItem,
            'BAT-202609-OPEX-LIVE',
            30,
            30,
            30,
            0,
            30,
            20000,
            [CostType::DIRECT_LABOR->value => '1500000.00']
        );

        // Initial Operating Expense: 100.000
        $expense = BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '100000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Kurir batch pertama',
            'created_by'   => $this->admin->id,
        ]);

        // Initial Net Profit: GP 900.000 - OPEX 100.000 = 800.000
        $initialProf = $this->profitabilityService->calculateOrderProfitability($this->order);
        $this->assertTrue($initialProf['is_ready']);
        $this->assertEquals('100000.00', $initialProf['operating_expense']);
        $this->assertEquals('800000.00', $initialProf['net_profit']);

        // Admin adds second operating expense: 50.000
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '50000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Kemasan khusus',
            'created_by'   => $this->admin->id,
        ]);

        // Next evaluation immediately reflects new total OPEX 150.000 and NP 750.000
        $updatedProf = $this->profitabilityService->calculateOrderProfitability($this->order);
        $this->assertTrue($updatedProf['is_ready']);
        $this->assertEquals('150000.00', $updatedProf['operating_expense']);
        $this->assertEquals('750000.00', $updatedProf['net_profit']);
    }
}
