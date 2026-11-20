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
use App\Models\Production\QcResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Production\ProductionResultService;
use App\Services\Production\QcResultService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionResultActualVarianceAndQcTest extends TestCase
{
    use RefreshDatabase;

    private User $produksiUser;
    private Customer $customer;
    private Unit $pcsUnit;
    private Material $labelMaterial;
    private MaterialStock $labelStock;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->produksiUser = User::factory()->create([
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Hijab UAT',
            'phone'         => '081299998888',
        ]);

        $this->pcsUnit = Unit::create([
            'unit_name' => 'Pieces',
            'symbol'    => 'pcs',
        ]);

        $this->labelMaterial = Material::create([
            'unit_id'       => $this->pcsUnit->id,
            'material_name' => 'Label Hijab Logam Nayara',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        $this->labelStock = MaterialStock::create([
            'material_id'   => $this->labelMaterial->id,
            'qty_on_hand'   => 100.0000,
            'avg_unit_cost' => 1500.0000,
        ]);

        $this->product = Product::create([
            'product_name' => 'Nayara Voal Premium',
            'active'       => true,
        ]);
    }

    private function createBatchWithOrder(float $targetQty = 19): array
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Rose',
            'sku'               => 'NYR-DR-' . uniqid(),
            'requires_printing' => false,
            'active'            => true,
        ]);

        $unitPrice = 85000;
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'ORD-UAT-' . uniqid(),
            'order_date'  => '2026-10-10',
            'status'      => OrderStatus::PRODUKSI,
        ]);

        $item = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => $targetQty,
            'unit_selling_price' => $unitPrice,
            'subtotal'           => $targetQty * $unitPrice,
        ]);

        $totalAmount = bcmul((string) $targetQty, (string) $unitPrice, 2);

        $invoice = Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-UAT-' . uniqid(),
            'invoice_date' => '2026-10-10',
            'total_amount' => $totalAmount,
            'status'       => \App\Enums\InvoiceStatus::UNPAID,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'          => $item->id,
            'batch_no'               => 'BAT-202610-0004',
            'target_qty'             => $targetQty,
            'status'                 => BatchStatus::PRODUKSI,
            'planned_start_date'     => '2026-10-10',
            'planned_end_date'       => '2026-10-12',
            'requires_printing'      => false,
            'production_employee_id' => $this->produksiUser->id,
        ]);

        $bm = BatchMaterial::create([
            'batch_id'      => $batch->id,
            'material_id'   => $this->labelMaterial->id,
            'required_qty'  => $targetQty,
            'allocated_qty' => $targetQty,
            'used_qty'      => 0.0000,
        ]);

        return [$batch, $order, $item, $invoice, $bm];
    }

    /**
     * Requirement A: Target 19, Actual 17 -> accepted (-2 pcs variance).
     */
    public function test_requirement_a_target_19_actual_17_is_accepted(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 17,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 17],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $freshBatch = $batch->fresh(['productionResult']);
        $this->assertEquals(BatchStatus::QC, $freshBatch->status);
        $this->assertEquals('19.0000', (string) $freshBatch->target_qty);
        $this->assertEquals('17.0000', (string) $freshBatch->productionResult->actual_qty);
        $this->assertEquals('-2.0000', $freshBatch->variance_qty);
    }

    /**
     * Requirement B: Target 19, Actual 19 -> accepted (0 pcs variance).
     */
    public function test_requirement_b_target_19_actual_19_is_accepted(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 19,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 19],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $freshBatch = $batch->fresh(['productionResult']);
        $this->assertEquals(BatchStatus::QC, $freshBatch->status);
        $this->assertEquals('19.0000', (string) $freshBatch->target_qty);
        $this->assertEquals('19.0000', (string) $freshBatch->productionResult->actual_qty);
        $this->assertEquals('0.0000', $freshBatch->variance_qty);
    }

    /**
     * Requirement C: Target 19, Actual 21 -> accepted (+2 pcs variance).
     */
    public function test_requirement_c_target_19_actual_21_is_accepted(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);
        // Allocation increased to 21 to cover usage of 21
        $bm->update(['allocated_qty' => 21]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $freshBatch = $batch->fresh(['productionResult']);
        $this->assertEquals(BatchStatus::QC, $freshBatch->status);
        $this->assertEquals('21.0000', (string) $freshBatch->productionResult->actual_qty);
        $this->assertEquals('2.0000', $freshBatch->variance_qty);
    }

    /**
     * Requirement D: Target remains 19 after actual 21 (immutable plan).
     */
    public function test_requirement_d_target_remains_19_after_actual_21(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);
        $bm->update(['allocated_qty' => 21]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        $freshBatch = $batch->fresh();
        $this->assertEquals('19.0000', (string) $freshBatch->target_qty);
        $this->assertEquals(19.0, (float) $freshBatch->target_qty);
        $this->assertEquals(21.0, (float) $freshBatch->actual_qty);
    }

    /**
     * Requirement E: Actual negative or zero -> rejected.
     */
    public function test_requirement_e_actual_negative_or_zero_is_rejected(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);

        foreach ([-5, 0, -0.01] as $invalidQty) {
            $response = $this->actingAs($this->produksiUser)
                ->post(route('produksi.batches.production-result.store', $batch), [
                    'actual_qty'     => $invalidQty,
                    'result_date'    => '2026-10-11',
                    'material_usage' => [$bm->id => 19],
                ]);

            $response->assertSessionHasErrors('actual_qty');
            $this->assertEquals(BatchStatus::PRODUKSI, $batch->fresh()->status);
            $this->assertNull($batch->fresh()->productionResult);
        }
    }

    /**
     * Requirement F: QC after actual 21 -> total classification = 21.
     */
    public function test_requirement_f_qc_after_actual_21_total_classification_source_is_21(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);
        $bm->update(['allocated_qty' => 21]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        $response = $this->actingAs($this->produksiUser)
            ->get(route('produksi.batches.qc.create', $batch));

        $response->assertOk();
        // Assert page shows 21 pcs as classification target
        $response->assertSee('21 pcs');
        $this->assertEquals('21.0000', (string) $batch->fresh()->productionResult->actual_qty);
    }

    /**
     * Requirement G: Good 21 from actual 21 -> accepted.
     */
    public function test_requirement_g_good_21_from_actual_21_is_accepted(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);
        $bm->update(['allocated_qty' => 21]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 21,
                'qc_date'  => '2026-10-11',
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));
        $response->assertSessionHas('success');

        $qc = QcResult::where('production_result_id', $batch->fresh()->productionResult->id)->first();
        $this->assertNotNull($qc);
        $this->assertEquals('21.0000', (string) $qc->good_qty);
        $this->assertEquals('0.0000', (string) $qc->reject_qty);
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
    }

    /**
     * Requirement H: Good 22 from actual 21 -> rejected.
     */
    public function test_requirement_h_good_22_from_actual_21_is_rejected(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);
        $bm->update(['allocated_qty' => 21]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 22,
                'qc_date'  => '2026-10-11',
            ]);

        $response->assertSessionHasErrors('good_qty');
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
        $this->assertNull(QcResult::where('production_result_id', $batch->fresh()->productionResult->id)->first());
    }

    /**
     * Requirement I: Actual 21 + Good 20 -> Reject auto = 1.
     */
    public function test_requirement_i_actual_21_good_20_auto_reject_is_1(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);
        $bm->update(['allocated_qty' => 21]);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.qc.store', $batch), [
                'good_qty' => 20,
                'qc_date'  => '2026-10-11',
            ]);

        $response->assertRedirect(route('produksi.batches.show', $batch));

        $qc = QcResult::where('production_result_id', $batch->fresh()->productionResult->id)->first();
        $this->assertNotNull($qc);
        $this->assertEquals('20.0000', (string) $qc->good_qty);
        $this->assertEquals('1.0000', (string) $qc->reject_qty);
        $this->assertEquals(BatchStatus::PACKING, $batch->fresh()->status);
    }

    /**
     * Requirement J: No QC validation uses target (19) as maximum.
     * (Good 20 and 21 are accepted even though target is 19).
     */
    public function test_requirement_j_no_qc_validation_uses_target_as_maximum(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);
        $bm->update(['allocated_qty' => 21]);

        // Service direct test
        $prodService = app(ProductionResultService::class);
        $prodResult = $prodService->recordFinalResult(
            $batch,
            21,
            '2026-10-11',
            null,
            $this->produksiUser,
            [$bm->id => 21]
        );

        $qcService = app(QcResultService::class);
        // Good = 21, which is > target (19)
        $qcResult = $qcService->recordFinalQc(
            $batch,
            21,
            null,
            $this->produksiUser,
            '2026-10-11'
        );

        $this->assertNotNull($qcResult);
        $this->assertEquals('21.0000', (string) $qcResult->good_qty);
        $this->assertEquals('0.0000', (string) $qcResult->reject_qty);
        $this->assertEquals('19.0000', (string) $batch->fresh()->target_qty);
    }

    /**
     * Requirement K: Sales Order quantity unchanged after actual > target.
     */
    public function test_requirement_k_sales_order_quantity_unchanged_after_actual_greater_than_target(): void
    {
        [$batch, $order, $item, , $bm] = $this->createBatchWithOrder(19);
        $bm->update(['allocated_qty' => 21]);

        $initialOrderQty = $item->qty_ordered;
        $this->assertEquals(19.0, (float) $initialOrderQty);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        $item->refresh();
        $this->assertEquals(19.0, (float) $item->qty_ordered);
        $this->assertEquals('19.0000', (string) $item->qty_ordered);
    }

    /**
     * Requirement L: Invoice amount unchanged after actual > target.
     */
    public function test_requirement_l_invoice_amount_unchanged_after_actual_greater_than_target(): void
    {
        [$batch, $order, $item, $invoice, $bm] = $this->createBatchWithOrder(19);
        $bm->update(['allocated_qty' => 21]);

        $expectedTotal = 19 * 85000;
        $this->assertEquals((float) $expectedTotal, (float) $invoice->total_amount);

        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        $invoice->refresh();
        $this->assertEquals((float) $expectedTotal, (float) $invoice->total_amount);
        $this->assertEquals('1615000.00', (string) $invoice->total_amount);
    }

    /**
     * Requirement M: Additional material allocation remains valid and stock movement still occurs exactly once.
     */
    public function test_requirement_m_additional_material_allocation_and_stock_movement_occurs_once(): void
    {
        [$batch, , , , $bm] = $this->createBatchWithOrder(19);
        $initialStock = (float) $this->labelStock->fresh()->qty_on_hand;

        $movementsBefore = StockMovement::where('material_id', $this->labelMaterial->id)->count();

        // 1. Request additional 2 pcs of label material
        $response = $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.materials.additional', [$batch, $bm->id]), [
                'additional_qty' => 2,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Required BOM snapshot remains 19, allocated becomes 21
        $bm->refresh();
        $this->assertEquals(19.0, (float) $bm->required_qty);
        $this->assertEquals(21.0, (float) $bm->allocated_qty);

        // Stock decreased by 2
        $this->assertEquals($initialStock - 2, (float) $this->labelStock->fresh()->qty_on_hand);

        // Exactly one new stock OUT movement created for this additional request
        $movementsAfter = StockMovement::where('material_id', $this->labelMaterial->id)->count();
        $this->assertEquals($movementsBefore + 1, $movementsAfter);

        $outMovement = StockMovement::where('material_id', $this->labelMaterial->id)
            ->where('movement_type', MovementType::OUT)
            ->where('reference_type', 'production_batches')
            ->where('reference_id', $batch->id)
            ->first();

        $this->assertNotNull($outMovement);
        $this->assertEquals('2.0000', (string) $outMovement->qty);

        // 2. Now record production result with 21 pcs used
        $this->actingAs($this->produksiUser)
            ->post(route('produksi.batches.production-result.store', $batch), [
                'actual_qty'     => 21,
                'result_date'    => '2026-10-11',
                'material_usage' => [$bm->id => 21],
            ]);

        // Stock remains $initialStock - 2 (no double deduction upon recording result)
        $this->assertEquals($initialStock - 2, (float) $this->labelStock->fresh()->qty_on_hand);
        $this->assertEquals(BatchStatus::QC, $batch->fresh()->status);
    }
}
