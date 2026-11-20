<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\ExpenseType;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\OpeningCashBalance;
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

class OwnerDashboardRefinementFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private User $gudang;
    private User $produksi;
    private User $customerUser;

    private Customer $customer;
    private ProductVariant $variant;
    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->produksi = User::factory()->create(['role' => UserRole::PRODUKSI, 'status' => UserStatus::ACTIVE]);
        $this->customerUser = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);

        // Opening cash
        OpeningCashBalance::create([
            'opening_date' => '2026-01-01',
            'amount'       => '90089000.00',
            'notes'        => 'Saldo Awal Operasional',
            'created_by'   => $this->owner->id,
        ]);

        $this->customer = Customer::create(['customer_name' => 'Nayara Partner Store', 'phone' => '08123456789']);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->material = Material::create([
            'material_name' => 'Kain Voal Nayara',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 15000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => 1000,
            'avg_unit_cost' => 15000,
        ]);

        $prod = Product::create(['product_name' => 'Hijab Bella Square']);
        $this->variant = ProductVariant::create([
            'product_id'         => $prod->id,
            'variant_name'       => 'Dusty Pink',
            'sku'                => 'HJB-DST-01',
            'base_selling_price' => 70000,
        ]);
    }

    /**
     * 1. Dashboard still renders 5 financial summary metrics.
     * 2. Current Cash value preserved.
     * 3. Revenue value preserved.
     * 4. HPP status preserved.
     * 5. Gross Profit status preserved.
     * 6. Net Profit status preserved.
     * 7. ROI remains visible.
     * 8. Alur Laba strip removed.
     * 9. Duplicate sold quantity removed.
     * 10. MER hidden if not authoritative.
     * 11. EBITDA hidden if not authoritative.
     */
    public function test_owner_dashboard_refinement_visual_contract_and_preservation(): void
    {
        // Create an unready order (in production) with 100 pcs
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-REFINE-01',
            'order_date'   => now('Asia/Jakarta')->toDateString(),
            'status'       => OrderStatus::PRODUKSI,
            'total_qty'    => 100,
            'total_amount' => 7000000,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 70000,
            'subtotal'           => 7000000,
        ]);

        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-REFINE-01',
            'target_qty'    => 100,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();

        // 1. Dashboard still renders 5 financial summary metrics
        $response->assertSee('Ringkasan Keuangan');
        $response->assertSee('Kas Saat Ini');
        $response->assertSee('Pendapatan');
        $response->assertSee('HPP Tercatat');
        $response->assertSee('Laba Kotor');
        $response->assertSee('Laba Bersih');

        // 2. Current Cash value preserved
        $response->assertSee('90.089.000');
        $response->assertSee('Saldo operasional');
        $response->assertDontSee('Saldo kas operasional terkini');

        // 3. Revenue value preserved
        $response->assertSee('7.000.000');
        $response->assertSee('100 pcs');
        $response->assertSee('1 pesanan');

        // 4. HPP Tercatat shows '—' and 'Belum ada HPP final' when zero orders final
        $response->assertSee('Belum ada HPP final');

        // 5. Gross profit shows 'Belum ada pesanan final'
        $response->assertSee('Belum ada pesanan final');

        // 6. Net profit shows 'Menunggu 1 pesanan'
        $response->assertSee('Menunggu 1 pesanan');

        // 7. ROI remains visible and highlighted
        $response->assertSee('ROI');
        $response->assertSee('Utama');
        $response->assertSee('Pendapatan / Pengeluaran');

        // 8. Alur Laba strip removed
        $response->assertDontSee('Alur Laba');
        $response->assertDontSee('Penjualan – Modal Produksi (HPP) = Laba Kotor – Beban Usaha = Laba Bersih');

        // 9. Duplicate sold quantity removed
        $response->assertDontSee('Total Barang Terjual:');

        // 10. MER hidden if not authoritative
        $response->assertDontSee('MER');
        $response->assertDontSee('Revenue / Biaya Marketing');

        // 11. EBITDA hidden if not authoritative
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('Sebelum bunga, pajak, depr.');

        // Currency prefix & no abbreviation
        $response->assertSee('Rp');
        $response->assertDontSee('90,1 jt');
        $response->assertDontSee('90,08 jt');
        $response->assertDontSee('7,0 jt');
        $response->assertSee('tabular-nums', false);
        $response->assertSee('tracking-tight', false);
    }

    /**
     * Test longer nominal values (e.g., hundreds of millions) render completely without abbreviation.
     */
    public function test_longer_amount_safety_renders_full_numbers_without_abbreviation(): void
    {
        // Update opening balance to 999.999.999
        OpeningCashBalance::query()->delete();
        OpeningCashBalance::create([
            'opening_date' => '2026-01-01',
            'amount'       => '999999999.00',
            'notes'        => 'High balance test',
            'created_by'   => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $response->assertOk();

        // Exact un-truncated rupiah value
        $response->assertSee('999.999.999');
        $response->assertDontSee('999,9 jt');
        $response->assertDontSee('999,99 jt');
        $response->assertDontSee('1,0 M');
    }

    /**
     * 12. Dashboard calculations unchanged.
     */
    public function test_dashboard_calculations_and_period_financial_service_remain_exact(): void
    {
        $today = now('Asia/Jakarta')->toDateString();

        // Completed order with full final costs
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-CALC-01',
            'order_date'   => $today,
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 50,
            'total_amount' => 3500000,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 70000,
            'subtotal'           => 3500000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-CALC-01',
            'target_qty'                                    => 50,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '10000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 50,
            'allocated_qty' => 50,
            'used_qty'      => 50,
        ]);

        StockMovement::create([
            'material_id'         => $this->material->id,
            'movement_type'       => MovementType::OUT,
            'movement_date'       => $today,
            'qty'                 => 50,
            'unit_cost_snapshot'  => '15000.0000',
            'total_cost_snapshot' => '750000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 50,
            'result_date' => $today,
        ]);

        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 50,
            'reject_qty'           => 0,
            'qc_date'              => $today,
        ]);

        $invoice = Invoice::create([
            'order_id'       => $order->id,
            'invoice_no'     => 'INV-CALC-01',
            'invoice_date'   => $today,
            'due_date'       => $today,
            'total_amount'   => 3500000,
            'paid_amount'    => 2000000,
            'status'         => \App\Enums\InvoiceStatus::PARTIAL,
        ]);

        Payment::create([
            'order_id'       => $order->id,
            'invoice_id'     => $invoice->id,
            'payment_no'     => 'PAY-CALC-01',
            'amount'         => 2000000,
            'payment_date'   => $today,
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'payment_type'   => PaymentType::PELUNASAN,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        BusinessExpense::create([
            'expense_date' => $today,
            'expense_type' => ExpenseType::OPERATING,
            'description'  => 'Biaya Listrik & Operasional',
            'amount'       => 1000000,
            'created_by'   => $this->owner->id,
        ]);

        $service = app(PeriodFinancialService::class);
        $financials = $service->calculatePeriodFinancials($today, $today);

        // Verification of business formulas:
        // Revenue = 3.500.000
        $this->assertEquals('3500000.00', $financials['revenue']);
        // HPP = 750.000 (material) + 500.000 (labor) = 1.250.000
        $this->assertEquals('1250000.00', $financials['hpp']);
        // Gross Profit = 3.500.000 - 1.250.000 = 2.250.000
        $this->assertEquals('2250000.00', $financials['gross_profit']);
        // Net Profit = 2.250.000 - 1.000.000 = 1.250.000
        $this->assertEquals('1250000.00', $financials['net_profit']);
        // Gross Margin = (2.250.000 / 3.500.000) * 100 = 64.2857%
        $this->assertEquals('64.2857', $financials['gross_margin']);
        // ROI = (Total Revenue / Total Pengeluaran) * 100 = (3.500.000 / 1.000.000) * 100 = 350.0000%
        $this->assertEquals('350.0000', $financials['roi']);

        // View also renders actual values
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', ['preset' => 'today']));
        $response->assertOk();
        $response->assertSee('3.500.000');
        $response->assertSee('1.250.000');
        $response->assertSee('2.250.000');
        $response->assertSee('1.250.000');
        $response->assertSee('64,29%');
        $response->assertSee('350,00%');
    }

    /**
     * 13. Owner-only access unchanged.
     */
    public function test_owner_only_access_remains_strictly_enforced(): void
    {
        $this->get(route('owner.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($this->admin)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($this->gudang)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($this->produksi)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk();
    }

    private function createOrderWithCompletedBatch(
        string $orderNo,
        string $orderDate,
        int $qty,
        float $unitPrice,
        float $materialQtyUsed,
        float $materialUnitSnapshot,
        float $directLaborAmount = 0
    ): Order {
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => $orderNo,
            'order_date'   => $orderDate,
            'status'       => OrderStatus::SELESAI,
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

        $laborSnapshot = $qty > 0 ? ($directLaborAmount / $qty) : 0;

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => "BAT-{$orderNo}",
            'target_qty'                                    => $qty,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => number_format($laborSnapshot, 4, '.', ''),
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => $materialQtyUsed,
            'allocated_qty' => $materialQtyUsed,
            'used_qty'      => $materialQtyUsed,
        ]);

        StockMovement::create([
            'material_id'         => $this->material->id,
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

    /**
     * Comprehensive test for 3 orders, 2 final, 1 pending HPP:
     * - Total revenue represents all 3 orders (13.957.000, 157 pcs, 3 pesanan).
     * - HPP card shows sum for 2 final orders (2.123.666) with "2 dari 3 pesanan final".
     * - Gross profit uses revenue of finalized orders ONLY (7.000.000 - 2.123.666 = 4.876.334) with "2 pesanan final".
     * - Accounting safety: Gross profit never subtracts partial HPP from total revenue (no 11.833.334).
     * - Gross margin uses finalized revenue / finalized HPP scope (69,66% with "2 pesanan final").
     * - Net profit and Net margin remain unavailable ("—", "Menunggu 1 pesanan").
     * - ROI remains available and unaffected by HPP pending (207,41%).
     * - Warning displays single notice with "1 pesanan masih menunggu finalisasi HPP."
     */
    public function test_partial_finality_with_three_orders_two_final_one_pending_and_accounting_safety(): void
    {
        $today = now('Asia/Jakarta')->toDateString();

        // Order 1: 50 pcs @ 70.000 = 3.500.000. HPP = 750.000 (material) + 500.000 (labor) = 1.250.000. Final.
        $this->createOrderWithCompletedBatch('ORD-PARTIAL-01', $today, 50, 70000, 50, 15000, 500000);

        // Order 2: 50 pcs @ 70.000 = 3.500.000. HPP = 300.000 (material 20m*15k) + 573.666 (labor) = 873.666. Final.
        $this->createOrderWithCompletedBatch('ORD-PARTIAL-02', $today, 50, 70000, 20, 15000, 573666);

        // Order 3: 57 pcs = 56 pcs @ 122.000 (6.832.000) + 1 pc @ 125.000 (125.000) = 6.957.000. In PRODUKSI (HPP not ready). Pending.
        $order3 = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-PARTIAL-03',
            'order_date'   => $today,
            'status'       => OrderStatus::PRODUKSI,
            'total_qty'    => 57,
            'total_amount' => 6957000,
        ]);
        $item3a = OrderItem::create([
            'order_id'           => $order3->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 56,
            'unit_selling_price' => 122000,
            'subtotal'           => 6832000,
        ]);
        $item3b = OrderItem::create([
            'order_id'           => $order3->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 1,
            'unit_selling_price' => 125000,
            'subtotal'           => 125000,
        ]);
        ProductionBatch::create([
            'order_item_id' => $item3a->id,
            'batch_no'      => 'BAT-ORD-PARTIAL-03A',
            'target_qty'    => 56,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        // Business expense: 1.000.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '1000000.00',
            'expense_date' => $today,
            'description'  => 'Biaya Operasional Periode',
            'created_by'   => $this->owner->id,
        ]);

        // Confirmed customer payment: 2.074.100
        $inv = Invoice::create([
            'order_id'     => $order3->id,
            'invoice_no'   => 'INV-PARTIAL-03',
            'invoice_date' => $today,
            'total_amount' => 6957000,
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        Payment::create([
            'order_id'       => $order3->id,
            'invoice_id'     => $inv->id,
            'payment_no'     => 'PAY-PARTIAL-03',
            'amount'         => '2074100.00',
            'payment_date'   => $today,
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'payment_type'   => PaymentType::DP,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', ['preset' => 'today']));
        $response->assertOk();

        // 1. Total revenue represents all 3 orders: 13.957.000, 157 pcs, 3 pesanan
        $response->assertSee('13.957.000');
        $response->assertSee('157 pcs');
        $response->assertSee('3 pesanan');

        // 2 & 3. HPP card shows sum for 2 final orders: 2.123.666 and "2 dari 3 pesanan final"
        $response->assertSee('HPP Tercatat');
        $response->assertSee('2.123.666');
        $response->assertSee('2 dari 3 pesanan final');

        // 4 & 5. Gross profit uses revenue of finalized orders ONLY:
        // Finalized Revenue (7.000.000) - Finalized HPP (2.123.666) = 4.876.334
        $response->assertSee('Laba Kotor');
        $response->assertSee('4.876.334');
        $response->assertSee('2 pesanan final');

        // Critical Accounting Safety: NEVER subtract partial HPP from total period revenue!
        // 13.957.000 - 2.123.666 = 11.833.334 MUST NOT BE SHOWN!
        $response->assertDontSee('11.833.334');

        // 6. Gross margin uses finalized revenue / finalized HPP scope:
        // (4.876.334 / 7.000.000) * 100 = 69,66%
        $response->assertSee('69,66%');

        // 7 & 8. Net profit and Net margin remain unavailable ("—", "Menunggu 1 pesanan")
        $response->assertSee('Menunggu 1 pesanan');

        // 9. ROI remains available while HPP pending:
        // (13.957.000 / 1.000.000) * 100 = 1.395,70%
        $response->assertSee('1.395,70%');
        $response->assertSee('Pendapatan / Pengeluaran');

        // 10. Warning displays single notice with correct pending count:
        $response->assertSee('1 pesanan masih menunggu finalisasi HPP.');
        $response->assertSee('Nilai profitabilitas pesanan yang sudah final tetap ditampilkan.');
        $response->assertSee('Lihat Pesanan Belum Final');
    }

    /**
     * Test when all orders become final:
     * - Warning disappears completely.
     * - HPP card shows "X dari X pesanan final".
     * - Net profit and Net margin become calculated.
     * - Normal final state helper texts.
     */
    public function test_when_all_orders_become_final_warning_disappears_and_metrics_fully_calculate(): void
    {
        $today = now('Asia/Jakarta')->toDateString();

        // 2 orders, both completed and finalized
        $this->createOrderWithCompletedBatch('ORD-ALLFINAL-01', $today, 50, 70000, 50, 15000, 500000);
        $this->createOrderWithCompletedBatch('ORD-ALLFINAL-02', $today, 50, 70000, 50, 15000, 500000);

        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '500000.00',
            'expense_date' => $today,
            'description'  => 'Biaya Listrik',
            'created_by'   => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', ['preset' => 'today']));
        $response->assertOk();

        // Warning banner is completely gone
        $response->assertDontSee('pesanan masih menunggu finalisasi HPP');
        $response->assertDontSee('Profitabilitas Periode Belum Final');
        $response->assertDontSee('Lihat Pesanan Belum Final');

        // HPP shows 2 dari 2 pesanan final
        $response->assertSee('2.500.000');
        $response->assertSee('2 dari 2 pesanan final');

        // Gross profit: 7.000.000 - 2.500.000 = 4.500.000
        $response->assertSee('4.500.000');
        $response->assertSee('Setelah biaya produksi (HPP)');

        // Net profit: 4.500.000 - 500.000 = 4.000.000
        $response->assertSee('4.000.000');
        $response->assertSee('Setelah seluruh beban usaha periode');

        // Gross Margin: (4.500.000 / 7.000.000) * 100 = 64,29%
        $response->assertSee('64,29%');
        $response->assertSee('Laba Kotor / Pendapatan');

        // Net Margin: (4.000.000 / 7.000.000) * 100 = 57,14%
        $response->assertSee('57,14%');
    }

    /**
     * Test adding a new unfinished order does not erase existing finalized HPP / gross profit / gross margin.
     */
    public function test_new_unfinished_order_does_not_erase_finalized_hpp_and_gross_metrics(): void
    {
        $today = now('Asia/Jakarta')->toDateString();

        // 1 completed order (HPP 1.250.000, Revenue 3.500.000)
        $this->createOrderWithCompletedBatch('ORD-PERSIST-01', $today, 50, 70000, 50, 15000, 500000);

        // Now a new order arrives, still unready
        $newOrder = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-PERSIST-02',
            'order_date'   => $today,
            'status'       => OrderStatus::PRODUKSI,
            'total_qty'    => 30,
            'total_amount' => 2100000,
        ]);
        $newItem = OrderItem::create([
            'order_id'           => $newOrder->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 70000,
            'subtotal'           => 2100000,
        ]);
        ProductionBatch::create([
            'order_item_id' => $newItem->id,
            'batch_no'      => 'BAT-ORD-PERSIST-02',
            'target_qty'    => 30,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', ['preset' => 'today']));
        $response->assertOk();

        // Total revenue includes both orders: 3.500.000 + 2.100.000 = 5.600.000
        $response->assertSee('5.600.000');
        $response->assertSee('2 pesanan');

        // Existing finalized HPP remains visible
        $response->assertSee('1.250.000');
        $response->assertSee('1 dari 2 pesanan final');

        // Existing finalized Gross Profit remains visible: 3.500.000 - 1.250.000 = 2.250.000
        $response->assertSee('2.250.000');
        $response->assertSee('1 pesanan final');

        // Gross margin: (2.250.000 / 3.500.000) * 100 = 64,29%
        $response->assertSee('64,29%');

        // Pending count is 1
        $response->assertSee('1 pesanan masih menunggu finalisasi HPP.');
        $response->assertSee('Menunggu 1 pesanan');
    }

    /**
     * Test zero finalized orders and zero revenue denominator handled safely.
     */
    public function test_zero_finalized_orders_and_zero_revenue_denominator_handled_safely(): void
    {
        $today = now('Asia/Jakarta')->toDateString();

        // Order with zero revenue (e.g. promotional / sample order with price 0)
        $order = Order::create([
            'customer_id'  => $this->customer->id,
            'order_no'     => 'ORD-ZERO-01',
            'order_date'   => $today,
            'status'       => OrderStatus::PRODUKSI,
            'total_qty'    => 10,
            'total_amount' => 0,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 0,
            'subtotal'           => 0,
        ]);
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-ZERO-01',
            'target_qty'    => 10,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', ['preset' => 'today']));
        $response->assertOk();

        // Safe display for zero finalized orders
        $response->assertSee('Belum ada HPP final');
        $response->assertSee('Belum ada pesanan final');
        $response->assertSee('Menunggu 1 pesanan');

        // Check empty period (no orders at all)
        $emptyResponse = $this->actingAs($this->owner)->get(route('owner.dashboard', [
            'preset'     => 'custom',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-02',
        ]));
        $emptyResponse->assertOk();
        $emptyResponse->assertSee('Belum ada HPP final');
        $emptyResponse->assertSee('Belum ada pesanan final');
    }
}
