<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Master\BomItem;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Unit;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProductVariant $variant;
    private Material $material;
    private Material $inactiveMaterial;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $product       = Product::create(['product_name' => 'Hijab Segi Empat', 'active' => true]);
        $this->variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Voal Premium',
            'sku'                => 'SKU-VOAL-001',
            'base_selling_price' => 85000,
            'active'             => true,
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Voal Cotton',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        $this->inactiveMaterial = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Discontinued',
            'min_stock'     => 0,
            'active'        => false,
        ]);
    }

    public function test_admin_can_add_material_to_bom(): void
    {
        $payload = [
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.1500,
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.store', $this->variant),
            $payload
        );

        $response->assertRedirect(route('admin.variants.show', $this->variant));
        $this->assertDatabaseHas('bom_items', [
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.1500,
        ]);
    }

    public function test_qty_per_unit_must_be_greater_than_zero(): void
    {
        $payload = [
            'material_id'  => $this->material->id,
            'qty_per_unit' => 0,
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.store', $this->variant),
            $payload
        );

        $response->assertSessionHasErrors('qty_per_unit');
    }

    public function test_duplicate_material_in_same_variant_is_rejected_with_validation_error(): void
    {
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.15,
        ]);

        $payload = [
            'material_id'  => $this->material->id,
            'qty_per_unit' => 2.0,
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.variants.bom.store', $this->variant),
            $payload
        );

        $response->assertSessionHasErrors('material_id');
    }

    public function test_only_active_materials_are_listed_on_variant_detail_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.variants.show', $this->variant));

        $response->assertStatus(200);
        $response->assertSee('Kain Voal Cotton');
        $response->assertDontSee('Kain Discontinued');
    }

    public function test_cross_variant_bom_update_is_rejected(): void
    {
        $otherProduct = Product::create(['product_name' => 'Produk Lain', 'active' => true]);
        $otherVariant = ProductVariant::create([
            'product_id'   => $otherProduct->id,
            'variant_name' => 'Varian Lain',
            'sku'          => 'SKU-OTHER-001',
            'active'       => true,
        ]);

        $bomItemOther = BomItem::create([
            'variant_id'   => $otherVariant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.5,
        ]);

        // Attempting to update $bomItemOther (which belongs to $otherVariant) using $this->variant in URL
        $response = $this->actingAs($this->admin)->put(
            "/admin/variants/{$this->variant->id}/bom/{$bomItemOther->id}",
            ['qty_per_unit' => 3.0]
        );

        $response->assertStatus(404);
        $bomItemOther->refresh();
        $this->assertEquals(1.5, $bomItemOther->qty_per_unit);
    }

    public function test_cross_variant_bom_delete_is_rejected(): void
    {
        $otherProduct = Product::create(['product_name' => 'Produk Lain 2', 'active' => true]);
        $otherVariant = ProductVariant::create([
            'product_id'   => $otherProduct->id,
            'variant_name' => 'Varian Lain 2',
            'sku'          => 'SKU-OTHER-002',
            'active'       => true,
        ]);

        $bomItemOther = BomItem::create([
            'variant_id'   => $otherVariant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => 1.5,
        ]);

        // Attempting to delete $bomItemOther using $this->variant in URL
        $response = $this->actingAs($this->admin)->delete(
            "/admin/variants/{$this->variant->id}/bom/{$bomItemOther->id}"
        );

        $response->assertStatus(404);
        $this->assertDatabaseHas('bom_items', ['id' => $bomItemOther->id]);
    }

    public function test_variant_detail_page_renders_human_friendly_bom_quantities(): void
    {
        $unitRoll = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);
        $unitPcs  = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $thread = Material::create([
            'unit_id'       => $unitRoll->id,
            'material_name' => 'Benang Jahit',
            'min_stock'     => 5,
            'active'        => true,
        ]);

        $label = Material::create([
            'unit_id'       => $unitPcs->id,
            'material_name' => 'Label Hijab',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        // 1.2900 m
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $this->material->id,
            'qty_per_unit' => '1.2900',
        ]);

        // 0.0200 roll
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $thread->id,
            'qty_per_unit' => '0.0200',
        ]);

        // 1.0000 pcs
        BomItem::create([
            'variant_id'   => $this->variant->id,
            'material_id'  => $label->id,
            'qty_per_unit' => '1.0000',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.variants.show', $this->variant));

        $response->assertStatus(200);

        // Verify human-friendly formatted values are present
        $response->assertSee('1,29');
        $response->assertSee('0,02');
        $response->assertSee('1');

        // Verify unnecessary trailing decimal zeroes are not rendered
        $response->assertDontSee('1,2900');
        $response->assertDontSee('0,0200');
        $response->assertDontSee('1,0000');
        $response->assertDontSee('1,0000 pcs');

        // Verify simplified column label is present
        $response->assertSee('Kebutuhan');
        $response->assertDontSee('Kebutuhan / 1 Pcs');

        // Verify full product name is rendered without truncation
        $response->assertSee('Hijab Segi Empat');

        // Verify Rumus Batch strip is removed
        $response->assertDontSee('Rumus Batch:');
        $response->assertDontSee('Rumus Batch');

        // Verify Edit Tarif button exists and links to variant edit
        $response->assertSee('Edit Tarif');
        $response->assertSee(route('admin.variants.edit', $this->variant));
    }
}
