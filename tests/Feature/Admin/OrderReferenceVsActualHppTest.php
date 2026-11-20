<?php

namespace Tests\Feature\Admin;

use App\Enums\BatchStatus;
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
use App\Models\Production\QcResult;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\System\User;
use App\Services\Finance\VariantCostCalculationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReferenceVsActualHppTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductVariant $variantGeometricSage;
    private ProductVariant $variantLatte;
    private ProductVariant $variantNoBom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customer = Customer::create([
            'customer_name' => 'Nayara Hijab Partner',
            'phone'         => '081298765432',
            'address'       => 'Jl. Riau No. 45 Bandung',
        ]);

        $unitMeter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $unitPcs = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);
        $unitRoll = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);

        // Materials for Geometric Sage: 47.160 material cost + 9.000 direct = 56.160
        $matPrinting = Material::create(['material_name' => 'Kain Voal Printing', 'unit_id' => $unitMeter->id, 'cost_per_unit' => 34000, 'active' => true]);
        MaterialStock::create(['material_id' => $matPrinting->id, 'qty_on_hand' => 100, 'avg_unit_cost' => 34000]);

        $matBenang = Material::create(['material_name' => 'Benang Jahit', 'unit_id' => $unitRoll->id, 'cost_per_unit' => 18000, 'active' => true]);
        MaterialStock::create(['material_id' => $matBenang->id, 'qty_on_hand' => 100, 'avg_unit_cost' => 18000]);

        $matLabel = Material::create(['material_name' => 'Label Brand Hijab', 'unit_id' => $unitPcs->id, 'cost_per_unit' => 600, 'active' => true]);
        MaterialStock::create(['material_id' => $matLabel->id, 'qty_on_hand' => 500, 'avg_unit_cost' => 600]);

        $matPolybag = Material::create(['material_name' => 'Polybag 25x35', 'unit_id' => $unitPcs->id, 'cost_per_unit' => 900, 'active' => true]);
        MaterialStock::create(['material_id' => $matPolybag->id, 'qty_on_hand' => 500, 'avg_unit_cost' => 900]);

        $matHangtag = Material::create(['material_name' => 'Hangtag', 'unit_id' => $unitPcs->id, 'cost_per_unit' => 800, 'active' => true]);
        MaterialStock::create(['material_id' => $matHangtag->id, 'qty_on_hand' => 500, 'avg_unit_cost' => 800]);

        $matStiker = Material::create(['material_name' => 'Stiker Barcode', 'unit_id' => $unitPcs->id, 'cost_per_unit' => 300, 'active' => true]);
        MaterialStock::create(['material_id' => $matStiker->id, 'qty_on_hand' => 500, 'avg_unit_cost' => 300]);

        // Materials for Latte: 34.196 material cost + 6.000 direct = 40.196
        $matPremium = Material::create(['material_name' => 'Kain Voal Premium', 'unit_id' => $unitMeter->id, 'cost_per_unit' => 24000, 'active' => true]);
        MaterialStock::create(['material_id' => $matPremium->id, 'qty_on_hand' => 100, 'avg_unit_cost' => 24000]);

        $prodVoalMotif = Product::create(['product_name' => 'Hijab Voal Motif', 'code' => 'VM', 'active' => true]);
        $this->variantGeometricSage = ProductVariant::create([
            'product_id'                           => $prodVoalMotif->id,
            'variant_name'                         => 'Geometric Sage',
            'sku'                                  => 'HJB-VM-GS-002',
            'base_selling_price'                   => 105000,
            'requires_printing'                    => true,
            'printing_cost_per_unit'               => 3000,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 0,
            'active'                               => true,
        ]);

        // BOM for Geometric Sage:
        // 1.3m * 34.000 = 44.200
        // 0.02 roll * 18.000 = 360
        // 1 pcs * 600 = 600
        // 1 pcs * 900 = 900
        // 1 pcs * 800 = 800
        // 1 pcs * 300 = 300
        // Total material = 47.160, Direct = 3000 + 5000 + 1000 = 9000 -> Grand Total = 56.160
        BomItem::create(['variant_id' => $this->variantGeometricSage->id, 'material_id' => $matPrinting->id, 'qty_per_unit' => 1.3]);
        BomItem::create(['variant_id' => $this->variantGeometricSage->id, 'material_id' => $matBenang->id, 'qty_per_unit' => 0.02]);
        BomItem::create(['variant_id' => $this->variantGeometricSage->id, 'material_id' => $matLabel->id, 'qty_per_unit' => 1.0]);
        BomItem::create(['variant_id' => $this->variantGeometricSage->id, 'material_id' => $matPolybag->id, 'qty_per_unit' => 1.0]);
        BomItem::create(['variant_id' => $this->variantGeometricSage->id, 'material_id' => $matHangtag->id, 'qty_per_unit' => 1.0]);
        BomItem::create(['variant_id' => $this->variantGeometricSage->id, 'material_id' => $matStiker->id, 'qty_per_unit' => 1.0]);

        $prodSegiEmpat = Product::create(['product_name' => 'Hijab Segi Empat Oval', 'code' => 'SO', 'active' => true]);
        $this->variantLatte = ProductVariant::create([
            'product_id'                           => $prodSegiEmpat->id,
            'variant_name'                         => 'Latte',
            'sku'                                  => 'HJB-SO-LT-001',
            'base_selling_price'                   => 89000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 0,
            'active'                               => true,
        ]);

        // BOM for Latte:
        // 1.3m * 24.000 = 31.200
        // 0.022 roll * 18.000 = 396
        // 1 pcs * 600 = 600
        // 1 pcs * 900 = 900
        // 1 pcs * 800 = 800
        // 1 pcs * 300 = 300
        // Total material = 34.196, Direct = 5000 + 1000 = 6000 -> Grand Total = 40.196
        BomItem::create(['variant_id' => $this->variantLatte->id, 'material_id' => $matPremium->id, 'qty_per_unit' => 1.3]);
        BomItem::create(['variant_id' => $this->variantLatte->id, 'material_id' => $matBenang->id, 'qty_per_unit' => 0.022]);
        BomItem::create(['variant_id' => $this->variantLatte->id, 'material_id' => $matLabel->id, 'qty_per_unit' => 1.0]);
        BomItem::create(['variant_id' => $this->variantLatte->id, 'material_id' => $matPolybag->id, 'qty_per_unit' => 1.0]);
        BomItem::create(['variant_id' => $this->variantLatte->id, 'material_id' => $matHangtag->id, 'qty_per_unit' => 1.0]);
        BomItem::create(['variant_id' => $this->variantLatte->id, 'material_id' => $matStiker->id, 'qty_per_unit' => 1.0]);

        $this->variantNoBom = ProductVariant::create([
            'product_id'                           => $prodSegiEmpat->id,
            'variant_name'                         => 'Navy Polos',
            'sku'                                  => 'HJB-SO-NV-099',
            'base_selling_price'                   => 85000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 0,
            'active'                               => true,
        ]);
    }

    /**
     * 1. Order edit receives Variant Reference HPP in view data.
     */
    public function test_order_edit_receives_variant_reference_hpp(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-TEST1',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 105000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.edit', $order));

        $response->assertOk();
        $response->assertViewHas('variantsPayload');
        $payload = $response->viewData('variantsPayload');
        $gs = collect($payload)->firstWhere('sku', 'HJB-VM-GS-002');
        $this->assertNotNull($gs);
        $this->assertEquals('56160.00', $gs['reference_cost']);
        $this->assertEquals('HPP / Modal Acuan', $gs['reference_cost_label']);
    }

    /**
     * 2. Existing selected item does not display Reference HPP, master price, or margin on edit.
     */
    public function test_existing_selected_item_shows_reference_hpp_and_margin(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-TEST2',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 105000,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantLatte->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 89000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.edit', $order));

        $response->assertOk();
        $response->assertDontSee('HPP Acuan:');
        $response->assertDontSee('Harga Master:');
        $response->assertDontSee('Margin:');
        $response->assertSee('Harga Jual');
        $response->assertSee('Subtotal');
        $response->assertDontSee('-Rp0 / pcs');
        $response->assertDontSee('-Rp0');
        $response->assertDontSee('(HPP / Modal Acuan)');
        $response->assertDontSee('Harga Transaksi');
    }

    /**
     * 3. Dynamic selected item in create form receives Reference HPP in JSON dataset but hides financial cards.
     */
    public function test_dynamic_selected_item_has_reference_hpp_in_dataset(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.create'));

        $response->assertOk();
        $response->assertViewHas('variantsPayload');
        $payload = $response->viewData('variantsPayload');

        $gs = collect($payload)->firstWhere('sku', 'HJB-VM-GS-002');
        $this->assertNotNull($gs);
        $this->assertEquals('56160.00', $gs['reference_cost']);

        $lt = collect($payload)->firstWhere('sku', 'HJB-SO-LT-001');
        $this->assertNotNull($lt);
        $this->assertEquals('40196.00', $lt['reference_cost']);

        $response->assertSee('Harga Jual');
        $response->assertSee('Subtotal');
        $response->assertDontSee('HPP Acuan:');
        $response->assertDontSee('Harga Master:');
        $response->assertDontSee('Margin:');
        $response->assertDontSee('(HPP / Modal Acuan)');
        $response->assertDontSee('Harga Transaksi');
    }

    /**
     * 4. Margin per unit calculates correctly: transaction price - reference HPP.
     */
    public function test_margin_per_unit_calculates_correctly(): void
    {
        $service = app(VariantCostCalculationService::class);

        // Geometric Sage: 105.000 - 56.160 = +48.840
        $evalGs = $service->evaluatePriceMargin($this->variantGeometricSage, 105000);
        $this->assertTrue($evalGs['has_reference']);
        $this->assertEquals('48840.00', $evalGs['margin']);
        $this->assertEquals(48840.0, $evalGs['margin_per_unit']);

        // Latte: 89.000 - 40.196 = +48.804
        $evalLt = $service->evaluatePriceMargin($this->variantLatte, 89000);
        $this->assertTrue($evalLt['has_reference']);
        $this->assertEquals('48804.00', $evalLt['margin']);
        $this->assertEquals(48804.0, $evalLt['margin_per_unit']);
    }

    /**
     * 5. HPP unavailable does not show HPP Acuan on order show view.
     */
    public function test_hpp_unavailable_shows_belum_dapat_dihitung_not_rp0(): void
    {
        $service = app(VariantCostCalculationService::class);
        $evalNoBom = $service->evaluatePriceMargin($this->variantNoBom, 85000);

        $this->assertFalse($evalNoBom['has_reference']);
        $this->assertNull($evalNoBom['reference_cost']);
        $this->assertNull($evalNoBom['margin']);
        $this->assertNull($evalNoBom['margin_per_unit']);

        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-NOBOM',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantNoBom->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 85000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));
        $response->assertOk();
        $response->assertDontSee('HPP Acuan');
        $response->assertDontSee('Margin');
        $response->assertDontSee('Rp0');
        $response->assertDontSee('-Rp0');
        $response->assertDontSee('-Rp0 / pcs');
    }

    /**
     * 6. Estimated order HPP = SUM(qty × reference HPP).
     * Geometric Sage: 30 × 56.160 = 1.684.800
     * Latte: 50 × 40.196 = 2.009.800
     * Total = 3.694.600
     */
    public function test_estimated_order_hpp_equals_sum_of_qty_times_reference_hpp(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-UAT',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 105000,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantLatte->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 89000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertViewHas('orderEstimation');
        $estimation = $response->viewData('orderEstimation');

        $this->assertTrue($estimation['is_available']);
        $this->assertEquals('3694600.00', $estimation['estimated_reference_hpp']);
    }

    /**
     * 7. Estimated gross profit calculated in backend while profitability section is removed from show UI.
     * Revenue: (30 × 105.000) + (50 × 89.000) = 7.600.000
     * Estimated Reference HPP: 3.694.600
     * Estimated Gross Profit: 3.905.400
     */
    public function test_estimated_gross_profit_equals_revenue_minus_estimated_hpp(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-PROFIT',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 105000,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantLatte->id,
            'qty_ordered'        => 50,
            'unit_selling_price' => 89000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $estimation = $response->viewData('orderEstimation');

        $this->assertEquals('7600000.00', $estimation['revenue_amount']);
        $this->assertEquals('3694600.00', $estimation['estimated_reference_hpp']);
        $this->assertEquals('3905400.00', $estimation['estimated_gross_profit']);
        $this->assertEquals('51.38', $estimation['estimated_gross_margin_percent']);

        // Order Total is visible, while profitability cards are removed from UI
        $response->assertSee('Rp 7.600.000');
        $response->assertDontSee('RINGKASAN NILAI PESANAN');
        $response->assertDontSee('Estimasi HPP');
        $response->assertDontSee('Estimasi Laba');
    }

    /**
     * 8. Actual HPP is not displayed on Sales Order show UI.
     */
    public function test_actual_hpp_remains_belum_final_before_final_production(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-ACTUAL-NOTREADY',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::DEAL,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 30,
            'unit_selling_price' => 105000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('HPP Aktual');
        $response->assertDontSee('RINGKASAN NILAI PESANAN');
        $response->assertSee($order->order_no);
    }

    /**
     * 9. Backend estimation computed but removed from Sales Order UI before production.
     */
    public function test_estimated_hpp_remains_visible_before_production(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-BEFORE-PROD',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 105000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $estimation = $response->viewData('orderEstimation');
        $this->assertEquals('561600.00', $estimation['estimated_reference_hpp']);

        $response->assertDontSee('Estimasi Sebelum Produksi');
        $response->assertDontSee('Estimasi HPP');
        $response->assertDontSee('RINGKASAN NILAI PESANAN');
    }

    /**
     * 10. Final actual HPP backend calculation intact while Sales Order UI remains clean.
     */
    public function test_final_actual_hpp_remains_separate_from_estimated_hpp(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-FINAL',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::SELESAI,
        ]);
        $orderItem = $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 105000,
        ]);

        $batch = ProductionBatch::create([
            'batch_no'                                      => 'PB-202610-0001',
            'order_item_id'                                 => $orderItem->id,
            'target_qty'                                    => 10,
            'status'                                        => BatchStatus::SELESAI,
            'requires_printing'                             => false,
            'printing_cost_per_unit_snapshot'               => 0,
            'direct_labor_cost_per_unit_snapshot'           => 5000,
            'packaging_process_cost_per_good_unit_snapshot' => 1000,
            'other_direct_cost_per_unit_snapshot'           => 0,
            'material_usage_recorded_at'                    => now(),
            'material_usage_recorded_by'                    => $this->admin->id,
            'planned_start_date'                            => now()->toDateString(),
            'planned_end_date'                              => now()->toDateString(),
        ]);

        $prodResult = \App\Models\Production\ProductionResult::create([
            'batch_id'    => $batch->id,
            'actual_qty'  => 10,
            'result_date' => now()->toDateString(),
            'recorded_by' => $this->admin->id,
        ]);

        QcResult::create([
            'production_result_id' => $prodResult->id,
            'good_qty'             => 10,
            'reject_qty'           => 0,
            'qc_date'              => now()->toDateString(),
            'qc_at'                => now(),
            'qc_by'                => $this->admin->id,
        ]);

        // Batch finished without additional unlinked material movements

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $profitability = $response->viewData('profitability');
        $estimation = $response->viewData('orderEstimation');

        // Both sections exist and have distinct values in backend
        $this->assertEquals('561600.00', $estimation['estimated_reference_hpp']);
        $this->assertTrue($profitability['is_ready']);
        $this->assertNotNull($profitability['hpp_amount']);

        // Sales Order UI remains clean and operational
        $response->assertDontSee('Selisih HPP');
        $response->assertDontSee('RINGKASAN NILAI PESANAN');
        $response->assertSee('Total Keseluruhan');
    }

    /**
     * 11. HPP price floor remains active during store and update.
     */
    public function test_hpp_price_floor_remains_active(): void
    {
        // Geometric Sage Reference HPP = 56.160
        // Price of 50.000 should be rejected
        $createData = [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'items'       => [
                [
                    'variant_id'         => $this->variantGeometricSage->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 50000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.orders.store'), $createData);
        $response->assertSessionHasErrors(['items.0.variant_id']);
        $this->assertDatabaseMissing('orders', ['customer_id' => $this->customer->id]);

        // Existing order update should also enforce price floor
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-FLOOR',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $item = $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 10,
            'unit_selling_price' => 105000,
        ]);

        $updateData = [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'items'       => [
                [
                    'order_item_id'      => $item->id,
                    'variant_id'         => $this->variantGeometricSage->id,
                    'qty_ordered'        => 10,
                    'unit_selling_price' => 56000, // lower than 56.160
                ],
            ],
        ];

        $updateResponse = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updateData);
        $updateResponse->assertSessionHasErrors(['items.0.variant_id']);
    }

    /**
     * 12. Historical transaction prices unchanged when updating order metadata.
     */
    public function test_historical_transaction_prices_unchanged(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_no'    => 'SO-202610-HISTORICAL',
            'order_date'  => now()->toDateString(),
            'status'      => OrderStatus::KOMUNIKASI,
        ]);
        $item = $order->items()->create([
            'variant_id'         => $this->variantGeometricSage->id,
            'qty_ordered'        => 25,
            'unit_selling_price' => 110000, // Custom negotiated historical price
        ]);

        // Admin updates customer or notes, leaving unit_selling_price empty or submitted as existing
        $updateData = [
            'customer_id' => $this->customer->id,
            'order_date'  => now()->toDateString(),
            'notes'       => 'Catatan khusus negosiasi',
            'items'       => [
                [
                    'order_item_id'      => $item->id,
                    'variant_id'         => $this->variantGeometricSage->id,
                    'qty_ordered'        => 25,
                    'unit_selling_price' => '', // blank submitted price preserves snapshot
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.orders.update', $order), $updateData);
        $response->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertEquals('110000.00', $order->items->first()->unit_selling_price);
    }
}
