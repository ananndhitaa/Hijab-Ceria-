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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerProfitabilityMonitoringFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private User $produksi;
    private User $gudang;
    private User $customerUser;
    private Order $order;
    private ProductionBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);
        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
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

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $material = Material::create([
            'material_name' => 'Kain Silk Sutra',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 50000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 50000,
        ]);

        $cust = Customer::create(['customer_name' => 'Butik Syari Indah', 'phone' => '081234567890']);
        $this->order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-PROFIT-01',
            'order_date'   => now()->toDateString(),
            'status'       => 'DEAL',
            'total_qty'    => 20,
            'total_amount' => 4000000,
        ]);

        $product = Product::create(['product_name' => 'Gamis Silk']);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Navy Velvet',
            'sku'          => 'GMS-NVY',
        ]);

        $item = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 20,
            'unit_selling_price' => 200000,
            'subtotal'           => 4000000,
        ]);

        $this->batch = ProductionBatch::create([
            'order_item_id'                                 => $item->id,
            'batch_no'                                      => 'BAT-202609-PROFIT-01',
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
            'batch_id'      => $this->batch->id,
            'material_id'   => $material->id,
            'required_qty'  => 20,
            'allocated_qty' => 20,
            'used_qty'      => 20,
        ]);

        StockMovement::create([
            'material_id'         => $material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 20,
            'unit_cost_snapshot'  => '50000.0000',
            'total_cost_snapshot' => '1000000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => now()->toDateString(),
        ]);

        $res = ProductionResult::create([
            'batch_id'    => $this->batch->id,
            'actual_qty'  => 20,
            'result_date' => now()->toDateString(),
        ]);

        QcResult::create([
            'production_result_id' => $res->id,
            'good_qty'             => 20,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
        ]);

        // Attributed Operating Expense: 200.000
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => '200000.00',
            'expense_date' => now()->toDateString(),
            'description'  => 'Ongkos kurir express',
            'created_by'   => $this->admin->id,
        ]);
    }

    public function test_owner_can_view_profitability_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.profitability.index'));

        $response->assertOk();
        $response->assertSee('Profitabilitas Pesanan');
        $response->assertSee('ORD-202609-PROFIT-01');
        $response->assertSee('Butik Syari Indah');

        // Revenue: 4.000.000
        $response->assertSee('4.000.000');
        // HPP: 1.500.000 (1m material + 500k labor)
        $response->assertSee('1.500.000');
        // Gross Profit: 2.500.000 (4m - 1.5m)
        $response->assertSee('2.500.000');
        // Gross Margin: 62,50%
        $response->assertSee('62,50%');
        // Net Profit: 2.300.000 (2.5m - 200k opex)
        $response->assertSee('2.300.000');
        // Net Margin: 57,50%
        $response->assertSee('57,50%');

        // Invariant: No premature period ROI/MER/EBITDA
        $response->assertDontSee('EBITDA');
        $response->assertDontSee('ROI');
        $response->assertDontSee('MER');
    }

    public function test_owner_can_view_order_profitability_detail(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.profitability.show', $this->order));

        $response->assertOk();
        $response->assertSee('ORD-202609-PROFIT-01');
        $response->assertSee('Laba Bersih Pesanan (Net Profit)');
        $response->assertSee('Struktur Perhitungan Laba Pesanan (Profit Waterfall)');
        $response->assertSee('4.000.000'); // Revenue
        $response->assertSee('1.500.000'); // HPP
        $response->assertSee('2.500.000'); // Gross Profit
        $response->assertSee('200.000');   // Operating Expense
        $response->assertSee('2.300.000'); // Net Profit
        $response->assertSee('Ongkos kurir express');

        // Navigation link to HPP detail is present
        $response->assertSee('Lihat Rincian HPP');

        // Read-only invariant: No edit/add expense mutation buttons
        $response->assertDontSee('Tambah Beban');
        $response->assertDontSee('Edit Beban');
        $response->assertDontSee('Hapus');
    }

    public function test_other_roles_cannot_access_owner_profitability_pages(): void
    {
        // Admin
        $this->actingAs($this->admin)->get(route('owner.profitability.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('owner.profitability.show', $this->order))->assertForbidden();

        // Produksi
        $this->actingAs($this->produksi)->get(route('owner.profitability.index'))->assertForbidden();
        $this->actingAs($this->produksi)->get(route('owner.profitability.show', $this->order))->assertForbidden();

        // Gudang
        $this->actingAs($this->gudang)->get(route('owner.profitability.index'))->assertForbidden();
        $this->actingAs($this->gudang)->get(route('owner.profitability.show', $this->order))->assertForbidden();

        // Customer
        $this->actingAs($this->customerUser)->get(route('owner.profitability.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('owner.profitability.show', $this->order))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('owner.profitability.index'))->assertRedirect(route('login'));
        $this->get(route('owner.profitability.show', $this->order))->assertRedirect(route('login'));
    }

    public function test_unready_orders_contribute_to_revenue_total_but_not_profit_totals(): void
    {
        // Create second order that is NOT ready (in PRODUKSI status, no completed batch)
        $cust2 = Customer::create(['customer_name' => 'Toko Busana Cantik', 'phone' => '0899999999']);
        $order2 = Order::create([
            'customer_id'  => $cust2->id,
            'order_no'     => 'ORD-202609-UNREADY',
            'order_date'   => now()->toDateString(),
            'status'       => 'PRODUKSI',
            'total_qty'    => 10,
            'total_amount' => 2000000,
        ]);
        $product = Product::first();
        $variant = ProductVariant::first();
        OrderItem::create([
            'order_id'           => $order2->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 200000,
            'subtotal'           => 2000000,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.profitability.index'));

        $response->assertOk();

        // 1. Total Revenue includes BOTH Order 1 (4.000.000) and Order 2 (2.000.000) = 6.000.000
        $response->assertSee('6.000.000');

        // 2. Gross Profit includes ONLY Order 1 = 2.500.000 (NOT corrupted by Order 2)
        $response->assertSee('2.500.000');

        // 3. Net Profit includes ONLY Order 1 = 2.300.000 (NOT corrupted by Order 2)
        $response->assertSee('2.300.000');

        // 4. Counts reflect 1 Final, 1 Menunggu
        $response->assertSee('1');
        $response->assertSee('Menunggu HPP Final');
    }

    public function test_cancelled_orders_are_excluded_from_profitability_index_totals(): void
    {
        // Cancel the order
        \Illuminate\Support\Facades\DB::table('orders')->where('id', $this->order->id)->update(['status' => 'CANCELLED']);

        $response = $this->actingAs($this->owner)->get(route('owner.profitability.index'));

        $response->assertOk();

        // Revenue of cancelled order (4.000.000) is EXCLUDED from total revenue -> Rp 0
        $response->assertSee('Total Revenue');
        $response->assertSee('Rp 0');

        // Profit totals are not derived from cancelled order -> shows "—" and helper text
        $response->assertSee('Belum ada pesanan dengan profit final pada hasil ini.');
    }

    public function test_owner_profitability_reads_do_not_persist_to_order_financials(): void
    {
        // Ensure initial count of order_financials is known
        $initialFinancialsCount = \App\Models\Finance\OrderFinancial::count();

        // Owner views Index
        $this->actingAs($this->owner)->get(route('owner.profitability.index'))->assertOk();

        // Owner views Show
        $this->actingAs($this->owner)->get(route('owner.profitability.show', $this->order))->assertOk();

        // Row count MUST NOT change (100% side-effect free GET reads)
        $this->assertEquals($initialFinancialsCount, \App\Models\Finance\OrderFinancial::count());
    }
}
