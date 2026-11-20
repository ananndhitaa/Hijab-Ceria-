<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Master\BomItem;
use App\Models\Master\BomTemplate;
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
use App\Services\Production\BatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyBomTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $pashminaProduct;
    private Product $voalProduct;
    private ProductVariant $variantHitam;
    private ProductVariant $variantMocca;
    private ProductVariant $variantVoal;
    private Material $kainKaos;
    private Material $benang;
    private Material $polybag;
    private BomTemplate $templatePashmina;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $unitM = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $unitRoll = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);
        $unitPcs = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $this->kainKaos = Material::create([
            'unit_id'       => $unitM->id,
            'material_name' => 'Kain Kaos Rayon Hitam',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        $this->benang = Material::create([
            'unit_id'       => $unitRoll->id,
            'material_name' => 'Benang Jahit 40/2',
            'min_stock'     => 5,
            'active'        => true,
        ]);

        $this->polybag = Material::create([
            'unit_id'       => $unitPcs->id,
            'material_name' => 'Polybag Kemasan',
            'min_stock'     => 20,
            'active'        => true,
        ]);

        // Products
        $this->pashminaProduct = Product::create(['product_name' => 'Pashmina Kaos', 'active' => true]);
        $this->voalProduct     = Product::create(['product_name' => 'Hijab Voal Polos', 'active' => true]);

        // Pashmina Variants
        $this->variantHitam = ProductVariant::create([
            'product_id'                           => $this->pashminaProduct->id,
            'variant_name'                         => 'Hitam',
            'sku'                                  => 'PSH-KS-001',
            'base_selling_price'                   => 50000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 4000,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 500,
            'active'                               => true,
        ]);

        $this->variantMocca = ProductVariant::create([
            'product_id'                           => $this->pashminaProduct->id,
            'variant_name'                         => 'Mocca',
            'sku'                                  => 'PSH-KS-002',
            'base_selling_price'                   => 50000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 4000,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 500,
            'active'                               => true,
        ]);

        // Voal Variant
        $this->variantVoal = ProductVariant::create([
            'product_id'                           => $this->voalProduct->id,
            'variant_name'                         => 'Navy',
            'sku'                                  => 'HJB-VP-003',
            'base_selling_price'                   => 85000,
            'requires_printing'                    => false,
            'printing_cost_per_unit'               => 0,
            'direct_labor_cost_per_unit'           => 5000,
            'packaging_process_cost_per_good_unit' => 1000,
            'other_direct_cost_per_unit'           => 500,
            'active'                               => true,
        ]);

        // Create approved template for Pashmina Kaos
        $this->templatePashmina = BomTemplate::create([
            'product_id'  => $this->pashminaProduct->id,
            'name'        => 'Pashmina Kaos Standard',
            'description' => 'Resep standar pashmina kaos',
            'is_active'   => true,
        ]);

        $this->templatePashmina->items()->createMany([
            ['material_id' => $this->kainKaos->id, 'qty_per_unit' => '1.7500'],
            ['material_id' => $this->benang->id,   'qty_per_unit' => '0.0250'],
            ['material_id' => $this->polybag->id,  'qty_per_unit' => '1.0000'],
        ]);
    }

    public function test_empty_variant_receives_exact_copied_bom_items(): void
    {
        $this->assertEquals(0, $this->variantHitam->bomItems()->count());

        $response = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantHitam),
            ['bom_template_id' => $this->templatePashmina->id]
        );

        $response->assertRedirect(route('admin.variants.show', $this->variantHitam));
        $this->variantHitam->refresh();

        $bomItems = $this->variantHitam->bomItems->keyBy('material_id');
        $this->assertCount(3, $bomItems);

        $this->assertEquals('1.7500', $bomItems[$this->kainKaos->id]->qty_per_unit);
        $this->assertEquals('0.0250', $bomItems[$this->benang->id]->qty_per_unit);
        $this->assertEquals('1.0000', $bomItems[$this->polybag->id]->qty_per_unit);
    }

    public function test_product_scoping_prevents_applying_template_to_unrelated_product_variant(): void
    {
        // Attempt to apply Pashmina Kaos template to Voal variant
        $response = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantVoal),
            ['bom_template_id' => $this->templatePashmina->id]
        );

        $response->assertSessionHasErrors('bom_template_id');
        $this->assertEquals(0, $this->variantVoal->bomItems()->count());
    }

    public function test_variant_show_only_receives_templates_belonging_to_its_product(): void
    {
        // Template for Voal
        $templateVoal = BomTemplate::create([
            'product_id' => $this->voalProduct->id,
            'name'       => 'Voal Standard',
            'is_active'  => true,
        ]);
        $templateVoal->items()->create([
            'material_id'  => $this->kainKaos->id,
            'qty_per_unit' => '1.2500',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.variants.show', $this->variantHitam));
        $response->assertOk();

        // Pashmina template must be present in view
        $response->assertSee('Pashmina Kaos Standard');

        // Voal template must NOT be present in view for Pashmina variant
        $response->assertDontSee('Voal Standard');
    }

    public function test_existing_bom_requires_explicit_replacement_confirmation(): void
    {
        // Manually create existing BOM item
        $this->variantHitam->bomItems()->create([
            'material_id'  => $this->kainKaos->id,
            'qty_per_unit' => '1.5000',
        ]);

        // Attempt to apply without replace_existing confirmation
        $response = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantHitam),
            ['bom_template_id' => $this->templatePashmina->id]
        );

        $response->assertSessionHasErrors('replace_existing');

        // Existing BOM must remain untouched
        $this->variantHitam->refresh();
        $this->assertCount(1, $this->variantHitam->bomItems);
        $this->assertEquals('1.5000', $this->variantHitam->bomItems->first()->qty_per_unit);
    }

    public function test_replacement_with_confirmation_is_atomic(): void
    {
        // Old BOM has kain with 1.5000
        $this->variantHitam->bomItems()->create([
            'material_id'  => $this->kainKaos->id,
            'qty_per_unit' => '1.5000',
        ]);

        // Apply with replace_existing = 1
        $response = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantHitam),
            [
                'bom_template_id'  => $this->templatePashmina->id,
                'replace_existing' => '1',
            ]
        );

        $response->assertRedirect(route('admin.variants.show', $this->variantHitam));
        $this->variantHitam->refresh();

        // Now has exactly 3 items from template, old 1.5000 is replaced by 1.7500
        $this->assertCount(3, $this->variantHitam->bomItems);
        $kainItem = $this->variantHitam->bomItems->where('material_id', $this->kainKaos->id)->first();
        $this->assertEquals('1.7500', $kainItem->qty_per_unit);
    }

    public function test_variants_own_independent_copied_bom_rows(): void
    {
        // Apply template to Hitam
        $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantHitam),
            ['bom_template_id' => $this->templatePashmina->id]
        );

        // Apply template to Mocca
        $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantMocca),
            ['bom_template_id' => $this->templatePashmina->id]
        );

        $this->variantHitam->refresh();
        $this->variantMocca->refresh();

        $this->assertCount(3, $this->variantHitam->bomItems);
        $this->assertCount(3, $this->variantMocca->bomItems);

        // Modify Hitam's kain quantity
        $hitamKain = $this->variantHitam->bomItems->where('material_id', $this->kainKaos->id)->first();
        $this->actingAs($this->admin)->put(
            route('admin.variants.bom.update', [$this->variantHitam, $hitamKain]),
            ['qty_per_unit' => '2.1000']
        );

        $this->variantHitam->refresh();
        $this->variantMocca->refresh();

        // Hitam is updated to 2.1000
        $this->assertEquals('2.1000', $this->variantHitam->bomItems->where('material_id', $this->kainKaos->id)->first()->qty_per_unit);

        // Mocca remains untouched at 1.7500
        $this->assertEquals('1.7500', $this->variantMocca->bomItems->where('material_id', $this->kainKaos->id)->first()->qty_per_unit);
    }

    public function test_changing_template_later_does_not_mutate_already_applied_variants(): void
    {
        // Apply template to Hitam
        $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantHitam),
            ['bom_template_id' => $this->templatePashmina->id]
        );

        // Update template's kain quantity
        $templateKain = $this->templatePashmina->items->where('material_id', $this->kainKaos->id)->first();
        $this->actingAs($this->admin)->put(
            route('admin.bom-templates.items.update', [$this->templatePashmina, $templateKain]),
            ['qty_per_unit' => '3.0000']
        );

        $this->variantHitam->refresh();

        // Hitam must still have 1.7500
        $this->assertEquals('1.7500', $this->variantHitam->bomItems->where('material_id', $this->kainKaos->id)->first()->qty_per_unit);
    }

    public function test_cannot_apply_inactive_or_empty_template(): void
    {
        // Inactive template
        $inactiveTpl = BomTemplate::create([
            'product_id' => $this->pashminaProduct->id,
            'name'       => 'Inactive Tpl',
            'is_active'  => false,
        ]);
        $inactiveTpl->items()->create(['material_id' => $this->kainKaos->id, 'qty_per_unit' => '1.0000']);

        $resInactive = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantHitam),
            ['bom_template_id' => $inactiveTpl->id]
        );
        $resInactive->assertSessionHasErrors('bom_template_id');

        // Empty template
        $emptyTpl = BomTemplate::create([
            'product_id' => $this->pashminaProduct->id,
            'name'       => 'Empty Tpl',
            'is_active'  => true,
        ]);

        $resEmpty = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantHitam),
            ['bom_template_id' => $emptyTpl->id]
        );
        $resEmpty->assertSessionHasErrors('bom_template_id');
    }

    public function test_batch_creation_from_applied_bom_generates_correct_snapshot_and_isolates_historical_data(): void
    {
        // 1. Apply template to Hitam
        $this->actingAs($this->admin)->post(
            route('admin.variants.bom.apply-template', $this->variantHitam),
            ['bom_template_id' => $this->templatePashmina->id]
        );

        // 2. Set up sales order and item
        $customer = Customer::create([
            'customer_name' => 'Toko Busana Muslimah',
            'phone'         => '081234567890',
            'address'       => 'Jl. Pasar Baru No. 12',
        ]);

        $order = Order::create([
            'order_no'      => 'ORD-2026-0001',
            'customer_id'   => $customer->id,
            'order_date'    => now(),
            'status'        => OrderStatus::DEAL,
            'total_amount'  => 5000000,
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $this->variantHitam->id,
            'qty_ordered'        => 100,
            'unit_selling_price' => 50000,
            'subtotal'           => 5000000,
        ]);

        $inv = \App\Models\Sales\Invoice::create([
            'order_id'     => $order->id,
            'invoice_no'   => 'INV-BOM-001',
            'invoice_date' => now()->toDateString(),
            'total_amount' => '5000000.00',
            'status'       => \App\Enums\InvoiceStatus::PARTIAL,
        ]);
        \App\Models\Sales\Payment::create([
            'invoice_id'   => $inv->id,
            'payment_no'   => 'PAY-BOM-001',
            'payment_date' => now()->toDateString(),
            'amount'       => '2500000.00',
            'payment_type' => \App\Enums\PaymentType::DP,
            'status'       => \App\Enums\PaymentStatus::CONFIRMED,
        ]);

        // 3. Create batch for 100 pcs via BatchService
        $batchService = app(BatchService::class);
        $result = $batchService->createBatch($order, [
            'order_item_id' => $orderItem->id,
            'target_qty'    => '100.0000',
        ], $this->admin);

        /** @var ProductionBatch $batch */
        $batch = $result['batch'];
        $this->assertNotNull($batch);

        // Verify batch_materials snapshot:
        // kain: 1.75 * 100 = 175.0000
        // benang: 0.025 * 100 = 2.5000
        // polybag: 1 * 100 = 100.0000
        $batchMaterials = BatchMaterial::where('batch_id', $batch->id)->get()->keyBy('material_id');
        $this->assertCount(3, $batchMaterials);

        $this->assertEquals('175.0000', $batchMaterials[$this->kainKaos->id]->required_qty);
        $this->assertEquals('2.5000', $batchMaterials[$this->benang->id]->required_qty);
        $this->assertEquals('100.0000', $batchMaterials[$this->polybag->id]->required_qty);

        // 4. Modify the template afterward
        $templateKain = $this->templatePashmina->items->where('material_id', $this->kainKaos->id)->first();
        $templateKain->update(['qty_per_unit' => '5.0000']);

        // 5. Modify the variant BOM afterward
        $variantKain = $this->variantHitam->bomItems->where('material_id', $this->kainKaos->id)->first();
        $variantKain->update(['qty_per_unit' => '6.0000']);

        // 6. Verify existing batch_materials snapshot is 100% untouched
        $refreshedBatchKain = BatchMaterial::where('batch_id', $batch->id)
            ->where('material_id', $this->kainKaos->id)
            ->first();

        $this->assertEquals('175.0000', $refreshedBatchKain->required_qty);
    }
}
