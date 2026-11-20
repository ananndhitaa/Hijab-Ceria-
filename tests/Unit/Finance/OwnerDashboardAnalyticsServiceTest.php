<?php

namespace Tests\Unit\Finance;

use App\Enums\BatchStatus;
use App\Enums\ExpenseType;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
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
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Finance\OwnerDashboardAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OwnerDashboardAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private OwnerDashboardAnalyticsService $service;
    private Customer $customerA;
    private Customer $customerB;
    private Product $product;
    private ProductVariant $variantA;
    private ProductVariant $variantB;
    private Material $kain;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OwnerDashboardAnalyticsService::class);
        $this->user = User::factory()->create();

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->kain = Material::create([
            'material_name' => 'Kain Voal Super',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 20000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 10000,
            'avg_unit_cost' => 20000,
        ]);

        $this->customerA = Customer::create([
            'customer_name' => "Toko 'Barokah' Hijab",
            'phone'         => '08111222333',
        ]);
        $this->customerB = Customer::create([
            'customer_name' => 'Boutique & Fashion "Elegance"',
            'phone'         => '08222333444',
        ]);

        $this->product = Product::create(['product_name' => 'Hijab Segiempat Premium']);
        $this->variantA = ProductVariant::create([
            'product_id'                 => $this->product->id,
            'variant_name'               => "Dusty Pink (O'hara)",
            'sku'                        => 'HJB-DST',
            'base_selling_price'         => 50000,
            'requires_printing'          => false,
            'direct_labor_cost_per_unit' => 5000,
        ]);
        $this->variantB = ProductVariant::create([
            'product_id'                 => $this->product->id,
            'variant_name'               => 'Navy Blue',
            'sku'                        => 'HJB-NVY',
            'base_selling_price'         => 60000,
            'requires_printing'          => false,
            'direct_labor_cost_per_unit' => 5000,
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
        float $laborPerUnit = 0,
        float $packPerGood = 0,
        float $printPerUnit = 0,
        float $otherPerUnit = 0
    ): ProductionBatch {
        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => $batchNo,
            'target_qty'                                    => $targetQty,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => $printPerUnit > 0,
            'printing_cost_per_unit_snapshot'               => number_format($printPerUnit, 2, '.', ''),
            'direct_labor_cost_per_unit_snapshot'           => number_format($laborPerUnit, 2, '.', ''),
            'packaging_process_cost_per_good_unit_snapshot' => number_format($packPerGood, 2, '.', ''),
            'other_direct_cost_per_unit_snapshot'           => number_format($otherPerUnit, 2, '.', ''),
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

    public function test_revenue_trend_uses_transaction_snapshot_price_and_ignores_master_price_changes(): void
    {
        $date = '2026-09-02';
        $order = Order::create([
            'customer_id'  => $this->customerA->id,
            'order_no'     => 'ORD-TEST-001',
            'order_date'   => $date,
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
        ]);

        // Change master price later
        $this->variantA->update(['base_selling_price' => 85000]);

        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 6);
        $trend = collect($analytics['revenue_trend'])->firstWhere('period', '2026-09');

        $this->assertNotNull($trend);
        $this->assertSame('500000.00', $trend['revenue']);
        $this->assertSame(500000.0, $trend['revenue_numeric']);
        $this->assertSame('Rp 500.000', $trend['revenue_formatted']);
        $this->assertTrue($trend['has_orders']);
    }

    public function test_cancelled_orders_are_excluded_from_all_chart_datasets(): void
    {
        $date = '2026-09-02';

        // Valid order
        $validOrder = Order::create([
            'customer_id'  => $this->customerA->id,
            'order_no'     => 'ORD-VALID-01',
            'order_date'   => $date,
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 5,
            'total_amount' => 250000,
        ]);
        OrderItem::create([
            'order_id'           => $validOrder->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 50000,
        ]);

        // Cancelled order with large amount
        $cancelledOrder = Order::create([
            'customer_id'  => $this->customerB->id,
            'order_no'     => 'ORD-CANCELLED-01',
            'order_date'   => $date,
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 100,
            'total_amount' => 6000000,
        ]);
        OrderItem::create([
            'order_id'           => $cancelledOrder->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 60000,
        ]);
        DB::table('orders')->where('id', $cancelledOrder->id)->update(['status' => 'CANCELLED']);

        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 6);

        // 1. Revenue trend
        $sepTrend = collect($analytics['revenue_trend'])->firstWhere('period', '2026-09');
        $this->assertSame('250000.00', $sepTrend['revenue']);

        // 2. Profit comparison
        $sepProfit = collect($analytics['profit_comparison'])->firstWhere('period', '2026-09');
        $this->assertSame('250000.00', $sepProfit['revenue']);

        // 3. Top products: only variantA is present
        $this->assertCount(1, $analytics['top_products']);
        $this->assertSame($this->variantA->id, $analytics['top_products'][0]['variant_id']);

        // 4. Top customers: only customerA is present
        $this->assertCount(1, $analytics['top_customers']);
        $this->assertSame($this->customerA->id, $analytics['top_customers'][0]['customer_id']);
    }

    public function test_missing_months_return_zero_revenue_bucket_and_not_represented_as_final_hpp_zero(): void
    {
        // Only create an order in September 2026
        $order = Order::create([
            'customer_id'  => $this->customerA->id,
            'order_no'     => 'ORD-SEP-01',
            'order_date'   => '2026-09-02',
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 2,
            'total_amount' => 100000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 2,
            'unit_selling_price' => 50000,
        ]);

        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 6);

        // For empty months (e.g. 2026-04 to 2026-08)
        $emptyMonths = ['2026-04', '2026-05', '2026-06', '2026-07', '2026-08'];
        foreach ($emptyMonths as $period) {
            $trend = collect($analytics['revenue_trend'])->firstWhere('period', $period);
            $this->assertNotNull($trend, "Missing trend period: {$period}");
            $this->assertSame('0.00', $trend['revenue']);
            $this->assertSame(0, $trend['revenue_numeric']);
            $this->assertSame('Rp 0', $trend['revenue_formatted']);
            $this->assertFalse($trend['has_orders']);

            $comp = collect($analytics['profit_comparison'])->firstWhere('period', $period);
            $this->assertNotNull($comp, "Missing comparison period: {$period}");
            $this->assertSame('0.00', $comp['revenue']);
            $this->assertNull($comp['hpp']);
            $this->assertNull($comp['gross_profit']);
            $this->assertFalse($comp['has_orders']);
            $this->assertFalse($comp['is_final']);
            $this->assertSame('Belum ada transaksi', $comp['status_label']);
        }
    }

    public function test_monthly_hpp_readiness_marks_complete_month_as_final_and_incomplete_month_as_not_final(): void
    {
        // Month A: August 2026 with READY HPP
        $orderAug = Order::create([
            'customer_id'  => $this->customerA->id,
            'order_no'     => 'ORD-AUG-01',
            'order_date'   => '2026-08-15',
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        $itemAug = OrderItem::create([
            'order_id'           => $orderAug->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
        ]);
        // Material 10 * 20,000 = 200,000 + Labor 10 * 5,000 = 50,000 => HPP 250,000
        $this->createCompletedBatchWithCost(
            $itemAug,
            'BAT-AUG-01',
            10,
            10,
            10,
            0,
            10,
            20000,
            5000
        );

        // Month B: September 2026 with UNREADY HPP (batch still in PRODUKSI)
        $orderSep = Order::create([
            'customer_id'  => $this->customerB->id,
            'order_no'     => 'ORD-SEP-02',
            'order_date'   => '2026-09-03',
            'status'       => OrderStatus::PRODUKSI,
            'total_qty'    => 10,
            'total_amount' => 600000,
        ]);
        $itemSep = OrderItem::create([
            'order_id'           => $orderSep->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 60000,
        ]);
        ProductionBatch::create([
            'order_item_id' => $itemSep->id,
            'batch_no'      => 'BAT-SEP-02',
            'target_qty'    => 10,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 6);
        $augComp = collect($analytics['profit_comparison'])->firstWhere('period', '2026-08');
        $sepComp = collect($analytics['profit_comparison'])->firstWhere('period', '2026-09');

        // August: All ready -> is_final = true
        $this->assertNotNull($augComp);
        $this->assertTrue($augComp['has_orders']);
        $this->assertTrue($augComp['is_final']);
        $this->assertSame('500000.00', $augComp['revenue']);
        $this->assertSame('250000.00', $augComp['hpp']);
        $this->assertSame('250000.00', $augComp['gross_profit']);
        $this->assertSame('Rp 250.000', $augComp['hpp_formatted']);
        $this->assertSame('Rp 250.000', $augComp['gross_profit_formatted']);
        $this->assertSame('Final', $augComp['status_label']);

        // September: Unready -> is_final = false, HPP=null, GP=null, Revenue visible
        $this->assertNotNull($sepComp);
        $this->assertTrue($sepComp['has_orders']);
        $this->assertFalse($sepComp['is_final']);
        $this->assertSame('600000.00', $sepComp['revenue']);
        $this->assertNull($sepComp['hpp']);
        $this->assertNull($sepComp['gross_profit']);
        $this->assertNull($sepComp['hpp_formatted']);
        $this->assertNull($sepComp['gross_profit_formatted']);
        $this->assertSame('HPP belum final', $sepComp['status_label']);
    }

    public function test_expense_composition_groups_exact_expense_types_with_indonesian_labels_and_exact_total(): void
    {
        BusinessExpense::create([
            'expense_date' => '2026-09-01',
            'expense_type' => ExpenseType::MARKETING,
            'description'  => 'Instagram Ads',
            'amount'       => 1500000,
            'created_by'   => $this->user->id,
        ]);
        BusinessExpense::create([
            'expense_date' => '2026-09-02',
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Listrik & Utilitas',
            'amount'       => 2000000,
            'created_by'   => $this->user->id,
        ]);
        BusinessExpense::create([
            'expense_date' => '2026-09-03',
            'expense_type' => ExpenseType::TAX,
            'description'  => 'PPh Final Usaha',
            'amount'       => 500000,
            'created_by'   => $this->user->id,
        ]);

        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 6);
        $expenseData = $analytics['expense_composition'];

        $this->assertTrue($expenseData['has_data']);
        $this->assertSame('4000000.00', $expenseData['total']);
        $this->assertSame('Rp 4.000.000', $expenseData['total_formatted']);

        $categories = collect($expenseData['categories']);
        $this->assertSame('1500000.00', $categories->firstWhere('type', ExpenseType::MARKETING->value)['amount']);
        $this->assertSame('Marketing', $categories->firstWhere('type', ExpenseType::MARKETING->value)['label']);

        $this->assertSame('2000000.00', $categories->firstWhere('type', ExpenseType::OPERATING->value)['amount']);
        $this->assertSame('Operasional', $categories->firstWhere('type', ExpenseType::OPERATING->value)['label']);

        $this->assertSame('500000.00', $categories->firstWhere('type', ExpenseType::TAX->value)['amount']);
        $this->assertSame('Pajak', $categories->firstWhere('type', ExpenseType::TAX->value)['label']);

        $this->assertSame('0.00', $categories->firstWhere('type', ExpenseType::INTEREST->value)['amount']);
        $this->assertSame('Bunga', $categories->firstWhere('type', ExpenseType::INTEREST->value)['label']);

        $this->assertSame('0.00', $categories->firstWhere('type', ExpenseType::DEPRECIATION->value)['amount']);
        $this->assertSame('Depresiasi', $categories->firstWhere('type', ExpenseType::DEPRECIATION->value)['label']);

        $this->assertSame('0.00', $categories->firstWhere('type', ExpenseType::AMORTIZATION->value)['amount']);
        $this->assertSame('Amortisasi', $categories->firstWhere('type', ExpenseType::AMORTIZATION->value)['label']);

        // Check exact sum of all 6 items equals total
        $sum = '0.00';
        foreach ($categories as $cat) {
            $sum = bcadd($sum, $cat['amount'], 2);
        }
        $this->assertSame($expenseData['total'], $sum);
    }

    public function test_top_products_ranks_by_revenue_and_includes_qty_ordered(): void
    {
        // Variant A: sold 10 units at 100,000 = 1,000,000 revenue
        $order1 = Order::create([
            'customer_id'  => $this->customerA->id,
            'order_no'     => 'ORD-TP-01',
            'order_date'   => '2026-09-05',
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 10,
            'total_amount' => 1000000,
        ]);
        OrderItem::create([
            'order_id'           => $order1->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 100000,
        ]);

        // Variant B: sold 100 units at 5,000 = 500,000 revenue (higher qty, lower revenue)
        $order2 = Order::create([
            'customer_id'  => $this->customerB->id,
            'order_no'     => 'ORD-TP-02',
            'order_date'   => '2026-09-06',
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 100,
            'total_amount' => 500000,
        ]);
        OrderItem::create([
            'order_id'           => $order2->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 5000,
        ]);

        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 6);
        $top = $analytics['top_products'];

        $this->assertCount(2, $top);

        // Rank 1: Variant A (highest revenue)
        $this->assertSame($this->variantA->id, $top[0]['variant_id']);
        $this->assertSame('1000000.00', $top[0]['revenue']);
        $this->assertSame('Rp 1.000.000', $top[0]['revenue_formatted']);
        $this->assertSame(10.0, $top[0]['qty']);
        $this->assertStringContainsString("Dusty Pink (O'hara)", $top[0]['label']);

        // Rank 2: Variant B
        $this->assertSame($this->variantB->id, $top[1]['variant_id']);
        $this->assertSame('500000.00', $top[1]['revenue']);
        $this->assertSame(100.0, $top[1]['qty']);
    }

    public function test_top_customers_ranks_by_revenue_and_includes_order_count(): void
    {
        // Customer A: 3 orders totaling 300,000
        for ($i = 1; $i <= 3; $i++) {
            $order = Order::create([
                'customer_id'  => $this->customerA->id,
                'order_no'     => "ORD-CUSTA-0{$i}",
                'order_date'   => '2026-09-0' . $i,
                'status'       => OrderStatus::SELESAI,
                'total_qty'    => 2,
                'total_amount' => 100000,
            ]);
            OrderItem::create([
                'order_id'           => $order->id,
                'variant_id'         => $this->variantA->id,
                'qty_ordered'        => 2,
                'unit_selling_price' => 50000,
            ]);
        }

        // Customer B: 1 order totaling 1,000,000
        $orderB = Order::create([
            'customer_id'  => $this->customerB->id,
            'order_no'     => 'ORD-CUSTB-01',
            'order_date'   => '2026-09-04',
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 10,
            'total_amount' => 1000000,
        ]);
        OrderItem::create([
            'order_id'           => $orderB->id,
            'variant_id'         => $this->variantB->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 100000,
        ]);

        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 6);
        $top = $analytics['top_customers'];

        $this->assertCount(2, $top);

        // Rank 1: Customer B (1,000,000 revenue with 1 order)
        $this->assertSame($this->customerB->id, $top[0]['customer_id']);
        $this->assertSame('1000000.00', $top[0]['revenue']);
        $this->assertSame(1, $top[0]['order_count']);
        $this->assertSame('1 pesanan', $top[0]['order_count_label']);

        // Rank 2: Customer A (300,000 revenue with 3 orders)
        $this->assertSame($this->customerA->id, $top[1]['customer_id']);
        $this->assertSame('300000.00', $top[1]['revenue']);
        $this->assertSame(3, $top[1]['order_count']);
        $this->assertSame('3 pesanan', $top[1]['order_count_label']);
    }

    public function test_negative_gross_profit_is_preserved_without_clamping_to_zero(): void
    {
        // Order selling at loss: Revenue = 100,000, HPP = 300,000 -> GP = -200,000
        $order = Order::create([
            'customer_id'  => $this->customerA->id,
            'order_no'     => 'ORD-LOSS-01',
            'order_date'   => '2026-09-05',
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 10,
            'total_amount' => 100000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantA->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 10000, // Revenue = 100,000
        ]);
        // Material 10 * 20,000 = 200,000 + Labor 10 * 10,000 = 100,000 => HPP = 300,000
        $this->createCompletedBatchWithCost(
            $item,
            'BAT-LOSS-01',
            10,
            10,
            10,
            0,
            10,
            20000,
            10000
        );

        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 6);
        $sepComp = collect($analytics['profit_comparison'])->firstWhere('period', '2026-09');

        $this->assertNotNull($sepComp);
        $this->assertTrue($sepComp['is_final']);
        $this->assertSame('100000.00', $sepComp['revenue']);
        $this->assertSame('300000.00', $sepComp['hpp']);
        $this->assertSame('-200000.00', $sepComp['gross_profit']);
        $this->assertSame(-200000.0, $sepComp['gross_profit_numeric']);
        $this->assertSame('-Rp 200.000', $sepComp['gross_profit_formatted']);
    }

    public function test_trailing_months_supports_12_months(): void
    {
        $analytics = $this->service->getDashboardAnalytics('2026-09-01', '2026-09-30', 12);

        $this->assertSame(12, $analytics['trailing_months']);
        $this->assertCount(12, $analytics['revenue_trend']);
        $this->assertCount(12, $analytics['profit_comparison']);
        $this->assertStringContainsString('Tren 12 bulan', $analytics['trend_scope_label']);
    }
}
