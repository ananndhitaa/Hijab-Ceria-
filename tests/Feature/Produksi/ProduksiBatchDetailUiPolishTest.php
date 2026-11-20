<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchRoute;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
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
use App\Support\DateTimeFormatter;
use App\Support\QuantityFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduksiBatchDetailUiPolishTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private User $gudangUser;
    private ProductionBatch $batch;
    private BatchMaterial $bmKain;
    private BatchMaterial $bmBenang;
    private BatchMaterial $bmLabel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staff Produksi Test',
        ]);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staff Gudang Test',
        ]);

        $customer = Customer::create([
            'customer_name' => 'Toko Cinay Hijab',
            'phone'         => '081234567890',
        ]);

        $product = Product::create([
            'product_name' => 'Hijab Segi Empat Oval',
            'active'       => true,
        ]);

        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Latte',
            'sku'          => 'HJB-SO-LT-001',
            'active'       => true,
        ]);

        $unitM = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $unitRoll = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);
        $unitPcs = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $matKain = Material::create(['unit_id' => $unitM->id, 'material_name' => 'Kain Voal Premium']);
        $matBenang = Material::create(['unit_id' => $unitRoll->id, 'material_name' => 'Benang Jahit']);
        $matLabel = Material::create(['unit_id' => $unitPcs->id, 'material_name' => 'Label Brand Hijab']);

        MaterialStock::create(['material_id' => $matKain->id, 'qty_on_hand' => 100.0, 'stock_status' => 'AMAN']);
        MaterialStock::create(['material_id' => $matBenang->id, 'qty_on_hand' => 10.0, 'stock_status' => 'AMAN']);
        MaterialStock::create(['material_id' => $matLabel->id, 'qty_on_hand' => 200.0, 'stock_status' => 'AMAN']);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-202610-0003',
            'order_date'  => '2026-10-10',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 18,
            'unit_selling_price' => 55000,
        ]);

        $this->batch = ProductionBatch::create([
            'order_item_id'      => $orderItem->id,
            'batch_no'           => 'BAT-202610-0003',
            'target_qty'         => '18.0000',
            'planned_start_date' => '2026-10-10',
            'planned_end_date'   => '2026-10-25',
            'requires_printing'  => false,
            'status'             => BatchStatus::BAHAN,
        ]);

        $this->bmKain = BatchMaterial::create([
            'batch_id'     => $this->batch->id,
            'material_id'  => $matKain->id,
            'required_qty' => '23.4000',
            'allocated_qty'=> '23.4000',
        ]);

        $this->bmBenang = BatchMaterial::create([
            'batch_id'     => $this->batch->id,
            'material_id'  => $matBenang->id,
            'required_qty' => '0.3960',
            'allocated_qty'=> '0.3960',
        ]);

        $this->bmLabel = BatchMaterial::create([
            'batch_id'     => $this->batch->id,
            'material_id'  => $matLabel->id,
            'required_qty' => '18.0000',
            'allocated_qty'=> '18.0000',
        ]);
    }

    public function test_produksi_batch_detail_displays_feature_header_and_compact_status(): void
    {
        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $response->assertOk();

        // 1. Feature header contains batch number, product info, status and Kembalian button
        $response->assertSee('BAT-202610-0003');
        $response->assertSee('Hijab Segi Empat Oval');
        $response->assertSee('Latte');
        $response->assertSee('Kembali');

        // 2. Redundant intro copy removed
        $response->assertDontSee('Detail operasional batch, kesiapan material, dan rute pengerjaan.');

        // 3. Compact operational stage panel
        $response->assertSee('Tahap Saat Ini');
        $response->assertSee('Bahan siap digunakan.');
        $response->assertSee('Mulai Produksi');
        $response->assertDontSee('Bahan telah dialokasikan penuh. Batch ini memiliki rute');

        // 4. Batch summary uses clean labels
        $response->assertSee('Produk');
        $response->assertSee('Pesanan');
        $response->assertSee('Target');
        $response->assertSee('Target Selesai');
        $response->assertSee('Material');
        $response->assertSee('Siap');
        $response->assertDontSee('Kuantitas rencana');
        $response->assertDontSee('TERALOKASI LENGKAP');
    }

    public function test_production_target_date_remains_visible_for_produksi_and_hidden_for_gudang(): void
    {
        $formattedDate = DateTimeFormatter::formatDate($this->batch->planned_end_date, 'd M Y');

        // Produksi sees target date
        $prodResponse = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));
        $prodResponse->assertOk();
        $prodResponse->assertSee('Target Selesai');
        $prodResponse->assertSee($formattedDate);

        // Gudang material planning index and show do NOT see target date
        $gudangIndex = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.index'));
        $gudangIndex->assertOk();
        $gudangIndex->assertDontSee('Target Selesai');
        $gudangIndex->assertDontSee($formattedDate);

        $gudangShow = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.show', $this->batch));
        $gudangShow->assertOk();
        $gudangShow->assertDontSee('Target Selesai');
        $gudangShow->assertDontSee($formattedDate);
    }

    public function test_material_table_quantity_formatting_and_no_repeated_normal_status(): void
    {
        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $response->assertOk();

        // Title is Pemakaian Bahan, not Pemakaian Bahan Aktual
        $response->assertSee('Pemakaian Bahan');
        $response->assertDontSee('Perbandingan material yang dialokasikan Gudang');

        // Indonesian formatting without trailing zeros
        $response->assertSee('23,4 m');
        $response->assertSee('0,396 roll');
        $response->assertSee('18 pcs');

        // Raw database decimals must not leak
        $response->assertDontSee('23.4000 m');
        $response->assertDontSee('23,4000 m');
        $response->assertDontSee('0.3960 roll');
        $response->assertDontSee('0,3960 roll');
        $response->assertDontSee('18.0000 pcs');
        $response->assertDontSee('18,0000 pcs');

        // Normal rows have no repeated TERALOKASI badges
        $response->assertDontSee('TERALOKASI');
    }

    public function test_production_state_and_database_remain_unchanged(): void
    {
        $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $freshBatch = $this->batch->fresh();
        $this->assertSame(BatchStatus::BAHAN, $freshBatch->status);
        $this->assertSame('18.0000', $freshBatch->target_qty);
        $this->assertSame('2026-10-10', $freshBatch->planned_start_date->format('Y-m-d'));
        $this->assertSame('2026-10-25', $freshBatch->planned_end_date->format('Y-m-d'));

        $this->assertSame('23.4000', $this->bmKain->fresh()->required_qty);
        $this->assertSame('23.4000', $this->bmKain->fresh()->allocated_qty);
        $this->assertSame('0.3960', $this->bmBenang->fresh()->required_qty);
        $this->assertSame('0.3960', $this->bmBenang->fresh()->allocated_qty);
    }

    public function test_material_usage_badge_and_conditional_surplus_copy(): void
    {
        $this->batch->update([
            'material_usage_recorded_at' => now(),
            'material_usage_recorded_by' => $this->produksiUser->id,
        ]);

        // Case A: All used == allocated (surplus return = 0)
        $this->bmKain->update(['used_qty' => '23.4000']);
        $this->bmBenang->update(['used_qty' => '0.3960']);
        $this->bmLabel->update(['used_qty' => '18.0000']);

        $resA = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $resA->assertOk();
        $resA->assertSee('Pemakaian Final Tercatat');
        // Surplus note must NOT appear when there is no surplus
        $resA->assertDontSee('Sisa bahan yang tidak terpakai telah dikembalikan ke stok Gudang.');
        $resA->assertSee('Dicatat oleh:');
        $resA->assertSee('Staff Produksi Test');

        // Case B: Kain used < allocated (21.4 m used, 2 m surplus)
        $this->bmKain->update(['used_qty' => '21.4000']);

        $resB = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $resB->assertOk();
        $resB->assertSee('Pemakaian Final Tercatat');
        // Surplus note MUST appear when surplus exists
        $resB->assertSee('Sisa bahan yang tidak terpakai telah dikembalikan ke stok Gudang.');
        $resB->assertSee('2 m tersisa');
        $resB->assertSee('Kembali ke Gudang');
        $resB->assertSee('Dicatat oleh:');
        $resB->assertSee('Staff Produksi Test');
    }

    public function test_hasil_produksi_selisih_conditional_copy_and_typography(): void
    {
        $prodResult = ProductionResult::create([
            'batch_id'    => $this->batch->id,
            'actual_qty'  => 18,
            'result_date' => '2026-10-12',
            'notes'       => 'Hasil produksi sesuai target standar.',
            'recorded_by' => $this->produksiUser->id,
        ]);

        // Case 1: Exactly target (variance = 0)
        $resExact = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $resExact->assertOk();
        $resExact->assertSee('Hasil Produksi');
        $resExact->assertSee('0 pcs · Sesuai Target');
        $resExact->assertSee('Tanggal Hasil');
        $resExact->assertSee('Catatan Produksi:');
        $resExact->assertSee('Hasil produksi sesuai target standar.');
        $resExact->assertSee('Dicatat oleh:');
        $resExact->assertSee('Staff Produksi Test');

        // Case 2: Above target (+2 pcs)
        $prodResult->update(['actual_qty' => 20]);

        $resAbove = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $resAbove->assertOk();
        $resAbove->assertSee('+2 pcs · Melebihi Target');

        // Case 3: Below target (-2 pcs)
        $prodResult->update(['actual_qty' => 16]);

        $resBelow = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $resBelow->assertOk();
        $resBelow->assertSee('-2 pcs · Di Bawah Target');
    }

    public function test_qc_result_typography_and_good_rate(): void
    {
        $prodResult = ProductionResult::create([
            'batch_id'    => $this->batch->id,
            'actual_qty'  => 18,
            'result_date' => '2026-10-12',
            'recorded_by' => $this->produksiUser->id,
        ]);

        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => 16,
            'reject_qty'           => 2,
            'total_checked'        => 18,
            'qc_date'              => '2026-10-12',
            'notes'                => 'QC batch reguler',
            'qc_by'                => $this->produksiUser->id,
        ]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $this->batch));

        $response->assertOk();
        $response->assertSee('Hasil Quality Control');
        $response->assertSee('Good Rate: 88.9%');
        $response->assertSee('Good (Produk Baik)');
        $response->assertSee('Reject (Cacat/Afkir)');
        $response->assertSee('16 pcs');
        $response->assertSee('2 pcs');
        $response->assertSee('Diperiksa oleh:');
        $response->assertSee('Staff Produksi Test');
    }
}

