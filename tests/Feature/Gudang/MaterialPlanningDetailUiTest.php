<?php

namespace Tests\Feature\Gudang;

use App\Enums\BatchStatus;
use App\Enums\MaterialReadiness;
use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialPlanningDetailUiTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private ProductionBatch $batch;
    private Material $material;
    private Unit $meter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staff Gudang',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $customer = Customer::create([
            'customer_name' => 'Toko Cinay Hijab',
            'phone'         => '08123456789',
            'active'        => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202610-0003',
            'order_date'  => Carbon::create(2026, 10, 5),
            'status'      => \App\Enums\OrderStatus::PRODUKSI,
        ]);

        $product = Product::create([
            'product_name' => 'Hijab Segi Empat Oval',
            'description'  => 'Hijab premium',
            'active'       => true,
        ]);

        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Latte',
            'sku'          => 'HJB-SEO-LATTE',
            'price'        => 85000,
            'active'       => true,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 18,
            'unit_selling_price' => 85000,
        ]);

        $this->batch = ProductionBatch::create([
            'order_item_id'    => $orderItem->id,
            'batch_no'         => 'BAT-202610-0003',
            'target_qty'       => 18,
            'status'           => BatchStatus::BAHAN,
            'planned_end_date' => Carbon::create(2026, 10, 25),
        ]);

        $this->material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Ceruty Premium',
            'min_stock'     => '1.0000',
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->material->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '35000.0000',
        ]);
    }

    /**
     * 1, 9. Detail page HTTP 200 and standard Feature Header renders without redundant H1.
     */
    public function test_detail_page_renders_feature_header_cleanly(): void
    {
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => '20.0000',
            'allocated_qty' => '0.0000',
            'stock_at_calc' => '100.0000',
            'calculated_at' => Carbon::create(2026, 10, 8, 23, 12, 0, 'Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        $response->assertSee('Detail Perencanaan Bahan');
        $response->assertSee('BAT-202610-0003 · Hijab Segi Empat Oval - Latte');
        $response->assertSee('Kembali');
        $response->assertDontSee('Analisis ketersediaan dan alokasi bahan baku');
    }

    /**
     * 2-6. Batch, stock, required_qty, and allocated_qty remain untouched.
     */
    public function test_detail_page_does_not_mutate_batch_quantities_or_stock(): void
    {
        $bm = BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => '22.5000',
            'allocated_qty' => '0.0000',
            'stock_at_calc' => '100.0000',
        ]);

        $initialStock = $this->material->stock->qty_on_hand;

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        $this->assertEquals('22.5000', $bm->fresh()->required_qty);
        $this->assertEquals('0.0000', $bm->fresh()->allocated_qty);
        $this->assertEquals($initialStock, $this->material->fresh()->stock->qty_on_hand);
        $this->assertEquals(0, StockMovement::count());
    }

    /**
     * 3, 10. Allocated state displays compact status panel without duplicate verbose copy.
     */
    public function test_allocated_batch_displays_compact_status_panel(): void
    {
        $bm = BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => '20.0000',
            'allocated_qty' => '20.0000',
            'stock_at_calc' => '100.0000',
        ]);

        $sm = StockMovement::create([
            'material_id'         => $this->material->id,
            'movement_type'       => MovementType::OUT,
            'qty'                 => '20.0000',
            'unit_cost_snapshot'  => '35000.0000',
            'total_cost_snapshot' => '700000.00',
            'qty_before'          => '100.0000',
            'qty_after'           => '80.0000',
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'movement_date'       => '2026-10-09',
            'notes'               => 'Alokasi bahan batch',
            'created_by'          => $this->gudangUser->id,
        ]);
        StockMovement::where('id', $sm->id)->update([
            'created_at' => Carbon::create(2026, 10, 9, 9, 9, 0, 'UTC'),
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        $response->assertSee('Status Material');
        $response->assertSee('Teralokasi');
        $response->assertSee('Dialokasikan:');
        $response->assertSee('09 Okt 2026, 16:09 WIB');
        $response->assertSee('Staff Gudang');

        // Verbose duplicate paragraph is eliminated
        $response->assertDontSee('Seluruh kebutuhan material batch telah dikeluarkan dari stok Gudang dan dialokasikan ke batch produksi.');
    }

    /**
     * 7, 8. Historical planning snapshot preserved and collapsed by default.
     */
    public function test_historical_snapshot_is_preserved_and_collapsed_by_default(): void
    {
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => '20.0000',
            'allocated_qty' => '0.0000',
            'stock_at_calc' => '95.5000',
            'shortage_qty'  => '0.0000',
            'calculated_at' => Carbon::create(2026, 10, 8, 23, 12, 0, 'Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        $response->assertSee('Riwayat Perencanaan Awal');
        $response->assertSee('Snapshot saat perencanaan dibuat.');
        $response->assertSee('showHistory: false'); // Collapsed by default in x-data
        $response->assertSee('95,50 m'); // historical stock preserved
        $response->assertDontSee('Nilai awal disimpan sebagai audit histori perencanaan');
    }

    /**
     * 11. Production target date is hidden from Gudang (Target Batch qty visible without deadline).
     */
    public function test_production_target_date_is_hidden_from_gudang(): void
    {
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => '20.0000',
            'allocated_qty' => '0.0000',
            'stock_at_calc' => '100.0000',
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        $response->assertDontSee('25 Okt 2026'); // planned_end_date is hidden from Gudang
        $response->assertDontSee('Target Selesai');
        $response->assertSee('Target Batch');
        $response->assertSee('18 pcs');
    }

    /**
     * 12. Allocation guards remain strictly active.
     */
    public function test_allocation_guards_and_actions_remain_intact(): void
    {
        // Material with shortage cannot be allocated
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => '200.0000', // exceeds 100m stock
            'allocated_qty' => '0.0000',
            'stock_at_calc' => '100.0000',
            'shortage_qty'  => '100.0000',
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        $response->assertSee('Kekurangan Bahan');
        $response->assertSee('Buat Purchase Order');
        $response->assertDontSee('Alokasikan Bahan');
    }

    /**
     * 13. Material table uses exception-based UI and em-dash for zero shortage without repeated 'Teralokasi' rows.
     */
    public function test_material_table_uses_exception_based_ui_and_em_dash_for_zero_shortage(): void
    {
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => '20.0000',
            'allocated_qty' => '20.0000',
            'stock_at_calc' => '100.0000',
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        // Em dash for zero shortage
        $response->assertSee('—');
        $response->assertDontSee('0 pcs');
        $response->assertDontSee('0 roll');

        // Short unit canonical symbol
        $response->assertSee('m');
        $response->assertDontSee('Meter (m)');

        // History table container has display none by default
        $response->assertSee('style="display: none;"', false);
    }

    /**
     * 14. Exceptions are highlighted with badge, while normal rows have no badge.
     */
    public function test_exceptions_highlighted_with_badge_while_normal_rows_remain_clean(): void
    {
        $material2 = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Benang Jahit',
            'min_stock'     => '1.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material2->id,
            'qty_on_hand'   => '5.0000',
            'avg_unit_cost' => '10000.0000',
        ]);

        // Row 1: Sufficient (Normal)
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => '10.0000',
            'allocated_qty' => '0.0000',
            'stock_at_calc' => '100.0000',
        ]);

        // Row 2: Shortage (Exception)
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $material2->id,
            'required_qty'  => '20.0000', // needs 20m, only 5m in stock -> short 15m
            'allocated_qty' => '0.0000',
            'stock_at_calc' => '5.0000',
            'shortage_qty'  => '15.0000',
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));

        $response->assertOk();
        // Exception row has badge
        $response->assertSee('Kurang');
        $response->assertSee('15 m');
    }
}
