<?php

namespace Tests\Feature\Produksi;

use App\Enums\BatchStatus;
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
use App\Models\Production\QcResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Production\BatchWorkflowService;
use App\Services\Production\QcResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialUsageFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private Customer $customer;
    private Unit $meterUnit;
    private Unit $pcsUnit;
    private Material $kain;
    private Material $benang;
    private Material $label;
    private MaterialStock $kainStock;
    private MaterialStock $benangStock;
    private MaterialStock $labelStock;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create(['customer_name' => 'Nayara Boutique', 'phone' => '081234567890']);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->pcsUnit = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $this->kain = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Katun Rayon',
            'min_stock'     => 10,
            'active'        => true,
        ]);
        $this->kainStock = MaterialStock::create([
            'material_id'   => $this->kain->id,
            'qty_on_hand'   => 500.0000,
            'avg_unit_cost' => 30000.0000,
        ]);

        $this->benang = Material::create([
            'unit_id'       => $this->pcsUnit->id,
            'material_name' => 'Benang Jahit',
            'min_stock'     => 5,
            'active'        => true,
        ]);
        $this->benangStock = MaterialStock::create([
            'material_id'   => $this->benang->id,
            'qty_on_hand'   => 50.0000,
            'avg_unit_cost' => 15000.0000,
        ]);

        $this->label = Material::create([
            'unit_id'       => $this->pcsUnit->id,
            'material_name' => 'Woven Label',
            'min_stock'     => 100,
            'active'        => true,
        ]);
        $this->labelStock = MaterialStock::create([
            'material_id'   => $this->label->id,
            'qty_on_hand'   => 1000.0000,
            'avg_unit_cost' => 1000.0000,
        ]);

        $this->product = Product::create(['product_name' => 'Gamis Rayon Premium', 'active' => true]);
    }

    private function createProduksiBatch(float $targetQty = 100): array
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Navy',
            'sku'               => 'SKU-NAVY-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => 150000,
            'subtotal'           => $targetQty * 150000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-USG-' . uniqid(),
            'target_qty'             => $targetQty,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bmKain = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->kain->id,
            'required_qty'  => 129.0000,
            'allocated_qty' => 129.0000,
            'used_qty'      => 0.0000,
        ]);

        $bmBenang = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->benang->id,
            'required_qty'  => 2.0000,
            'allocated_qty' => 2.0000,
            'used_qty'      => 0.0000,
        ]);

        return [$batch, $order, $variant, $bmKain, $bmBenang];
    }

    public function test_exact_usage_records_marker_and_advances_batch(): void
    {
        [$batch, , , $bmKain, $bmBenang] = $this->createProduksiBatch(100);

        $initialMovementCount = StockMovement::count();
        $initialKainStock = $this->kainStock->fresh()->qty_on_hand;

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bmKain->id   => 129.0,
                    $bmBenang->id => 2.0,
                ],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertNotNull($batch->fresh()->material_usage_recorded_at);
        $this->assertEquals($this->produksiUser->id, $batch->fresh()->material_usage_recorded_by);

        $this->assertEquals('129.0000', $bmKain->fresh()->used_qty);
        $this->assertEquals('2.0000', $bmBenang->fresh()->used_qty);

        // Zero inventory side-effect
        $this->assertEquals($initialMovementCount, StockMovement::count());
        $this->assertEquals($initialKainStock, $this->kainStock->fresh()->qty_on_hand);
    }

    public function test_usage_below_allocation_automatically_returns_surplus_to_warehouse(): void
    {
        [$batch, , , $bmKain, $bmBenang] = $this->createProduksiBatch(100);

        $initialMovementCount = StockMovement::count();
        $initialKainStock = (string) $this->kainStock->fresh()->qty_on_hand;

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 98,
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bmKain->id   => 127.5,
                    $bmBenang->id => 1.75,
                ],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals('127.5000', $bmKain->fresh()->used_qty);
        $this->assertEquals('1.7500', $bmBenang->fresh()->used_qty);

        // Revisi Pembimbing Tahap 2: Sisa bahan (1.5 m kain, 0.25 pcs benang) otomatis kembali ke gudang
        $this->assertEquals($initialMovementCount + 2, StockMovement::count());
        $this->assertEquals(bcadd($initialKainStock, '1.5000', 4), (string) $this->kainStock->fresh()->qty_on_hand);
    }

    public function test_zero_used_quantity_is_valid_and_distinguished_from_unrecorded(): void
    {
        [$batch, , , $bmKain, $bmBenang] = $this->createProduksiBatch(100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 10,
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bmKain->id   => 10.0,
                    $bmBenang->id => 0.0, // Explicit zero
                ],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $this->assertEquals('0.0000', $bmBenang->fresh()->used_qty);
        $this->assertNotNull($batch->fresh()->material_usage_recorded_at);
    }

    public function test_usage_above_allocation_is_rejected(): void
    {
        [$batch, , , $bmKain, $bmBenang] = $this->createProduksiBatch(100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bmKain->id   => 130.0, // Exceeds 129.0
                    $bmBenang->id => 2.0,
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertNull($batch->fresh()->material_usage_recorded_at);
        $this->assertEquals('0.0000', $bmKain->fresh()->used_qty);
        $this->assertEquals(0, ProductionResult::where('batch_id', $batch->id)->count());
    }

    public function test_missing_material_entry_rejects_entire_submission(): void
    {
        [$batch, , , $bmKain] = $this->createProduksiBatch(100);

        // Submit only kain, omitting benang
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bmKain->id => 129.0,
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertNull($batch->fresh()->material_usage_recorded_at);
        $this->assertEquals('0.0000', $bmKain->fresh()->used_qty);
    }

    public function test_foreign_material_entry_is_rejected(): void
    {
        [$batch1, , , $bmKain1, $bmBenang1] = $this->createProduksiBatch(100);
        [$batch2, , , $bmKain2] = $this->createProduksiBatch(100);

        // Include BatchMaterial from batch2 in submission for batch1
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch1), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bmKain1->id => 129.0,
                    $bmKain2->id => 50.0, // Foreign ID
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals(BatchStatus::PRODUKSI, $batch1->fresh()->status);
        $this->assertEquals('0.0000', $bmKain1->fresh()->used_qty);
        $this->assertEquals('0.0000', $bmKain2->fresh()->used_qty);
    }

    public function test_atomic_rollback_on_invalid_actual_qty(): void
    {
        [$batch, , , $bmKain, $bmBenang] = $this->createProduksiBatch(100);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 0, // Invalid actual qty
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bmKain->id   => 129.0,
                    $bmBenang->id => 2.0,
                ],
            ]);

        $response->assertSessionHasErrors('actual_qty');

        $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
        $this->assertNull($batch->fresh()->material_usage_recorded_at);
        $this->assertEquals('0.0000', $bmKain->fresh()->used_qty);
        $this->assertEquals(0, ProductionResult::count());
    }

    public function test_authenticated_actor_authority_enforced(): void
    {
        [$batch, , , $bmKain, $bmBenang] = $this->createProduksiBatch(100);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'                  => 100,
                'result_date'                 => '2026-08-29',
                'material_usage_recorded_by'  => $otherUser->id, // Malicious spoof
                'material_usage'              => [
                    $bmKain->id   => 129.0,
                    $bmBenang->id => 2.0,
                ],
            ]);

        $freshBatch = $batch->fresh();
        $this->assertEquals($this->produksiUser->id, $freshBatch->material_usage_recorded_by);
        $result = ProductionResult::where('batch_id', $batch->id)->first();
        $this->assertEquals($this->produksiUser->id, $result->recorded_by);
    }

    public function test_p4_qc_and_p5_packing_preserve_recorded_material_usage(): void
    {
        [$batch, , , $bmKain, $bmBenang] = $this->createProduksiBatch(100);

        // 1. Record result & usage (P3 + P6A)
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 100,
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bmKain->id   => 127.5,
                    $bmBenang->id => 1.8,
                ],
            ]);

        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertEquals('127.5000', $bmKain->fresh()->used_qty);
        $this->assertEquals('1.8000', $bmBenang->fresh()->used_qty);

        // 2. Perform QC (P4)
        $qcService = app(QcResultService::class);
        $qcService->recordFinalQc($batch->fresh(), 98, 'QC Pass', $this->produksiUser);

        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
        $this->assertEquals('127.5000', $bmKain->fresh()->used_qty);
        $this->assertEquals('1.8000', $bmBenang->fresh()->used_qty);

        // 3. Complete Packing (P5)
        $workflowService = app(BatchWorkflowService::class);
        $workflowService->completePacking($batch->fresh(), $this->produksiUser);

        $this->assertEquals(BatchStatus::SELESAI, $batch->fresh()->status);
        $this->assertEquals('127.5000', $bmKain->fresh()->used_qty);
        $this->assertEquals('1.8000', $bmBenang->fresh()->used_qty);
    }

    public function test_multiple_materials_all_recorded_atomically(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Sage Green',
            'sku'               => 'SKU-SAGE-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-MULTI-' . uniqid(),
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 150000,
            'subtotal'           => 15000000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-MULTI-' . uniqid(),
            'target_qty'             => 100,
            'status'                 => BatchStatus::PRODUKSI,
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bm1 = BatchMaterial::create(['batch_id' => $batch->id, 'material_id' => $this->kain->id, 'required_qty' => 129, 'allocated_qty' => 129]);
        $bm2 = BatchMaterial::create(['batch_id' => $batch->id, 'material_id' => $this->benang->id, 'required_qty' => 2, 'allocated_qty' => 2]);
        $bm3 = BatchMaterial::create(['batch_id' => $batch->id, 'material_id' => $this->label->id, 'required_qty' => 100, 'allocated_qty' => 100]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 98,
                'result_date'    => '2026-08-29',
                'material_usage' => [
                    $bm1->id => 127.5,
                    $bm2->id => 1.75,
                    $bm3->id => 98.0,
                ],
            ]);

        $this->assertEquals('127.5000', $bm1->fresh()->used_qty);
        $this->assertEquals('1.7500', $bm2->fresh()->used_qty);
        $this->assertEquals('98.0000', $bm3->fresh()->used_qty);
    }

    public function test_legacy_record_display_does_not_claim_confirmed_zero_usage(): void
    {
        [$batch] = $this->createProduksiBatch(100);

        // Manually create legacy production result without material_usage_recorded_at
        ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 100,
            'result_date' => '2026-08-28',
            'recorded_by' => $this->produksiUser->id,
        ]);
        $batch->update(['status' => BatchStatus::QC]);

        $this->assertNull($batch->fresh()->material_usage_recorded_at);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.show', $batch));

        $response->assertOk();
        $response->assertSee('Pemakaian bahan aktual belum tercatat pada data ini');
    }

    public function test_role_authorization_for_material_usage_submission(): void
    {
        [$batch, , , $bmKain, $bmBenang] = $this->createProduksiBatch(100);

        $payload = [
            'actual_qty'     => 100,
            'result_date'    => '2026-08-29',
            'material_usage' => [
                $bmKain->id   => 129.0,
                $bmBenang->id => 2.0,
            ],
        ];

        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($admin)
            ->post(route('produksi.batches.production-result.store', $batch), $payload)
            ->assertForbidden();

        $gudang = User::factory()->create(['role' => UserRole::GUDANG, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($gudang)
            ->post(route('produksi.batches.production-result.store', $batch), $payload)
            ->assertForbidden();

        $owner = User::factory()->create(['role' => UserRole::OWNER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($owner)
            ->post(route('produksi.batches.production-result.store', $batch), $payload)
            ->assertForbidden();

        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'status' => UserStatus::ACTIVE]);
        $this->actingAs($customer)
            ->post(route('produksi.batches.production-result.store', $batch), $payload)
            ->assertForbidden();
    }
}
