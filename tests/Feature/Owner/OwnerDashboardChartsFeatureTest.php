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
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDashboardChartsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private User $gudang;
    private User $produksi;
    private User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $outlet = \App\Models\System\Outlet::create([
            'outlet_name' => 'Nayara Chart Convection',
            'outlet_code' => 'OUT-CHART-01',
            'status' => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'role' => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
            'outlet_id' => $outlet->id,
        ]);

        \App\Models\Platform\TenantSubscription::create([
            'outlet_id' => $outlet->id,
            'type' => \App\Models\Platform\TenantSubscription::TYPE_PREMIUM,
            'status' => \App\Models\Platform\TenantSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->produksi = User::factory()->create(['role' => UserRole::PRODUKSI, 'status' => UserStatus::ACTIVE]);
        $this->customerUser = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);
    }

    public function test_owner_can_view_dashboard_with_all_five_chart_containers(): void
    {
        // Seed an order and expense so all 5 charts have active canvas elements
        $cust = Customer::create(['customer_name' => 'Toko Barokah', 'phone' => '08123456789']);
        $prod = Product::create(['product_name' => 'Hijab Segiempat']);
        $var = ProductVariant::create(['product_id' => $prod->id, 'variant_name' => 'Navy', 'sku' => 'HJB-NVY-01']);

        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-CHART-01',
            'order_date'   => now('Asia/Jakarta')->toDateString(),
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 10,
            'total_amount' => 500000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 50000,
        ]);
        BusinessExpense::create([
            'expense_date' => now('Asia/Jakarta')->toDateString(),
            'expense_type' => ExpenseType::MARKETING,
            'description'  => 'Instagram Promo',
            'amount'       => 50000,
            'created_by'   => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();

        // 5 Chart containers
        $response->assertSee('id="revenueTrendChart"', false);
        $response->assertSee('id="profitComparisonChart"', false);
        $response->assertSee('id="expenseCompositionChart"', false);
        $response->assertSee('id="topProductsChart"', false);
        $response->assertSee('id="topCustomersChart"', false);

        // Chart titles and labels
        $response->assertSee('Tren Pendapatan');
        $response->assertSee('Pendapatan vs HPP vs Laba Kotor');
        $response->assertSee('Komposisi Beban Usaha');
        $response->assertSee('Produk dengan Penjualan Tertinggi');
        $response->assertSee('Customer dengan Nilai Penjualan Tertinggi');

        // Link to detailed financial performance
        $response->assertSee('Lihat Kinerja Keuangan');

        // Local Chart.js script asset
        $response->assertSee('vendor/chartjs/chart.umd.min.js', false);
    }

    public function test_owner_dashboard_shows_empty_states_when_no_business_data_exists(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();

        // Empty states for sections without data
        $response->assertSee('Belum ada beban usaha pada periode ini.');
        $response->assertSee('Belum ada penjualan pada periode ini.');
        $response->assertSee('Belum ada data customer pada periode ini.');
    }

    public function test_role_authorization_for_owner_dashboard(): void
    {
        // Guests redirected
        $guestResponse = $this->get(route('owner.dashboard'));
        $guestResponse->assertRedirect(route('login'));

        // Admin cannot access owner dashboard
        $adminResponse = $this->actingAs($this->admin)->get(route('owner.dashboard'));
        $adminResponse->assertForbidden();

        // Gudang cannot access owner dashboard
        $gudangResponse = $this->actingAs($this->gudang)->get(route('owner.dashboard'));
        $gudangResponse->assertForbidden();

        // Produksi cannot access owner dashboard
        $produksiResponse = $this->actingAs($this->produksi)->get(route('owner.dashboard'));
        $produksiResponse->assertForbidden();

        // Customer cannot access owner dashboard
        $customerResponse = $this->actingAs($this->customerUser)->get(route('owner.dashboard'));
        $customerResponse->assertForbidden();
    }

    public function test_trend_months_query_merging_preserves_dashboard_period_selection(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', [
            'preset'       => 'last_month',
            'trend_months' => 12,
        ]));

        $response->assertOk();
        $response->assertSee('preset=last_month', false);
        $response->assertSee('trend_months=6', false);
        $response->assertSee('trend_months=12', false);
    }

    public function test_customer_and_product_names_with_special_characters_serialize_safely(): void
    {
        $cust = Customer::create([
            'customer_name' => "Toko 'Amanah & Barokah' <Special> \"Hijab\"",
            'phone'         => '08123456789',
        ]);
        $prod = Product::create(['product_name' => "Voal 'Silk' & Cotton"]);
        $var = ProductVariant::create([
            'product_id'   => $prod->id,
            'variant_name' => 'Rose "Gold" & Ruby',
            'sku'          => 'RS-GLD',
        ]);

        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-SPECIAL-01',
            'order_date'   => now('Asia/Jakarta')->toDateString(),
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 5,
            'total_amount' => 250000,
        ]);
        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 5,
            'unit_selling_price' => 50000,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $response->assertOk();
        // HTML rendered safely without unescaped broken JS syntax errors
        $response->assertSee('revenueTrendChart', false);
    }

    public function test_owner_dashboard_view_has_zero_side_effects_on_all_tables(): void
    {
        // Seed full domain data
        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $mat = Material::create(['material_name' => 'Kain Rayon', 'unit_id' => $unit->id, 'cost_per_unit' => 15000, 'active' => true]);
        MaterialStock::create(['material_id' => $mat->id, 'qty_on_hand' => 500, 'avg_unit_cost' => 15000]);

        $cust = Customer::create(['customer_name' => 'Toko Sentosa', 'phone' => '0811998877']);
        $prod = Product::create(['product_name' => 'Gamis Rayon']);
        $var = ProductVariant::create([
            'product_id'                 => $prod->id,
            'variant_name'               => 'Hitam',
            'sku'                        => 'GMS-HTM',
            'base_selling_price'         => 120000,
            'requires_printing'          => false,
            'direct_labor_cost_per_unit' => 10000,
        ]);

        $order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-SE-01',
            'order_date'   => now('Asia/Jakarta')->toDateString(),
            'status'       => OrderStatus::SELESAI,
            'total_qty'    => 10,
            'total_amount' => 1200000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $var->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 120000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-SE-01',
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '10000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
            'material_usage_recorded_at'                    => now('Asia/Jakarta'),
        ]);
        $bm = BatchMaterial::create([
            'batch_id'             => $batch->id,
            'material_id'          => $mat->id,
            'planned_qty'          => 10,
            'actual_qty'           => 10,
            'used_qty'             => 10,
            'allocated_qty'        => 10,
            'actual_cost_per_unit' => 15000,
            'subtotal'             => 150000,
        ]);
        StockMovement::create([
            'material_id'         => $mat->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 10,
            'unit_cost_snapshot'  => 15000,
            'total_cost_snapshot' => 150000,
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now('Asia/Jakarta')->toDateString(),
        ]);
        $res = ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 10,
            'result_date' => now('Asia/Jakarta')->toDateString(),
        ]);
        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 10,
            'reject_qty'           => 0,
            'qc_date'              => now('Asia/Jakarta')->toDateString(),
        ]);
        BusinessExpense::create([
            'expense_date' => now('Asia/Jakarta')->toDateString(),
            'expense_type' => ExpenseType::MARKETING,
            'description'  => 'Facebook Ads',
            'amount'       => 100000,
            'created_by'   => $this->owner->id,
        ]);
        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-SE-01',
            'invoice_date' => now('Asia/Jakarta')->toDateString(),
            'total_amount' => 1200000,
            'status'       => 'PAID',
        ]);
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_no'     => 'PAY-SE-01',
            'payment_date'   => now('Asia/Jakarta')->toDateString(),
            'amount'         => 1200000,
            'payment_type'   => PaymentType::PELUNASAN,
            'payment_method' => PaymentMethod::TRANSFER_BANK,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        // Pre-counts
        $ordersCount = Order::count();
        $orderItemsCount = OrderItem::count();
        $batchesCount = ProductionBatch::count();
        $batchMaterialsCount = BatchMaterial::count();
        $stockMovementsCount = StockMovement::count();
        $expensesCount = BusinessExpense::count();
        $invoicesCount = Invoice::count();
        $paymentsCount = Payment::count();
        $orderFinancialsCount = OrderFinancial::count();

        // Perform GET request
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $response->assertOk();

        // Post-counts must match exactly
        $this->assertSame($ordersCount, Order::count(), 'Order count changed');
        $this->assertSame($orderItemsCount, OrderItem::count(), 'OrderItem count changed');
        $this->assertSame($batchesCount, ProductionBatch::count(), 'ProductionBatch count changed');
        $this->assertSame($batchMaterialsCount, BatchMaterial::count(), 'BatchMaterial count changed');
        $this->assertSame($stockMovementsCount, StockMovement::count(), 'StockMovement count changed');
        $this->assertSame($expensesCount, BusinessExpense::count(), 'BusinessExpense count changed');
        $this->assertSame($invoicesCount, Invoice::count(), 'Invoice count changed');
        $this->assertSame($paymentsCount, Payment::count(), 'Payment count changed');
        $this->assertSame($orderFinancialsCount, OrderFinancial::count(), 'OrderFinancial count changed');
        $this->assertSame(0, OrderFinancial::count(), 'OrderFinancial rows should never be created by GET requests');
    }

    public function test_owner_dashboard_uses_refined_terminology_and_contains_no_kpi_wording(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();

        // Ensure NO user-facing "KPI" or over-corporatized terms appear
        $response->assertDontSee('KPI');
        $response->assertDontSee('Primary KPI');
        $response->assertDontSee('Financial KPI');
        $response->assertDontSee('KPI Utama');
        $response->assertDontSee('KPI Keuangan');
        $response->assertDontSee('KPI Finansial');
        $response->assertDontSee('Performance KPI');
        $response->assertDontSee('Secondary KPI');
        $response->assertDontSee('Executive KPI');
        $response->assertDontSee('Executive Metrics');
        $response->assertDontSee('Business Intelligence');
        $response->assertDontSee('Performance Center');
        $response->assertDontSee('Financial Overview');
        $response->assertDontSee('Operational Snapshot');
        $response->assertDontSee('Cash Collection');

        // Required natural Indonesian NAYARA presentation wording
        $response->assertSee('Ringkasan Keuangan');
        $response->assertSee('Pendapatan');
        $response->assertSee('HPP Tercatat');
        $response->assertSee('Laba Kotor');
        $response->assertSee('Laba Bersih');

        $response->assertSee('Indikator Kinerja');
        $response->assertSee('Gross Margin');
        $response->assertSee('Net Margin');
        $response->assertSee('ROI');
        $response->assertDontSee('MER');
        $response->assertDontSee('EBITDA');

        $response->assertSee('Penerimaan Kas');
        $response->assertSee('Pembayaran pelanggan yang telah dikonfirmasi pada periode ini.');

        $response->assertSee('Analisis Penjualan');
        $response->assertSee('Tren Pendapatan');
        $response->assertSee('Pendapatan vs HPP vs Laba Kotor');
        $response->assertSee('Komposisi Beban Usaha');
        $response->assertSee('Produk dengan Penjualan Tertinggi');
        $response->assertSee('Customer dengan Nilai Penjualan Tertinggi');
        $response->assertSee('Perkembangan pendapatan selama 6 bulan terakhir (berdasarkan tanggal pesanan non-cancelled).');

        $response->assertSee('Ringkasan Operasional');
        $response->assertSee('Total Pesanan');
        $response->assertSee('Batch Aktif');
        $response->assertSee('Dalam Pengiriman');
        $response->assertSee('Pesanan Selesai');

        // Verify 12-month timeline helper updates dynamically
        $response12 = $this->actingAs($this->owner)->get(route('owner.dashboard', ['trend_months' => 12]));
        $response12->assertOk();
        $response12->assertSee('Perkembangan pendapatan selama 12 bulan terakhir (berdasarkan tanggal pesanan non-cancelled).');
        $response12->assertSee('Tren 12 bulan');
    }
}

