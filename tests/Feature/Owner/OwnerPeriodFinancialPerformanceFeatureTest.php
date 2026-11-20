<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\ExpenseType;
use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\BatchCost;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\OrderFinancial;
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
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerPeriodFinancialPerformanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private User $produksi;
    private User $gudang;
    private User $customerUser;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->produksi = User::factory()->create(['role' => UserRole::PRODUKSI, 'status' => UserStatus::ACTIVE]);
        $this->gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->customerUser = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $material = Material::create([
            'material_name' => 'Kain Ceruty Babydoll',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 25000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => 1000,
            'avg_unit_cost' => 25000,
        ]);

        $customer = Customer::create(['customer_name' => 'Boutique Hijab Syari', 'phone' => '08111222333']);

        $product = Product::create(['product_name' => 'Dress Syari']);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Dusty Pink',
            'sku'          => 'DRS-DST',
        ]);

        $this->order = Order::create([
            'customer_id'  => $customer->id,
            'order_no'     => 'ORD-PERF-01',
            'order_date'   => now('Asia/Jakarta')->toDateString(),
            'status'       => 'DEAL',
            'total_qty'    => 20,
            'total_amount' => 3000000,
        ]);

        $item = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 150000,
            'subtotal'           => 3000000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-PERF-01',
            'target_qty'                                    => 20,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '25000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
            'material_usage_recorded_at'                    => now(),
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $material->id,
            'required_qty'  => 20,
            'allocated_qty' => 20,
            'used_qty'      => 20,
        ]);

        StockMovement::create([
            'material_id'         => $material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 20,
            'unit_cost_snapshot'  => '25000.0000',
            'total_cost_snapshot' => '500000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now('Asia/Jakarta')->toDateString(),
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 20,
            'result_date' => now('Asia/Jakarta')->toDateString(),
        ]);

        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 20,
            'reject_qty'           => 0,
            'qc_date'              => now('Asia/Jakarta')->toDateString(),
        ]);

        // Marketing expense: 200.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::MARKETING,
            'amount'       => '200000.00',
            'expense_date' => now('Asia/Jakarta')->toDateString(),
            'description'  => 'Google Ads Campaign',
            'created_by'   => $this->admin->id,
        ]);
        // Operating expense: 300.000
        BusinessExpense::create([
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '300000.00',
            'expense_date' => now('Asia/Jakarta')->toDateString(),
            'description'  => 'Biaya Listrik',
            'created_by'   => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'order_id'     => $this->order->id,
            'invoice_no'   => 'INV-PERF-01',
            'invoice_date' => now('Asia/Jakarta')->toDateString(),
            'total_amount' => 3000000,
            'status'       => InvoiceStatus::UNPAID,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 500000,
            'payment_date'   => now('Asia/Jakarta')->toDateString(),
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);
    }

    public function test_owner_can_view_dashboard_with_period_financial_kpis(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Owner');
        $response->assertSee('Selamat Datang, Owner');

        // Revenue = 3.000.000
        $response->assertSee('3.000.000');
        // HPP = 1.000.000 (500k material + 500k labor)
        $response->assertSee('1.000.000');
        // Gross Profit = 2.000.000
        $response->assertSee('2.000.000');
        // Total Expenses = 500.000 (200k mkt + 300k opex)
        // Net Profit = 1.500.000 (2m - 500k)
        $response->assertSee('1.500.000');

        // Efficiency metrics
        // Standardized ROI = (3.000.000 / 500.000) * 100 = 600.00%
        $response->assertSee('ROI');
        $response->assertSee('600,00%');
        // MER and EBITDA are hidden on dashboard UI until authoritative formula is established
        $response->assertDontSee('MER');
        $response->assertDontSee('EBITDA');
    }

    public function test_owner_can_view_financial_performance_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.performance.index'));

        $response->assertOk();
        $response->assertSee('Kinerja Keuangan');
        $response->assertSee('Ringkasan Laba Rugi');
        $response->assertSee('Rincian Pengeluaran');
        $response->assertSee('Daftar Pesanan Dalam Periode');
        $response->assertSee('ORD-PERF-01');
        $response->assertSee('3.000.000');
        $response->assertSee('1.000.000');
    }

    public function test_period_preset_filters_are_supported(): void
    {
        // Test today preset
        $response = $this->actingAs($this->owner)->get(route('owner.performance.index', ['preset' => 'today']));
        $response->assertOk();
        $response->assertSee('ORD-PERF-01');

        // Test last_7_days preset
        $response = $this->actingAs($this->owner)->get(route('owner.performance.index', ['preset' => 'last_7_days']));
        $response->assertOk();

        // Test custom date range in past with no orders
        $response = $this->actingAs($this->owner)->get(route('owner.performance.index', [
            'preset'     => 'custom',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-01-31',
        ]));
        $response->assertOk();
        $response->assertSee('Tidak ada pesanan pada periode terpilih.');
    }

    public function test_other_roles_cannot_access_owner_financial_performance(): void
    {
        $this->actingAs($this->admin)->get(route('owner.performance.index'))->assertForbidden();
        $this->actingAs($this->produksi)->get(route('owner.performance.index'))->assertForbidden();
        $this->actingAs($this->gudang)->get(route('owner.performance.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('owner.performance.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('owner.dashboard'))->assertRedirect(route('login'));
        $this->get(route('owner.performance.index'))->assertRedirect(route('login'));
    }

    public function test_owner_reads_do_not_persist_or_mutate_order_financials(): void
    {
        $initialCount = OrderFinancial::count();

        // Owner visits dashboard
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk();

        // Owner visits performance page
        $this->actingAs($this->owner)->get(route('owner.performance.index'))->assertOk();

        // Count must remain untouched (strictly side-effect free)
        $this->assertEquals($initialCount, OrderFinancial::count());
    }

    public function test_owner_dashboard_displays_gross_margin_and_net_margin_when_ready(): void
    {
        // Setup in setUp(): Revenue = 3.000.000, HPP = 1.000.000, GP = 2.000.000, NP = 1.500.000
        // Gross Margin = (2.000.000 / 3.000.000) * 100 = 66,67%
        // Net Margin = (1.500.000 / 3.000.000) * 100 = 50,00%
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();
        $response->assertSee('Gross Margin');
        $response->assertSee('Net Margin');
        $response->assertSee('66,67%');
        $response->assertSee('50,00%');
    }

    public function test_owner_dashboard_displays_belum_final_when_not_ready_while_cash_collection_remains_available(): void
    {
        // Add an unready order in the same period
        $unreadyOrder = Order::create([
            'customer_id'  => $this->order->customer_id,
            'order_no'     => 'ORD-UNRDY-DASH',
            'order_date'   => now('Asia/Jakarta')->toDateString(),
            'status'       => 'PRODUKSI',
            'total_qty'    => 10,
            'total_amount' => 1000000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $unreadyOrder->id,
            'variant_id'         => $this->order->items->first()->variant_id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 100000,
            'subtotal'           => 1000000,
        ]);
        ProductionBatch::create([
            'order_item_id' => $item->id,
            'batch_no'      => 'BAT-UNRDY-DASH',
            'target_qty'    => 10,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();
        // Warning banner appears
        $response->assertSee('Profitabilitas Periode Belum Final');
        // Total Revenue updated to 4.000.000 (3m + 1m)
        $response->assertSee('4.000.000');

        // Belum Final appears for unready metrics
        $response->assertSee('Belum Final');

        // MER and EBITDA are hidden from dashboard UI until authoritative formula is established
        $response->assertDontSee('MER');
        $response->assertDontSee('EBITDA');

        // Pembayaran Diterima remains available (cash collection)
        $response->assertSee('Penerimaan Kas');
    }
}
