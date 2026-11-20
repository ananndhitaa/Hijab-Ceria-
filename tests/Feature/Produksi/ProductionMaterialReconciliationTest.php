<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchStatus;
use App\Enums\MovementType;
use App\Enums\OrderStatus;
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
use App\Models\Production\ProductionResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Finance\BatchCostCalculationService;
use App\Services\Inventory\MaterialAllocationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionMaterialReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private User $adminUser;
    private User $gudangUser;
    private Customer $customer;
    private Unit $meterUnit;
    private Unit $pcsUnit;
    private Material $kainVoal;
    private MaterialStock $kainStock;
    private Material $benang;
    private MaterialStock $benangStock;
    private Material $label;
    private MaterialStock $labelStock;
    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staf Produksi',
        ]);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Admin Nayara',
        ]);

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
            'name'   => 'Staf Gudang',
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Butik Nabila',
            'phone'         => '08123456789',
        ]);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->pcsUnit = Unit::create(['unit_name' => 'Pcs', 'symbol' => 'pcs']);

        $this->kainVoal = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Voal',
            'cost_per_unit' => 25000,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->kainStock = MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => 30.0000,
            'avg_unit_cost' => 25000.0000,
        ]);

        $this->benang = Material::create([
            'unit_id'       => $this->pcsUnit->id,
            'material_name' => 'Benang Jahit',
            'cost_per_unit' => 5000,
            'min_stock'     => 5,
            'active'        => true,
        ]);
        $this->benangStock = MaterialStock::create([
            'material_id'   => $this->benang->id,
            'qty_on_hand'   => 20.0000,
            'avg_unit_cost' => 5000.0000,
        ]);

        $this->label = Material::create([
            'unit_id'       => $this->pcsUnit->id,
            'material_name' => 'Label Nayara',
            'cost_per_unit' => 1000,
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->labelStock = MaterialStock::create([
            'material_id'   => $this->label->id,
            'qty_on_hand'   => 50.0000,
            'avg_unit_cost' => 1000.0000,
        ]);

        $this->product = Product::create([
            'product_name' => 'Hijab Segiempat Voal',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'HJB-VOAL-DPK',
            'requires_printing' => false,
            'active'            => true,
        ]);
    }

    private function createBatchWithMaterial(
        float $targetQty = 50,
        float $allocatedQty = 100.0,
        float $initialStock = 30.0
    ): array {
        $this->kainStock->update(['qty_on_hand' => $initialStock]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 75000,
            'subtotal'           => $targetQty * 75000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-' . uniqid(),
            'target_qty'             => $targetQty,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kainVoal->id,
            'required_qty'  => $allocatedQty,
            'allocated_qty' => $allocatedQty,
            'used_qty'      => 0.0000,
        ]);

        // Simulasikan mutasi OUT awal dari alokasi gudang
        StockMovement::create([
            'material_id'         => $this->kainVoal->id,
            'movement_type'       => MovementType::OUT,
            'movement_date'       => now()->toDateString(),
            'qty'                 => $allocatedQty,
            'unit_cost_snapshot'  => 25000.0000,
            'total_cost_snapshot' => $allocatedQty * 25000.0000,
            'qty_before'          => $initialStock + $allocatedQty,
            'qty_after'           => $initialStock,
            'reference_type'      => 'batch_materials',
            'reference_id'        => $bm->id,
            'notes'               => "Alokasi material untuk {$batch->batch_no}",
            'created_by'          => $this->gudangUser->id,
        ]);

        return [$batch, $order, $item, $bm];
    }

    // =========================================================================
    // NORMAL: EXACT USAGE
    // =========================================================================

    public function test_normal_exact_usage_produces_no_stock_movement(): void
    {
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);
        $initialMovementsCount = StockMovement::count();

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'notes'          => 'Produksi selesai tepat alokasi',
                'material_usage' => [$bm->id => 100.0],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals('100.0000', $bm->fresh()->used_qty);
        $this->assertEquals('30.0000', $this->kainStock->fresh()->qty_on_hand);

        // Tidak ada StockMovement baru
        $this->assertEquals($initialMovementsCount, StockMovement::count());
    }

    // =========================================================================
    // RETURN: ACTUAL < ALLOCATION
    // =========================================================================

    public function test_return_surplus_creates_stock_movement_in_and_increases_warehouse_stock(): void
    {
        // Allocated: 100 m, Used: 92 m -> Return: 8 m. Stock before: 30 m -> after: 38 m.
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'notes'          => 'Pemakaian hemat kain',
                'material_usage' => [$bm->id => 92.0],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals('92.0000', $bm->fresh()->used_qty);

        // Stok gudang bertambah dari 30 -> 38
        $this->assertEquals('38.0000', $this->kainStock->fresh()->qty_on_hand);

        // StockMovement IN tercatat
        $returnMovement = StockMovement::where('movement_type', MovementType::IN)
            ->where('reference_type', 'production_batches')
            ->where('reference_id', $batch->id)
            ->first();

        $this->assertNotNull($returnMovement);
        $this->assertEquals('8.0000', $returnMovement->qty);
        $this->assertEquals('30.0000', $returnMovement->qty_before);
        $this->assertEquals('38.0000', $returnMovement->qty_after);
        $this->assertEquals($this->produksiUser->id, $returnMovement->created_by);
        $this->assertStringContainsString('Pengembalian sisa bahan', $returnMovement->notes);
    }

    public function test_return_cost_does_not_corrupt_mwa(): void
    {
        // Returning unused allocated quantity must not recalculate moving weighted average
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);
        $initialAvgCost = $this->kainStock->fresh()->avg_unit_cost;

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 92.0],
            ]);

        $this->assertEquals($initialAvgCost, $this->kainStock->fresh()->avg_unit_cost);
    }

    public function test_return_cannot_execute_twice_idempotency(): void
    {
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        // Submit pertama kali: status batch pindah ke QC
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 92.0],
            ]);

        $this->assertEquals('38.0000', $this->kainStock->fresh()->qty_on_hand);
        $movementCountAfterFirst = StockMovement::count();

        // Submit kedua kali (sudah di QC): harus diblokir oleh BatchWorkflowService
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 92.0],
            ]);

        $response->assertSessionHas('error');
        // Stok tidak berubah lagi
        $this->assertEquals('38.0000', $this->kainStock->fresh()->qty_on_hand);
        // Tidak ada StockMovement duplikat
        $this->assertEquals($movementCountAfterFirst, StockMovement::count());
    }

    // =========================================================================
    // ADDITIONAL MATERIAL: ACTUAL > ALLOCATION
    // =========================================================================

    public function test_direct_final_usage_exceeding_allocation_is_blocked(): void
    {
        // Allocated: 100 m, input: 105 m -> diblokir
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 105.0],
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak boleh melebihi kuantitas yang dialokasikan', session('error'));
        // Status batch tetap PRODUKSI
        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        // Pemakaian belum tercatat
        $this->assertEquals('0.0000', $bm->fresh()->used_qty);
        // Stok gudang tidak tersentuh
        $this->assertEquals('30.0000', $this->kainStock->fresh()->qty_on_hand);
    }

    public function test_additional_allocation_deducts_stock_and_increases_batch_allocation(): void
    {
        // Allocated: 100 m, Warehouse Stock: 30 m, Tambahan: 5 m
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 5.0,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Alokasi batch menjadi 105 m
        $this->assertEquals('105.0000', $bm->fresh()->allocated_qty);

        // Stok gudang berkurang dari 30 -> 25 m
        $this->assertEquals('25.0000', $this->kainStock->fresh()->qty_on_hand);

        // Mutasi OUT tercatat dengan referensi production_batches
        $outMovement = StockMovement::where('movement_type', MovementType::OUT)
            ->where('reference_type', 'production_batches')
            ->where('reference_id', $batch->id)
            ->first();

        $this->assertNotNull($outMovement);
        $this->assertEquals('5.0000', $outMovement->qty);
        $this->assertEquals('30.0000', $outMovement->qty_before);
        $this->assertEquals('25.0000', $outMovement->qty_after);
        $this->assertEquals($this->produksiUser->id, $outMovement->created_by);
        $this->assertStringContainsString('Tambahan alokasi bahan', $outMovement->notes);
    }

    public function test_after_additional_allocation_user_can_finalize_usage(): void
    {
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        // Tambah bahan 5 m dari gudang
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 5.0,
            ]);

        $this->assertEquals('105.0000', $bm->fresh()->allocated_qty);

        // Sekarang finalisasi dengan 105 m diizinkan
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 105.0],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals('105.0000', $bm->fresh()->used_qty);
        // Stok tetap 25 (tidak ada double OUT)
        $this->assertEquals('25.0000', $this->kainStock->fresh()->qty_on_hand);
    }

    public function test_additional_allocation_rejects_negative_or_zero_qty(): void
    {
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        $responseZero = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 0,
            ]);
        $responseZero->assertSessionHasErrors('additional_qty');

        $responseNegative = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => -5,
            ]);
        $responseNegative->assertSessionHasErrors('additional_qty');

        // Alokasi dan stok tidak berubah
        $this->assertEquals('100.0000', $bm->fresh()->allocated_qty);
        $this->assertEquals('30.0000', $this->kainStock->fresh()->qty_on_hand);
    }

    // =========================================================================
    // INSUFFICIENT WAREHOUSE STOCK
    // =========================================================================

    public function test_additional_allocation_blocked_when_warehouse_stock_insufficient(): void
    {
        // Butuh tambahan: 5 m, Stok Gudang: 3 m -> diblokir, kekurangan 2 m
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 3.0);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 5.0,
            ]);

        $response->assertSessionHas('error');
        $errorMessage = session('error');
        $this->assertStringContainsString('Stok Gudang tidak mencukupi', $errorMessage);
        $this->assertStringContainsString('Kekurangan 2', $errorMessage);

        // Stok tidak boleh negatif!
        $this->assertEquals('3.0000', $this->kainStock->fresh()->qty_on_hand);
        // Alokasi tidak bertambah
        $this->assertEquals('100.0000', $bm->fresh()->allocated_qty);
    }

    // =========================================================================
    // MULTIPLE MATERIALS RECONCILIATION
    // =========================================================================

    public function test_multiple_materials_reconcile_independently(): void
    {
        // Batch dengan 3 bahan:
        // 1. Kain Voal: allocated 100, used 92 -> return 8 m (Stock IN 8)
        // 2. Benang:    allocated 5,   used 5  -> no movement
        // 3. Label:     allocated 50,  butuh 52 -> tambah 2 (Stock OUT 2), then used 52 -> no return
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-MULTI',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRODUKSI,
        ]);
        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variant->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 75000,
            'subtotal'           => 50 * 75000,
        ]);
        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-MULTI',
            'target_qty'             => 50,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bmKain = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kainVoal->id,
            'required_qty'  => 100.0,
            'allocated_qty' => 100.0,
            'used_qty'      => 0.0,
        ]);
        $bmBenang = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->benang->id,
            'required_qty'  => 5.0,
            'allocated_qty' => 5.0,
            'used_qty'      => 0.0,
        ]);
        $bmLabel = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->label->id,
            'required_qty'  => 50.0,
            'allocated_qty' => 50.0,
            'used_qty'      => 0.0,
        ]);

        // Gudang stock: Kain 30, Benang 20, Label 50
        $this->kainStock->update(['qty_on_hand' => 30.0]);
        $this->benangStock->update(['qty_on_hand' => 20.0]);
        $this->labelStock->update(['qty_on_hand' => 50.0]);

        // Step 1: Tambah bahan Label 2 pcs dari gudang
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bmLabel]), [
                'additional_qty' => 2.0,
            ]);

        $this->assertEquals('52.0000', $bmLabel->fresh()->allocated_qty);
        $this->assertEquals('48.0000', $this->labelStock->fresh()->qty_on_hand);

        // Step 2: Finalisasi pemakaian semua bahan
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [
                    $bmKain->id   => 92.0,
                    $bmBenang->id => 5.0,
                    $bmLabel->id  => 52.0,
                ],
            ]);

        // Kain: sisa 8 m dikembalikan -> stok kain jadi 30 + 8 = 38 m
        $this->assertEquals('38.0000', $this->kainStock->fresh()->qty_on_hand);
        $this->assertEquals('92.0000', $bmKain->fresh()->used_qty);

        // Benang: pas 5 -> stok benang tetap 20
        $this->assertEquals('20.0000', $this->benangStock->fresh()->qty_on_hand);
        $this->assertEquals('5.0000', $bmBenang->fresh()->used_qty);

        // Label: pas 52 -> stok label tetap 48
        $this->assertEquals('48.0000', $this->labelStock->fresh()->qty_on_hand);
        $this->assertEquals('52.0000', $bmLabel->fresh()->used_qty);

        // Periksa mutasi pengembalian kain
        $kainReturn = StockMovement::where('movement_type', MovementType::IN)
            ->where('material_id', $this->kainVoal->id)
            ->where('reference_type', 'production_batches')
            ->first();
        $this->assertNotNull($kainReturn);
        $this->assertEquals('8.0000', $kainReturn->qty);
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    public function test_unauthorized_role_cannot_request_additional_allocation(): void
    {
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        // Unauthenticated
        $responseGuest = $this->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
            'additional_qty' => 5.0,
        ]);
        $responseGuest->assertRedirect('/login');

        // Gudang user trying to access production route
        $responseGudang = $this->actingAs($this->gudangUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 5.0,
            ]);
        $responseGudang->assertForbidden();

        // Produksi user can access
        $responseProduksi = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 5.0,
            ]);
        $responseProduksi->assertRedirect();
        $responseProduksi->assertSessionHas('success');
    }

    // =========================================================================
    // AUDIT & REVIEWS
    // =========================================================================

    public function test_audit_fields_recorded_accurately(): void
    {
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        // Additional allocation
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 5.0,
            ]);

        $addMovement = StockMovement::where('movement_type', MovementType::OUT)
            ->where('reference_type', 'production_batches')
            ->where('reference_id', $batch->id)
            ->first();

        $this->assertNotNull($addMovement);
        $this->assertEquals($this->produksiUser->id, $addMovement->created_by);
        $this->assertEquals($batch->id, $addMovement->reference_id);
        $this->assertEquals('30.0000', $addMovement->qty_before);
        $this->assertEquals('25.0000', $addMovement->qty_after);
        $this->assertNotNull($addMovement->created_at);

        // Return surplus
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 95.0],
            ]);

        $returnMovement = StockMovement::where('movement_type', MovementType::IN)
            ->where('reference_type', 'production_batches')
            ->where('reference_id', $batch->id)
            ->first();

        $this->assertNotNull($returnMovement);
        $this->assertEquals($this->produksiUser->id, $returnMovement->created_by);
        $this->assertEquals($batch->id, $returnMovement->reference_id);
        // After previous deduction to 25, return of 10 m (105 - 95 = 10) takes stock from 25 to 35
        $this->assertEquals('25.0000', $returnMovement->qty_before);
        $this->assertEquals('35.0000', $returnMovement->qty_after);
        $this->assertEquals('10.0000', $returnMovement->qty);
    }

    // =========================================================================
    // REGRESSION & HPP SAFETY
    // =========================================================================

    public function test_existing_initial_allocation_does_not_double_out(): void
    {
        // Initial allocation: 100 m OUT
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        $outCountBefore = StockMovement::where('movement_type', MovementType::OUT)->count();

        // Exact usage: 100 m
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 100.0],
            ]);

        $outCountAfter = StockMovement::where('movement_type', MovementType::OUT)->count();

        // Tidak boleh ada OUT kedua!
        $this->assertEquals($outCountBefore, $outCountAfter);
        $this->assertEquals('30.0000', $this->kainStock->fresh()->qty_on_hand);
    }

    public function test_hpp_calculation_remains_consistent_with_actual_used_qty(): void
    {
        // Testing that BatchCostCalculationService respects used_qty correctly
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        // Usage 92 m (8 m returned)
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 50,
                'result_date'    => now()->toDateString(),
                'material_usage' => [$bm->id => 92.0],
            ]);

        $costService = app(BatchCostCalculationService::class);
        $materialCost = $costService->calculateMaterialCost($batch->fresh());

        $this->assertEquals('READY', $materialCost['status']);
        // 92 m * Rp 25.000 = Rp 2.300.000
        $this->assertEquals('2300000.00', $materialCost['amount']);
    }

    public function test_additional_allocation_blocked_if_batch_not_in_produksi_status(): void
    {
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);
        $batch->update(['status' => BatchStatus::QC]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 5.0,
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('PRODUKSI', session('error'));
        $this->assertEquals('100.0000', $bm->fresh()->allocated_qty);
        $this->assertEquals('30.0000', $this->kainStock->fresh()->qty_on_hand);
    }

    public function test_additional_allocation_blocked_if_material_does_not_belong_to_batch(): void
    {
        [$batch, , , ] = $this->createBatchWithMaterial(50, 100.0, 30.0);
        [$otherBatch, , , $otherBm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        // Try to allocate additional material using a batch material belonging to another batch
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $otherBm]), [
                'additional_qty' => 5.0,
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('bukan milik batch', session('error'));
    }

    public function test_stock_movement_timestamp_is_traceable(): void
    {
        [$batch, , , $bm] = $this->createBatchWithMaterial(50, 100.0, 30.0);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm]), [
                'additional_qty' => 5.0,
            ]);

        $movement = StockMovement::where('movement_type', MovementType::OUT)
            ->where('reference_type', 'production_batches')
            ->where('reference_id', $batch->id)
            ->first();

        $this->assertNotNull($movement);
        $formattedWib = \App\Support\DateTimeFormatter::formatDateTime($movement->created_at);
        $this->assertNotEmpty($formattedWib);
        $this->assertStringContainsString('WIB', $formattedWib);
    }
}
