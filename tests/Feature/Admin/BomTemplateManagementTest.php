<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Master\BomTemplate;
use App\Models\Master\BomTemplateItem;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\Unit;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product1;
    private Product $product2;
    private Material $kain;
    private Material $benang;
    private Material $inactiveMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->product1 = Product::create(['product_name' => 'Pashmina Kaos', 'active' => true]);
        $this->product2 = Product::create(['product_name' => 'Hijab Voal Polos', 'active' => true]);

        $unitM = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $unitRoll = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);

        $this->kain = Material::create([
            'unit_id'       => $unitM->id,
            'material_name' => 'Kain Kaos Rayon',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        $this->benang = Material::create([
            'unit_id'       => $unitRoll->id,
            'material_name' => 'Benang Jahit Hitam',
            'min_stock'     => 5,
            'active'        => true,
        ]);

        $this->inactiveMaterial = Material::create([
            'unit_id'       => $unitM->id,
            'material_name' => 'Kain Discontinued',
            'min_stock'     => 0,
            'active'        => false,
        ]);
    }

    public function test_admin_can_create_template_for_a_product(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.bom-templates.store'), [
            'product_id'  => $this->product1->id,
            'name'        => 'Pashmina Kaos Standard',
            'description' => 'Resep standar untuk pashmina bahan kaos 180x75 cm',
        ]);

        $template = BomTemplate::where('name', 'Pashmina Kaos Standard')->first();
        $this->assertNotNull($template);
        $this->assertEquals($this->product1->id, $template->product_id);
        $this->assertTrue($template->is_active);

        $response->assertRedirect(route('admin.bom-templates.show', $template));
        $this->assertDatabaseHas('bom_templates', [
            'product_id' => $this->product1->id,
            'name'       => 'Pashmina Kaos Standard',
            'is_active'  => true,
        ]);
    }

    public function test_template_name_uniqueness_is_scoped_to_product(): void
    {
        // Create template for Product 1
        BomTemplate::create([
            'product_id'  => $this->product1->id,
            'name'        => 'Standard',
            'description' => 'Desc P1',
            'is_active'   => true,
        ]);

        // Duplicate name on SAME product must be rejected
        $response = $this->actingAs($this->admin)->post(route('admin.bom-templates.store'), [
            'product_id'  => $this->product1->id,
            'name'        => 'Standard',
            'description' => 'Duplicate on P1',
        ]);
        $response->assertSessionHasErrors('name');

        // SAME name on DIFFERENT product must be allowed
        $responseDiff = $this->actingAs($this->admin)->post(route('admin.bom-templates.store'), [
            'product_id'  => $this->product2->id,
            'name'        => 'Standard',
            'description' => 'Same name on P2',
        ]);
        $responseDiff->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bom_templates', [
            'product_id' => $this->product2->id,
            'name'       => 'Standard',
        ]);
    }

    public function test_admin_can_add_material_to_template(): void
    {
        $template = BomTemplate::create([
            'product_id' => $this->product1->id,
            'name'       => 'Pashmina Standard',
            'is_active'  => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.bom-templates.items.store', $template), [
            'material_id'  => $this->kain->id,
            'qty_per_unit' => '1.7500',
        ]);

        $response->assertRedirect(route('admin.bom-templates.show', $template));
        $this->assertDatabaseHas('bom_template_items', [
            'bom_template_id' => $template->id,
            'material_id'     => $this->kain->id,
            'qty_per_unit'    => '1.7500',
        ]);
    }

    public function test_duplicate_material_in_same_template_is_rejected(): void
    {
        $template = BomTemplate::create([
            'product_id' => $this->product1->id,
            'name'       => 'Pashmina Standard',
            'is_active'  => true,
        ]);

        $template->items()->create([
            'material_id'  => $this->kain->id,
            'qty_per_unit' => '1.7500',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.bom-templates.items.store', $template), [
            'material_id'  => $this->kain->id,
            'qty_per_unit' => '2.0000',
        ]);

        $response->assertSessionHasErrors('material_id');
        $this->assertCount(1, $template->items);
    }

    public function test_zero_or_negative_quantity_is_rejected(): void
    {
        $template = BomTemplate::create([
            'product_id' => $this->product1->id,
            'name'       => 'Pashmina Standard',
            'is_active'  => true,
        ]);

        // Zero qty
        $resZero = $this->actingAs($this->admin)->post(route('admin.bom-templates.items.store', $template), [
            'material_id'  => $this->kain->id,
            'qty_per_unit' => '0',
        ]);
        $resZero->assertSessionHasErrors('qty_per_unit');

        // Negative qty
        $resNeg = $this->actingAs($this->admin)->post(route('admin.bom-templates.items.store', $template), [
            'material_id'  => $this->kain->id,
            'qty_per_unit' => '-1.5',
        ]);
        $resNeg->assertSessionHasErrors('qty_per_unit');
    }

    public function test_inactive_material_is_rejected(): void
    {
        $template = BomTemplate::create([
            'product_id' => $this->product1->id,
            'name'       => 'Pashmina Standard',
            'is_active'  => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.bom-templates.items.store', $template), [
            'material_id'  => $this->inactiveMaterial->id,
            'qty_per_unit' => '1.0000',
        ]);

        $response->assertSessionHasErrors('material_id');
    }

    public function test_admin_can_update_item_quantity(): void
    {
        $template = BomTemplate::create([
            'product_id' => $this->product1->id,
            'name'       => 'Pashmina Standard',
            'is_active'  => true,
        ]);

        $item = $template->items()->create([
            'material_id'  => $this->kain->id,
            'qty_per_unit' => '1.5000',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.bom-templates.items.update', [$template, $item]), [
            'qty_per_unit' => '1.8000',
        ]);

        $response->assertRedirect(route('admin.bom-templates.show', $template));
        $item->refresh();
        $this->assertEquals('1.8000', $item->qty_per_unit);
    }

    public function test_admin_can_remove_item_from_template(): void
    {
        $template = BomTemplate::create([
            'product_id' => $this->product1->id,
            'name'       => 'Pashmina Standard',
            'is_active'  => true,
        ]);

        $item = $template->items()->create([
            'material_id'  => $this->kain->id,
            'qty_per_unit' => '1.5000',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.bom-templates.items.destroy', [$template, $item]));

        $response->assertRedirect(route('admin.bom-templates.show', $template));
        $this->assertDatabaseMissing('bom_template_items', ['id' => $item->id]);
    }

    public function test_nested_cross_template_item_mutation_returns_404(): void
    {
        $templateA = BomTemplate::create([
            'product_id' => $this->product1->id,
            'name'       => 'Template A',
            'is_active'  => true,
        ]);

        $templateB = BomTemplate::create([
            'product_id' => $this->product2->id,
            'name'       => 'Template B',
            'is_active'  => true,
        ]);

        // Item belongs to Template B
        $itemB = $templateB->items()->create([
            'material_id'  => $this->kain->id,
            'qty_per_unit' => '1.2000',
        ]);

        // Attempting to delete itemB through templateA's URL must fail with 404
        $deleteResponse = $this->actingAs($this->admin)->delete(
            route('admin.bom-templates.items.destroy', ['bomTemplate' => $templateA->id, 'item' => $itemB->id])
        );
        $deleteResponse->assertNotFound();
        $this->assertDatabaseHas('bom_template_items', ['id' => $itemB->id]);

        // Attempting to update itemB through templateA's URL must fail with 404
        $updateResponse = $this->actingAs($this->admin)->put(
            route('admin.bom-templates.items.update', ['bomTemplate' => $templateA->id, 'item' => $itemB->id]),
            ['qty_per_unit' => '9.9999']
        );
        $updateResponse->assertNotFound();
        $itemB->refresh();
        $this->assertEquals('1.2000', $itemB->qty_per_unit);
    }

    public function test_admin_can_toggle_template_active_status(): void
    {
        $template = BomTemplate::create([
            'product_id' => $this->product1->id,
            'name'       => 'Template Toggle',
            'is_active'  => true,
        ]);

        $this->actingAs($this->admin)->post(route('admin.bom-templates.toggle-active', $template));
        $template->refresh();
        $this->assertFalse($template->is_active);

        $this->actingAs($this->admin)->post(route('admin.bom-templates.toggle-active', $template));
        $template->refresh();
        $this->assertTrue($template->is_active);
    }
}
