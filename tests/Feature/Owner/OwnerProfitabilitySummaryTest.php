<?php

namespace Tests\Feature\Owner;

use App\Enums\BatchStatus;
use App\Enums\CostType;
use App\Enums\ExpenseType;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
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
use App\Services\Finance\OrderProfitabilityService;
use App\Services\Finance\PeriodFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerProfitabilitySummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $customerUser;
    private Order $order;
    private ProductVariant $variant1;
    private ProductVariant $variant2;
    private ProductionBatch $batch1;
    private ProductionBatch $batch2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $material = Material::create([
            'material_name' => 'Kain Kaos Premium',
            'unit_id'       => $unit->id,
            'cost_per_unit' => 30000,
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => 500,
            'avg_unit_cost' => 30000,
        ]);

        $cust = Customer::create(['customer_name' => 'Toko Hijab Cantik', 'phone' => '081298765432']);
        $this->order = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-SUMM-01',
            'order_date'   => now('Asia/Jakarta')->toDateString(),
            'status'       => OrderStatus::DEAL,
            'total_qty'    => 100,
            'total_amount' => 5000000,
        ]);

        $product = Product::create(['product_name' => 'Pashmina Kaos']);
        $this->variant1 = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Hitam',
            'sku'                => 'PK-HTM-01',
            'base_selling_price' => 50000,
        ]);
        $this->variant2 = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Mocca',
            'sku'                => 'PK-MCC-01',
            'base_selling_price' => 50000,
        ]);

        // Item 1: 60 pcs @ 50.000 = 3.000.000
        $item1 = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 60,
            'unit_selling_price' => 50000,
            'subtotal'           => 3000000,
        ]);

        // Item 2: 40 pcs @ 50.000 = 2.000.000
        $item2 = OrderItem::create([
            'order_id'           => $this->order->id,
            'variant_id'         => $this->variant2->id,
            'qty_ordered'        => 40,
            'unit_selling_price' => 50000,
            'subtotal'           => 2000000,
        ]);

        // Complete batches for HPP readiness
        // Batch 1 for Item 1: 60 pcs
        $this->batch1 = ProductionBatch::create([
            'batch_no'                                      => 'BAT-SUMM-01',
            'order_item_id'                                 => $item1->id,
            'target_qty'                                    => 60,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
            'material_usage_recorded_at'                    => now(),
        ]);
        $bm1 = BatchMaterial::create([
            'batch_id'      => $this->batch1->id,
            'material_id'   => $material->id,
            'required_qty'  => 60,
            'allocated_qty' => 60,
            'used_qty'      => 60,
        ]);
        StockMovement::create([
            'material_id'         => $material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 60,
            'unit_cost_snapshot'  => '20000.0000',
            'total_cost_snapshot' => '1200000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm1->id,
            'movement_date'       => now()->toDateString(),
        ]);
        $res1 = ProductionResult::create([
            'batch_id'    => $this->batch1->id,
            'actual_qty'  => 60,
            'result_date' => now()->toDateString(),
        ]);
        QcResult::create([
            'production_result_id' => $res1->id,
            'good_qty'             => 60,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
        ]);

        // Batch 2 for Item 2: 40 pcs
        $this->batch2 = ProductionBatch::create([
            'batch_no'                                      => 'BAT-SUMM-02',
            'order_item_id'                                 => $item2->id,
            'target_qty'                                    => 40,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => '0.00',
            'direct_labor_cost_per_unit_snapshot'           => '5000.00',
            'packaging_process_cost_per_good_unit_snapshot' => '0.00',
            'other_direct_cost_per_unit_snapshot'           => '0.00',
            'material_usage_recorded_at'                    => now(),
        ]);
        $bm2 = BatchMaterial::create([
            'batch_id'      => $this->batch2->id,
            'material_id'   => $material->id,
            'required_qty'  => 40,
            'allocated_qty' => 40,
            'used_qty'      => 40,
        ]);
        StockMovement::create([
            'material_id'         => $material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => 40,
            'unit_cost_snapshot'  => '20000.0000',
            'total_cost_snapshot' => '800000.00',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm2->id,
            'movement_date'       => now()->toDateString(),
        ]);
        $res2 = ProductionResult::create([
            'batch_id'    => $this->batch2->id,
            'actual_qty'  => 40,
            'result_date' => now()->toDateString(),
        ]);
        QcResult::create([
            'production_result_id' => $res2->id,
            'good_qty'             => 40,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
        ]);

        // Business expense for this order: 200.000
        BusinessExpense::create([
            'order_id'     => $this->order->id,
            'expense_type' => ExpenseType::OPERATING,
            'amount'       => 200000,
            'description'  => 'Biaya kemasan khusus',
            'expense_date' => now()->toDateString(),
            'created_by'   => $this->owner->id,
        ]);
    }

    public function test_sold_quantity_aggregated_from_order_items(): void
    {
        $this->assertEquals(100, (int) $this->order->items->sum('qty_ordered'));
    }

    public function test_calculate_order_item_breakdown_method(): void
    {
        $service = app(OrderProfitabilityService::class);
        $breakdown = $service->calculateOrderItemBreakdown($this->order);

        $this->assertCount(2, $breakdown);
        $this->assertEquals('Hitam', $breakdown[0]['variant_name']);
        $this->assertEquals('PK-HTM-01', $breakdown[0]['sku']);
        $this->assertEquals('60.0000', $breakdown[0]['qty']);
        $this->assertEquals('50000.00', $breakdown[0]['unit_price']);
        $this->assertEquals('3000000.00', $breakdown[0]['subtotal']);

        $this->assertEquals('Mocca', $breakdown[1]['variant_name']);
        $this->assertEquals('PK-MCC-01', $breakdown[1]['sku']);
        $this->assertEquals('40.0000', $breakdown[1]['qty']);
        $this->assertEquals('50000.00', $breakdown[1]['unit_price']);
        $this->assertEquals('2000000.00', $breakdown[1]['subtotal']);
    }

    public function test_owner_order_profitability_view_renders_ringkasan_card_and_item_breakdown(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.profitability.show', $this->order));

        $response->assertOk();
        $response->assertViewIs('owner.profitability.order-show');

        // Ringkasan Penjualan & Laba
        $response->assertSee('Ringkasan Penjualan & Laba', false);
        $response->assertSee('Jumlah Terjual');
        $response->assertSee('100 pcs');
        $response->assertSee('Nilai Penjualan');
        $response->assertSee('Rp 5.000.000');
        $response->assertSee('Modal Produksi (HPP)');
        $response->assertSee('Rp 2.500.000'); // 1.500.000 + 1.000.000
        $response->assertSee('Laba Kotor');
        $response->assertSee('Rp 2.500.000'); // 5.000.000 - 2.500.000
        $response->assertSee('Beban Operasional');
        $response->assertSee('Rp 200.000');
        $response->assertSee('Laba Bersih');
        $response->assertSee('Rp 2.300.000'); // 2.500.000 - 200.000

        // Calculation flow text
        $response->assertSee('Alur Perhitungan:');
        $response->assertSee('Penjualan');
        $response->assertSee('Modal Produksi (HPP)');

        // Item breakdown table
        $response->assertSee('Rincian Item & Penjualan Produk', false);
        $response->assertSee('Pashmina Kaos');
        $response->assertSee('Hitam');
        $response->assertSee('PK-HTM-01');
        $response->assertSee('60 pcs');
        $response->assertSee('Rp 3.000.000');
        $response->assertSee('Mocca');
        $response->assertSee('PK-MCC-01');
        $response->assertSee('40 pcs');
        $response->assertSee('Rp 2.000.000');
    }

    public function test_revenue_uses_immutable_unit_selling_price_snapshots(): void
    {
        // Mutate the master product variant price
        $this->variant1->update(['base_selling_price' => 99999]);

        $service = app(OrderProfitabilityService::class);
        $profitability = $service->calculateOrderProfitability($this->order);
        $breakdown = $service->calculateOrderItemBreakdown($this->order);

        // Revenue must remain 5.000.000, not affected by variant master change
        $this->assertEquals('5000000.00', $profitability['revenue_amount']);
        $this->assertEquals('50000.00', $breakdown[0]['unit_price']);
        $this->assertEquals('3000000.00', $breakdown[0]['subtotal']);
    }

    public function test_period_financials_includes_total_qty_sold(): void
    {
        $service = app(PeriodFinancialService::class);
        $financials = $service->calculatePeriodFinancials(
            now('Asia/Jakarta')->toDateString(),
            now('Asia/Jakarta')->toDateString()
        );

        $this->assertEquals('100', $financials['total_qty_sold']);
    }

    public function test_owner_dashboard_displays_sold_quantity_and_removes_redundant_alur_laba_strip(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.dashboard', ['preset' => 'today']));

        $response->assertOk();
        $response->assertDontSee('Alur Laba');
        $response->assertDontSee('Total Barang Terjual:');
        $response->assertSee('100 pcs');
    }

    public function test_unauthorized_users_cannot_access_owner_profitability_detail(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->get(route('owner.profitability.show', $this->order));

        $response->assertForbidden();
    }

    public function test_unfinished_hpp_order_displays_belum_final_and_never_fake_zero_profit(): void
    {
        // Create an order without completed batches (unready HPP)
        $cust = Customer::create(['customer_name' => 'Pelanggan Baru', 'phone' => '081111111111']);
        $unreadyOrder = Order::create([
            'customer_id'  => $cust->id,
            'order_no'     => 'ORD-202609-UNFINISHED-01',
            'order_date'   => now()->toDateString(),
            'status'       => OrderStatus::PRODUKSI,
            'total_qty'    => 50,
            'total_amount' => 2500000,
        ]);
        $item = OrderItem::create([
            'order_id'           => $unreadyOrder->id,
            'variant_id'         => $this->variant1->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 50000,
            'subtotal'           => 2500000,
        ]);
        // Batch is still in progress (PRODUKSI, not SELESAI)
        ProductionBatch::create([
            'batch_no'      => 'BAT-UNFINISHED-01',
            'order_item_id' => $item->id,
            'target_qty'    => 50,
            'status'        => BatchStatus::PRODUKSI,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('owner.profitability.show', $unreadyOrder));

        $response->assertOk();
        // Displays Menunggu HPP Final & Belum Final
        $response->assertSee('Menunggu HPP Final');
        $response->assertSee('Laba Pesanan Belum Final');
        $response->assertSee('Belum Final');
        // Revenue is still shown accurately
        $response->assertSee('Rp 2.500.000');
        $response->assertSee('50 pcs');
    }
}
