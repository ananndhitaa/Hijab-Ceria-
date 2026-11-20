<?php

namespace Tests\Feature\Gudang;

use App\Enums\BatchStatus;
use App\Enums\MaterialReadiness;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\Production\BatchMaterial;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\System\DashboardActionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GudangActionQueueAndStockAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private Unit $meter;
    private Unit $roll;
    private Unit $pcs;
    private Supplier $supplier;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staf Gudang',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser = User::factory()->create([
            'name'   => 'Admin Keuangan',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->roll  = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);
        $this->pcs   = Unit::create(['unit_name' => 'Pcs', 'symbol' => 'pcs']);

        $this->supplier = Supplier::create([
            'supplier_name' => 'PT Sumber Kain',
            'phone'         => '08123456789',
            'active'        => true,
        ]);

        $product = Product::create([
            'product_name' => 'Hijab Segi Empat Oval',
            'description'  => 'Hijab oval premium',
            'active'       => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Latte',
            'sku'          => 'HJB-SEO-LATTE',
            'price'        => 85000,
            'active'       => true,
        ]);

        OpeningCashBalance::create([
            'opening_date' => '2026-10-01',
            'amount'       => '10000000.00',
            'notes'        => 'Kas Awal',
            'created_by'   => $this->adminUser->id,
        ]);
    }

    private function createBatchWithReadiness(
        string $batchNo,
        string $targetQty,
        MaterialReadiness $readiness,
        string $productName = 'Hijab Segi Empat Oval',
        string $variantName = 'Latte'
    ): ProductionBatch {
        $customer = Customer::create([
            'customer_name' => 'Butik Al-Falah',
            'phone'         => '08123456780',
            'active'        => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_no'    => 'SO-TEST-' . uniqid(),
            'order_date'  => Carbon::today(),
            'status'      => \App\Enums\OrderStatus::PRODUKSI,
        ]);

        $product = Product::firstOrCreate(
            ['product_name' => $productName],
            ['description' => 'Test Product', 'active' => true]
        );

        $variant = ProductVariant::firstOrCreate(
            ['sku' => 'SKU-' . uniqid()],
            [
                'product_id'   => $product->id,
                'variant_name' => $variantName,
                'price'        => 85000,
                'active'       => true,
            ]
        );

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => (int) $targetQty,
            'unit_selling_price' => 85000,
        ]);

        $batch = ProductionBatch::create([
            'order_item_id'    => $orderItem->id,
            'batch_no'         => $batchNo,
            'target_qty'       => (float) $targetQty,
            'status'           => BatchStatus::BAHAN,
            'planned_end_date' => Carbon::create(2026, 10, 12),
        ]);

        $material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain ' . uniqid(),
            'min_stock'     => '1.0000',
            'active'        => true,
        ]);

        if ($readiness === MaterialReadiness::SIAP_DIALOKASIKAN) {
            MaterialStock::create([
                'material_id'   => $material->id,
                'qty_on_hand'   => '100.0000',
                'avg_unit_cost' => '35000.0000',
            ]);
            BatchMaterial::create([
                'batch_id'      => $batch->id,
                'material_id'   => $material->id,
                'required_qty'  => '20.0000',
                'allocated_qty' => '0.0000',
            ]);
        } elseif ($readiness === MaterialReadiness::TERALOKASI) {
            MaterialStock::create([
                'material_id'   => $material->id,
                'qty_on_hand'   => '100.0000',
                'avg_unit_cost' => '35000.0000',
            ]);
            BatchMaterial::create([
                'batch_id'      => $batch->id,
                'material_id'   => $material->id,
                'required_qty'  => '20.0000',
                'allocated_qty' => '20.0000',
            ]);
        } elseif ($readiness === MaterialReadiness::PERLU_PEMBELIAN) {
            MaterialStock::create([
                'material_id'   => $material->id,
                'qty_on_hand'   => '5.0000',
                'avg_unit_cost' => '35000.0000',
            ]);
            BatchMaterial::create([
                'batch_id'      => $batch->id,
                'material_id'   => $material->id,
                'required_qty'  => '20.0000',
                'allocated_qty' => '0.0000',
            ]);
        }

        return $batch;
    }

    /**
     * 1. Siap Dialokasikan batch appears in Gudang Dashboard.
     */
    public function test_siap_dialokasikan_batch_appears_in_gudang_dashboard(): void
    {
        $batch = $this->createBatchWithReadiness('BAT-202610-0003', '18.0000', MaterialReadiness::SIAP_DIALOKASIKAN);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee('Perlu Tindakan');
        $response->assertSee('BAT-202610-0003');
        $response->assertSee('Hijab Segi Empat Oval - Latte');
        $response->assertSee('Siap Dialokasikan');
        $response->assertSee('Alokasikan');
        $response->assertSee(route('gudang.material-planning.show', $batch));
    }

    /**
     * 2. Fully allocated batch does not appear as allocation action.
     */
    public function test_fully_allocated_batch_does_not_appear_as_allocation_action(): void
    {
        $batch = $this->createBatchWithReadiness('BAT-202610-0004', '19.0000', MaterialReadiness::TERALOKASI);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertDontSee('BAT-202610-0004');
        $response->assertSee('Tidak ada tindakan yang perlu ditangani.');
    }

    /**
     * 3 & 4. Current BAT-202610-0003 appears, BAT-202610-0004 does not.
     */
    public function test_bat_0003_appears_and_bat_0004_does_not_appear(): void
    {
        $batch3 = $this->createBatchWithReadiness('BAT-202610-0003', '18.0000', MaterialReadiness::SIAP_DIALOKASIKAN);
        $batch4 = $this->createBatchWithReadiness('BAT-202610-0004', '19.0000', MaterialReadiness::TERALOKASI, 'Hijab Voal Polos Premium', 'Navy');

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertSee('BAT-202610-0003');
        $response->assertSee('Siap Dialokasikan');
        $response->assertDontSee('BAT-202610-0004');
        $response->assertDontSee('Navy');
    }

    /**
     * 5. Purchase actions remain correct and respect priority hierarchy.
     */
    public function test_purchase_actions_and_batch_actions_respect_priority_order(): void
    {
        // Priority 1: PO Perlu Revisi
        $poRevision = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-REV-01',
            'purchase_date'   => '2026-10-09',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::REVISION_REQUIRED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $poRevision->id,
            'material_id'  => Material::create(['unit_id' => $this->meter->id, 'material_name' => 'M1', 'min_stock' => 1])->id,
            'qty'          => '10.0000',
            'unit_price'   => '50000.00',
            'qty_received' => '0.0000',
        ]);

        // Priority 2: Batch Siap Dialokasikan
        $batch = $this->createBatchWithReadiness('BAT-202610-0003', '18.0000', MaterialReadiness::SIAP_DIALOKASIKAN);

        // Priority 6: PO Belum Diajukan
        $poDraft = Purchase::create([
            'supplier_id'     => $this->supplier->id,
            'purchase_no'     => 'PO-DRAFT-01',
            'purchase_date'   => '2026-10-09',
            'status'          => PurchaseStatus::DRAFT,
            'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $poDraft->id,
            'material_id'  => Material::create(['unit_id' => $this->meter->id, 'material_name' => 'M2', 'min_stock' => 1])->id,
            'qty'          => '10.0000',
            'unit_price'   => '50000.00',
            'qty_received' => '0.0000',
        ]);

        $service = app(DashboardActionService::class);
        $actions = $service->getGudangActions(10)['items'];

        $this->assertCount(3, $actions);
        $this->assertEquals('PO-REV-01', $actions[0]['purchase_no']);
        $this->assertEquals(1, $actions[0]['priority']);
        $this->assertEquals('BAT-202610-0003', $actions[1]['purchase_no']);
        $this->assertEquals(2, $actions[1]['priority']);
        $this->assertEquals('PO-DRAFT-01', $actions[2]['purchase_no']);
        $this->assertEquals(6, $actions[2]['priority']);
    }

    /**
     * 6. Gudang cash privacy remains intact.
     */
    public function test_gudang_cash_privacy_remains_intact(): void
    {
        $this->createBatchWithReadiness('BAT-202610-0003', '18.0000', MaterialReadiness::SIAP_DIALOKASIKAN);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();
        $response->assertDontSee('Kas Saat Ini');
        $response->assertDontSee('Kas Tersedia');
        $response->assertDontSee('10.000.000');
    }

    /**
     * 7. qty = 0 => Habis only.
     */
    public function test_stock_status_qty_zero_is_habis_only(): void
    {
        $material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Zero',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => '0.0000',
            'avg_unit_cost' => '30000.0000',
        ]);

        $material->load('stock');

        $this->assertTrue($material->isOutOfStock());
        $this->assertFalse($material->isLowStock());
        $this->assertFalse($material->isSafeStock());
        $this->assertEquals('HABIS', $material->stock_status);
    }

    /**
     * 8. qty < 0 if legacy/error state => Habis only.
     */
    public function test_stock_status_qty_negative_is_habis_only(): void
    {
        $material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Negatif',
            'min_stock'     => '5.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => '-2.5000',
            'avg_unit_cost' => '30000.0000',
        ]);

        $material->load('stock');

        $this->assertTrue($material->isOutOfStock());
        $this->assertFalse($material->isLowStock());
        $this->assertFalse($material->isSafeStock());
        $this->assertEquals('HABIS', $material->stock_status);
    }

    /**
     * 9. qty > 0 and qty <= minimum => Rendah only.
     */
    public function test_stock_status_qty_positive_and_below_or_equal_min_is_rendah_only(): void
    {
        $material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Rendah',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => '10.0000', // Exactly minimum
            'avg_unit_cost' => '30000.0000',
        ]);

        $material->load('stock');

        $this->assertFalse($material->isOutOfStock());
        $this->assertTrue($material->isLowStock());
        $this->assertFalse($material->isSafeStock());
        $this->assertEquals('RENDAH', $material->stock_status);

        // Sub-test: slightly below minimum but positive
        $material->stock->update(['qty_on_hand' => '0.0001']);
        $material->load('stock');

        $this->assertFalse($material->isOutOfStock());
        $this->assertTrue($material->isLowStock());
        $this->assertFalse($material->isSafeStock());
        $this->assertEquals('RENDAH', $material->stock_status);
    }

    /**
     * 10. qty > minimum => Aman only.
     */
    public function test_stock_status_qty_above_minimum_is_aman_only(): void
    {
        $material = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Aman',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);
        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => '10.0001',
            'avg_unit_cost' => '30000.0000',
        ]);

        $material->load('stock');

        $this->assertFalse($material->isOutOfStock());
        $this->assertFalse($material->isLowStock());
        $this->assertTrue($material->isSafeStock());
        $this->assertEquals('AMAN', $material->stock_status);
    }

    /**
     * 11. Low and Out counts never overlap on dashboard.
     */
    public function test_low_and_out_counts_never_overlap(): void
    {
        // 1 Out of Stock (0)
        $m1 = Material::create(['unit_id' => $this->meter->id, 'material_name' => 'Mat Zero', 'min_stock' => '5.0000', 'active' => true]);
        MaterialStock::create(['material_id' => $m1->id, 'qty_on_hand' => '0.0000']);

        // 1 Out of Stock (< 0)
        $m2 = Material::create(['unit_id' => $this->meter->id, 'material_name' => 'Mat Neg', 'min_stock' => '5.0000', 'active' => true]);
        MaterialStock::create(['material_id' => $m2->id, 'qty_on_hand' => '-1.0000']);

        // 2 Low Stock (> 0 and <= min)
        $m3 = Material::create(['unit_id' => $this->meter->id, 'material_name' => 'Mat Low 1', 'min_stock' => '10.0000', 'active' => true]);
        MaterialStock::create(['material_id' => $m3->id, 'qty_on_hand' => '2.0000']);

        $m4 = Material::create(['unit_id' => $this->meter->id, 'material_name' => 'Mat Low 2', 'min_stock' => '10.0000', 'active' => true]);
        MaterialStock::create(['material_id' => $m4->id, 'qty_on_hand' => '10.0000']);

        // 1 Safe Stock (> min)
        $m5 = Material::create(['unit_id' => $this->meter->id, 'material_name' => 'Mat Safe', 'min_stock' => '10.0000', 'active' => true]);
        MaterialStock::create(['material_id' => $m5->id, 'qty_on_hand' => '15.0000']);

        // 1 Inactive (should not count)
        $m6 = Material::create(['unit_id' => $this->meter->id, 'material_name' => 'Mat Inactive', 'min_stock' => '10.0000', 'active' => false]);
        MaterialStock::create(['material_id' => $m6->id, 'qty_on_hand' => '0.0000']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));
        $response->assertOk();

        $viewData = $response->getOriginalContent()->getData();
        $this->assertEquals(5, $viewData['totalMaterials']);
        $this->assertEquals(2, $viewData['lowStockCount']);
        $this->assertEquals(2, $viewData['outOfStockCount']);
        $this->assertEquals(1, $viewData['safeStockCount']);

        // Verify mathematical non-overlapping partition
        $this->assertEquals(
            $viewData['totalMaterials'],
            $viewData['lowStockCount'] + $viewData['outOfStockCount'] + $viewData['safeStockCount']
        );
    }

    /**
     * 12. Non-overlapping classification on distinct units and materials.
     */
    public function test_all_material_units_classify_consistently(): void
    {
        $materials = [
            ['name' => 'Kain Kaos', 'unit' => $this->meter, 'stock' => '100.0000', 'min' => '0.0000', 'expected' => 'AMAN'],
            ['name' => 'Kain Voal', 'unit' => $this->meter, 'stock' => '41.0800',  'min' => '1.0000', 'expected' => 'AMAN'],
            ['name' => 'Benang Jahit', 'unit' => $this->roll, 'stock' => '83.3590', 'min' => '10.0000', 'expected' => 'AMAN'],
            ['name' => 'Label Brand', 'unit' => $this->pcs, 'stock' => '274.0000', 'min' => '50.0000', 'expected' => 'AMAN'],
            ['name' => 'Benang Tipis', 'unit' => $this->roll, 'stock' => '5.0000', 'min' => '10.0000', 'expected' => 'RENDAH'],
            ['name' => 'Polybag Habis', 'unit' => $this->pcs, 'stock' => '0.0000', 'min' => '50.0000', 'expected' => 'HABIS'],
        ];

        foreach ($materials as $m) {
            $created = Material::create([
                'unit_id'       => $m['unit']->id,
                'material_name' => $m['name'],
                'min_stock'     => $m['min'],
                'active'        => true,
            ]);
            MaterialStock::create([
                'material_id'   => $created->id,
                'qty_on_hand'   => $m['stock'],
                'avg_unit_cost' => '1000.0000',
            ]);

            $created->load('stock');
            $this->assertEquals($m['expected'], $created->stock_status);
        }
    }

    /**
     * 13 & 14. Viewing dashboard causes NO stock mutation and NO MWA mutation.
     */
    public function test_no_stock_or_mwa_mutation_on_dashboard_view(): void
    {
        $mat = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Audit',
            'min_stock'     => '10.0000',
            'active'        => true,
        ]);
        $stock = MaterialStock::create([
            'material_id'   => $mat->id,
            'qty_on_hand'   => '45.1234',
            'avg_unit_cost' => '32150.7500',
        ]);

        $this->actingAs($this->gudangUser)->get(route('gudang.dashboard'));

        $fresh = $stock->fresh();
        $this->assertEquals('45.1234', $fresh->qty_on_hand);
        $this->assertEquals('32150.7500', $fresh->avg_unit_cost);
    }
}
