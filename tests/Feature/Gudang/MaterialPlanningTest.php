<?php

namespace Tests\Feature\Gudang;

use App\Enums\BatchStatus;
use App\Enums\MaterialReadiness;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\BomItem;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialPlanningTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private User $ownerUser;
    private User $produksiUser;
    private User $customerUser;
    private Unit $meterUnit;
    private ProductVariant $variant;
    private Material $material;
    private ProductionBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->ownerUser = User::factory()->create([
            'role'   => UserRole::OWNER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $product = Product::create(['product_name' => 'Hijab Segiempat Voal', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Navy',
            'sku'                => 'HJB-SGM-NVY',
            'base_selling_price' => 60000,
            'active'             => true,
        ]);

        $this->material = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Voal Navy',
            'min_stock'     => 50,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $this->material->id, 'qty_on_hand' => 100]);

        $customer = Customer::create(['customer_name' => 'Customer Budi', 'phone' => '0811223344']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-2026-001',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::BAHAN,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 60000,
        ]);

        $this->batch = ProductionBatch::create([
            'order_item_id' => $orderItem->id,
            'batch_no'      => 'BAT-2026-001',
            'target_qty'    => 100,
            'status'        => BatchStatus::BAHAN,
        ]);
    }

    public function test_gudang_can_view_material_planning_index(): void
    {
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index'));
        $response->assertOk();
        $response->assertSee('Perencanaan Kebutuhan Bahan (MRP)');
        $response->assertSee('Kesiapan Bahan');
        $response->assertSee('BAT-2026-001');
    }

    /**
     * Requirement A: MRP exists + live shortage > 0 -> PERLU PEMBELIAN.
     */
    public function test_mrp_exists_and_live_shortage_greater_than_zero_shows_perlu_pembelian(): void
    {
        // Batch requires 100m, but live stock is only 30m -> shortage 70m
        $this->material->stock->update(['qty_on_hand' => 30]);

        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 100,
            'stock_at_calc' => 30,
            'shortage_qty'  => 70,
            'allocated_qty' => 0,
        ]);

        $this->assertSame(MaterialReadiness::PERLU_PEMBELIAN, $this->batch->fresh()->material_readiness);

        $indexRes = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('PERLU PEMBELIAN');

        $showRes = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.show', $this->batch));
        $showRes->assertOk();
        $showRes->assertSee('PERLU PEMBELIAN');
        $showRes->assertSee('Buat Purchase Order');
        $showRes->assertDontSee('Alokasikan Bahan');
    }

    /**
     * Requirement B: MRP exists + all live shortage = 0 + not allocated -> SIAP DIALOKASIKAN.
     */
    public function test_mrp_exists_and_all_live_shortage_zero_and_not_allocated_shows_siap_dialokasikan(): void
    {
        // Batch requires 80m, live stock is 100m -> shortage 0m
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 80,
            'stock_at_calc' => 100,
            'shortage_qty'  => 0,
            'allocated_qty' => 0,
        ]);

        $this->assertSame(MaterialReadiness::SIAP_DIALOKASIKAN, $this->batch->fresh()->material_readiness);

        $indexRes = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('SIAP DIALOKASIKAN');

        $showRes = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.show', $this->batch));
        $showRes->assertOk();
        $showRes->assertSee('SIAP DIALOKASIKAN');
        $showRes->assertSee('Alokasikan Bahan');
    }

    /**
     * Requirement C: all allocated_qty == required_qty -> TERALOKASI.
     */
    public function test_all_allocated_qty_equals_required_qty_shows_teralokasi(): void
    {
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 100,
            'stock_at_calc' => 100,
            'shortage_qty'  => 0,
            'allocated_qty' => 100,
        ]);

        $this->assertSame(MaterialReadiness::TERALOKASI, $this->batch->fresh()->material_readiness);

        $indexRes = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('TERALOKASI');

        $showRes = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.show', $this->batch));
        $showRes->assertOk();
        $showRes->assertSee('TERALOKASI');
        $showRes->assertSee('Bahan Telah Dialokasikan');
        $showRes->assertSee('Seluruh bahan telah teralokasi');
        $showRes->assertDontSee('Alokasikan Bahan');
        $showRes->assertDontSee('Buat Purchase Order');
    }

    /**
     * Requirement D: Fully allocated Batch remains TERALOKASI even if warehouse stock later drops.
     */
    public function test_fully_allocated_batch_remains_teralokasi_even_if_warehouse_stock_later_drops(): void
    {
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 100,
            'stock_at_calc' => 100,
            'shortage_qty'  => 0,
            'allocated_qty' => 100,
        ]);

        // Warehouse stock later drops to 0
        $this->material->stock->update(['qty_on_hand' => 0]);

        $freshBatch = $this->batch->fresh();
        $this->assertSame(MaterialReadiness::TERALOKASI, $freshBatch->material_readiness);

        // Filter TERALOKASI still includes it
        $responseTeralokasi = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.index', ['readiness_status' => 'TERALOKASI']));
        $responseTeralokasi->assertOk();
        $responseTeralokasi->assertSee($this->batch->batch_no);

        // Filter PERLU_PEMBELIAN does NOT include it
        $responsePerlu = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.index', ['readiness_status' => 'PERLU_PEMBELIAN']));
        $responsePerlu->assertOk();
        $responsePerlu->assertDontSee($this->batch->batch_no);
    }

    /**
     * Requirement E: Historical shortage_qty remains unchanged.
     */
    public function test_historical_shortage_qty_remains_unchanged_when_warehouse_stock_changes(): void
    {
        // Batch created when stock was 20 -> historical shortage is 80
        $bm = BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 100,
            'stock_at_calc' => 20,
            'shortage_qty'  => 80,
            'allocated_qty' => 0,
        ]);

        // Later warehouse receives goods and stock becomes 200
        $this->material->stock->update(['qty_on_hand' => 200]);

        // Historical snapshot on BatchMaterial MUST NOT CHANGE
        $bmFresh = $bm->fresh();
        $this->assertSame('20.0000', (string) $bmFresh->stock_at_calc);
        $this->assertSame('80.0000', (string) $bmFresh->shortage_qty);

        // But operational readiness reflects current live stock -> SIAP_DIALOKASIKAN
        $this->assertSame(MaterialReadiness::SIAP_DIALOKASIKAN, $this->batch->fresh()->material_readiness);
    }

    /**
     * Requirement F, G, H: Primary Filters (PERLU_PEMBELIAN, SIAP_DIALOKASIKAN, TERALOKASI) work authoritatively.
     */
    public function test_filter_perlu_pembelian_works(): void
    {
        // Batch 1: Perlu Pembelian (stock 10 < required 100)
        $this->material->stock->update(['qty_on_hand' => 10]);
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 100,
            'stock_at_calc' => 10,
            'shortage_qty'  => 90,
            'allocated_qty' => 0,
        ]);

        // Batch 2: Siap Dialokasikan (needs other material with sufficient stock)
        $mat2 = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Furing',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $mat2->id, 'qty_on_hand' => 200]);

        $batch2 = ProductionBatch::create([
            'order_item_id' => $this->batch->order_item_id,
            'batch_no'      => 'BAT-2026-002',
            'target_qty'    => 50,
            'status'        => BatchStatus::BAHAN,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch2->id,
            'material_id'   => $mat2->id,
            'required_qty'  => 50,
            'stock_at_calc' => 200,
            'shortage_qty'  => 0,
            'allocated_qty' => 0,
        ]);

        // Batch 3: Teralokasi
        $batch3 = ProductionBatch::create([
            'order_item_id' => $this->batch->order_item_id,
            'batch_no'      => 'BAT-2026-003',
            'target_qty'    => 30,
            'status'        => BatchStatus::BAHAN,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch3->id,
            'material_id'   => $mat2->id,
            'required_qty'  => 30,
            'stock_at_calc' => 200,
            'shortage_qty'  => 0,
            'allocated_qty' => 30,
        ]);

        // Filter: PERLU_PEMBELIAN
        $res = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.index', ['readiness_status' => 'PERLU_PEMBELIAN']));
        $res->assertOk();
        $res->assertSee('BAT-2026-001');
        $res->assertDontSee('BAT-2026-002');
        $res->assertDontSee('BAT-2026-003');
    }

    public function test_filter_siap_dialokasikan_works(): void
    {
        // Batch 1: Perlu Pembelian
        $this->material->stock->update(['qty_on_hand' => 10]);
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 100,
            'stock_at_calc' => 10,
            'shortage_qty'  => 90,
            'allocated_qty' => 0,
        ]);

        // Batch 2: Siap Dialokasikan
        $mat2 = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Katun',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $mat2->id, 'qty_on_hand' => 200]);

        $batch2 = ProductionBatch::create([
            'order_item_id' => $this->batch->order_item_id,
            'batch_no'      => 'BAT-2026-002',
            'target_qty'    => 50,
            'status'        => BatchStatus::BAHAN,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch2->id,
            'material_id'   => $mat2->id,
            'required_qty'  => 50,
            'stock_at_calc' => 200,
            'shortage_qty'  => 0,
            'allocated_qty' => 0,
        ]);

        // Batch 3: Teralokasi
        $batch3 = ProductionBatch::create([
            'order_item_id' => $this->batch->order_item_id,
            'batch_no'      => 'BAT-2026-003',
            'target_qty'    => 30,
            'status'        => BatchStatus::BAHAN,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch3->id,
            'material_id'   => $mat2->id,
            'required_qty'  => 30,
            'stock_at_calc' => 200,
            'shortage_qty'  => 0,
            'allocated_qty' => 30,
        ]);

        // Filter: SIAP_DIALOKASIKAN
        $res = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.index', ['readiness_status' => 'SIAP_DIALOKASIKAN']));
        $res->assertOk();
        $res->assertSee('BAT-2026-002');
        $res->assertDontSee('BAT-2026-001');
        $res->assertDontSee('BAT-2026-003');
    }

    public function test_filter_teralokasi_works(): void
    {
        // Batch 1: Perlu Pembelian
        $this->material->stock->update(['qty_on_hand' => 10]);
        BatchMaterial::create([
            'batch_id'      => $this->batch->id,
            'material_id'   => $this->material->id,
            'required_qty'  => 100,
            'stock_at_calc' => 10,
            'shortage_qty'  => 90,
            'allocated_qty' => 0,
        ]);

        // Batch 2: Siap Dialokasikan
        $mat2 = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Satin',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $mat2->id, 'qty_on_hand' => 200]);

        $batch2 = ProductionBatch::create([
            'order_item_id' => $this->batch->order_item_id,
            'batch_no'      => 'BAT-2026-002',
            'target_qty'    => 50,
            'status'        => BatchStatus::BAHAN,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch2->id,
            'material_id'   => $mat2->id,
            'required_qty'  => 50,
            'stock_at_calc' => 200,
            'shortage_qty'  => 0,
            'allocated_qty' => 0,
        ]);

        // Batch 3: Teralokasi
        $batch3 = ProductionBatch::create([
            'order_item_id' => $this->batch->order_item_id,
            'batch_no'      => 'BAT-2026-003',
            'target_qty'    => 30,
            'status'        => BatchStatus::BAHAN,
        ]);
        BatchMaterial::create([
            'batch_id'      => $batch3->id,
            'material_id'   => $mat2->id,
            'required_qty'  => 30,
            'stock_at_calc' => 200,
            'shortage_qty'  => 0,
            'allocated_qty' => 30,
        ]);

        // Filter: TERALOKASI
        $res = $this->actingAs($this->gudangUser)
            ->get(route('gudang.material-planning.index', ['readiness_status' => 'TERALOKASI']));
        $res->assertOk();
        $res->assertSee('BAT-2026-003');
        $res->assertDontSee('BAT-2026-001');
        $res->assertDontSee('BAT-2026-002');
    }

    /**
     * Requirement I: No normal "Belum Dihitung (Legacy)" filter remains.
     */
    public function test_no_normal_belum_dihitung_legacy_filter_remains_in_ui(): void
    {
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index'));
        $response->assertOk();
        $response->assertDontSee('Belum Dihitung (Legacy)');
        $response->assertDontSee('Semua Status MRP');
        $response->assertDontSee('Sudah Dihitung');

        $response->assertSee('Semua Kesiapan Bahan');
        $response->assertSee('Perlu Pembelian');
        $response->assertSee('Siap Dialokasikan');
        $response->assertSee('Teralokasi');
    }

    public function test_exceptional_batch_without_snapshot_shows_perencanaan_belum_tersedia(): void
    {
        // Batch without batchMaterials snapshot
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index'));
        $response->assertOk();
        $response->assertSee('PERENCANAAN BELUM TERSEDIA');

        $showResponse = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.show', $this->batch));
        $showResponse->assertOk();
        $showResponse->assertSee('Perencanaan Belum Tersedia');
    }

    public function test_non_gudang_cannot_access_or_calculate_material_planning(): void
    {
        $resAdmin = $this->actingAs($this->adminUser)->get(route('gudang.material-planning.index'));
        $resAdmin->assertForbidden();

        $resAdminPost = $this->actingAs($this->adminUser)->post(route('gudang.material-planning.calculate', $this->batch));
        $resAdminPost->assertForbidden();

        $resOwner = $this->actingAs($this->ownerUser)->get(route('gudang.material-planning.index'));
        $resOwner->assertForbidden();

        $resProduksi = $this->actingAs($this->produksiUser)->get(route('gudang.material-planning.index'));
        $resProduksi->assertForbidden();

        $resCustomer = $this->actingAs($this->customerUser)->get(route('gudang.material-planning.index'));
        $resCustomer->assertForbidden();
    }

    public function test_default_view_prioritizes_active_batches_and_history_accessible(): void
    {
        // Create a completed batch
        $completedBatch = ProductionBatch::create([
            'order_item_id' => $this->batch->order_item_id,
            'batch_no'      => 'BAT-COMPLETED-999',
            'target_qty'    => 50,
            'status'        => BatchStatus::SELESAI,
        ]);

        // Default view: shows all batches with active appearing first
        $resDefault = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index'));
        $resDefault->assertOk();
        $resDefault->assertSee($this->batch->batch_no);
        $resDefault->assertSee($completedBatch->batch_no);

        // Active appears before completed in HTML
        $posActive = strpos($resDefault->getContent(), $this->batch->batch_no);
        $posCompleted = strpos($resDefault->getContent(), $completedBatch->batch_no);
        $this->assertLessThan($posCompleted, $posActive, 'Active batch must appear before completed batch');

        // Filter Aktif: active visible, completed hidden
        $resAktif = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index', ['batch_status' => 'AKTIF']));
        $resAktif->assertOk();
        $resAktif->assertSee($this->batch->batch_no);
        $resAktif->assertDontSee($completedBatch->batch_no);

        // Filter Selesai: completed visible, active hidden
        $resSelesai = $this->actingAs($this->gudangUser)->get(route('gudang.material-planning.index', ['batch_status' => 'SELESAI']));
        $resSelesai->assertOk();
        $resSelesai->assertSee($completedBatch->batch_no);
        $resSelesai->assertDontSee($this->batch->batch_no);
    }
}
