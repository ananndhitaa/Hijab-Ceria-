<?php

namespace Tests\Feature\Gudang;

use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Production\BatchMaterial;
use App\Models\Master\BomItem;
use App\Models\Master\Material;
use App\Models\Inventory\MaterialStock;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Production\ProductionBatch;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private Unit $meterUnit;
    private Unit $pcsUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->pcsUnit   = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);
    }

    public function test_gudang_can_list_materials_and_filter_by_status(): void
    {
        $m1 = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Voal',
            'min_stock'     => 50,
            'active'        => true,
        ]);
        MaterialStock::create(['material_id' => $m1->id, 'qty_on_hand' => 100, 'avg_unit_cost' => 35000]);

        $m2 = Material::create([
            'unit_id'       => $this->pcsUnit->id,
            'material_name' => 'Label Hijab',
            'min_stock'     => 500,
            'active'        => false,
        ]);
        MaterialStock::create(['material_id' => $m2->id, 'qty_on_hand' => 200, 'avg_unit_cost' => 500]);

        // Filter by search
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.materials.index', ['search' => 'Kain']));
        $response->assertOk();
        $response->assertSee('Kain Voal');
        $response->assertDontSee('Label Hijab');

        // Filter by active status
        $responseActive = $this->actingAs($this->gudangUser)->get(route('gudang.materials.index', ['active' => '1']));
        $responseActive->assertOk();
        $responseActive->assertSee('Kain Voal');
        $responseActive->assertDontSee('Label Hijab');
    }

    public function test_gudang_can_create_material_and_initializes_stock_record(): void
    {
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.materials.store'), [
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Jersey Premium',
            'min_stock'     => 30.5,
            'active'        => 1,
        ]);

        $material = Material::where('material_name', 'Kain Jersey Premium')->first();
        $this->assertNotNull($material);

        $response->assertRedirect(route('gudang.materials.show', $material));
        $response->assertSessionHas('success', 'Material berhasil ditambahkan.');

        $this->assertDatabaseHas('materials', [
            'id'            => $material->id,
            'material_name' => 'Kain Jersey Premium',
            'min_stock'     => '30.5000',
            'active'        => true,
        ]);

        $this->assertDatabaseHas('material_stocks', [
            'material_id'   => $material->id,
            'qty_on_hand'   => '0.0000',
            'avg_unit_cost' => '0.0000',
        ]);
    }

    public function test_material_validation_fails_for_invalid_inputs(): void
    {
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.materials.store'), [
            'unit_id'       => 99999, // non-existent
            'material_name' => '',
            'min_stock'     => -10,
        ]);

        $response->assertSessionHasErrors(['unit_id', 'material_name', 'min_stock']);
    }

    public function test_gudang_can_view_material_detail(): void
    {
        $material = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Voal Premium',
            'min_stock'     => 50,
        ]);
        MaterialStock::create(['material_id' => $material->id, 'qty_on_hand' => 200]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.materials.show', $material));
        $response->assertOk();
        $response->assertSee('Kain Voal Premium');
        $response->assertSee('200 m');
        $response->assertSee('AMAN');
    }

    public function test_gudang_can_update_material(): void
    {
        $material = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Voal Lama',
            'min_stock'     => 50,
        ]);
        MaterialStock::create(['material_id' => $material->id, 'qty_on_hand' => 200]);

        $response = $this->actingAs($this->gudangUser)->put(route('gudang.materials.update', $material), [
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Voal Baru',
            'min_stock'     => 60,
            'active'        => 1,
        ]);

        $response->assertRedirect(route('gudang.materials.show', $material));
        $response->assertSessionHas('success', 'Perubahan material berhasil disimpan.');

        $this->assertDatabaseHas('materials', [
            'id'            => $material->id,
            'material_name' => 'Kain Voal Baru',
            'min_stock'     => '60.0000',
        ]);
    }

    public function test_gudang_can_toggle_material_active_status(): void
    {
        $material = Material::create([
            'unit_id'       => $this->meterUnit->id,
            'material_name' => 'Kain Sutra',
            'min_stock'     => 10,
            'active'        => true,
        ]);

        // Toggle to inactive
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.materials.toggle-active', $material));
        $response->assertSessionHas('success', 'Material berhasil dinonaktifkan.');
        $this->assertFalse($material->fresh()->active);

        // Toggle back to active
        $response2 = $this->actingAs($this->gudangUser)->post(route('gudang.materials.toggle-active', $material));
        $response2->assertSessionHas('success', 'Material berhasil diaktifkan.');
        $this->assertTrue($material->fresh()->active);
    }

    public function test_stock_status_logic_aman_rendah_habis(): void
    {
        // 1. AMAN: stock > min_stock
        $mAman = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Aman', 'min_stock' => 50]);
        MaterialStock::create(['material_id' => $mAman->id, 'qty_on_hand' => 60]);
        $this->assertEquals('AMAN', $mAman->fresh(['stock'])->stock_status);
        $this->assertEquals('badge-success', $mAman->fresh(['stock'])->stock_status_badge);

        // 2. RENDAH: stock > 0 AND stock <= min_stock
        $mRendah = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Rendah', 'min_stock' => 50]);
        MaterialStock::create(['material_id' => $mRendah->id, 'qty_on_hand' => 50]);
        $this->assertEquals('RENDAH', $mRendah->fresh(['stock'])->stock_status);
        $this->assertEquals('badge-warning', $mRendah->fresh(['stock'])->stock_status_badge);

        $mRendah2 = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Rendah 2', 'min_stock' => 50]);
        MaterialStock::create(['material_id' => $mRendah2->id, 'qty_on_hand' => 10]);
        $this->assertEquals('RENDAH', $mRendah2->fresh(['stock'])->stock_status);

        // 3. HABIS: stock <= 0
        $mHabis = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Habis', 'min_stock' => 50]);
        MaterialStock::create(['material_id' => $mHabis->id, 'qty_on_hand' => 0]);
        $this->assertEquals('HABIS', $mHabis->fresh(['stock'])->stock_status);
        $this->assertEquals('badge-danger', $mHabis->fresh(['stock'])->stock_status_badge);
    }

    public function test_material_deletion_blocked_if_has_stock_on_hand(): void
    {
        $material = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Stocked', 'min_stock' => 10]);
        MaterialStock::create(['material_id' => $material->id, 'qty_on_hand' => 15]);

        $response = $this->actingAs($this->gudangUser)->delete(route('gudang.materials.destroy', $material));
        $response->assertRedirect(route('gudang.materials.index'));
        $response->assertSessionHas('error', 'Material tidak dapat dihapus karena memiliki riwayat transaksi/stok/BOM. Anda dapat menonaktifkannya.');
        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    public function test_material_deletion_blocked_if_referenced_in_bom(): void
    {
        $material = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat in BOM', 'min_stock' => 10]);
        MaterialStock::create(['material_id' => $material->id, 'qty_on_hand' => 0]);

        $product = Product::create(['product_name' => 'Hijab Segiempat', 'active' => true]);
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'HJB-SGM-001',
            'base_selling_price' => 50000,
        ]);

        BomItem::create([
            'variant_id'   => $variant->id,
            'material_id'  => $material->id,
            'qty_per_unit' => 1.5,
        ]);

        $response = $this->actingAs($this->gudangUser)->delete(route('gudang.materials.destroy', $material));
        $response->assertRedirect(route('gudang.materials.index'));
        $response->assertSessionHas('error', 'Material tidak dapat dihapus karena memiliki riwayat transaksi/stok/BOM. Anda dapat menonaktifkannya.');
        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    public function test_material_deletion_blocked_if_referenced_in_stock_movements(): void
    {
        $material = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Movement', 'min_stock' => 10]);
        MaterialStock::create(['material_id' => $material->id, 'qty_on_hand' => 0]);

        StockMovement::create([
            'material_id'        => $material->id,
            'movement_type'      => MovementType::IN->value,
            'qty'                => 10,
            'unit_cost_snapshot' => 1000,
            'total_cost_snapshot'=> 10000,
            'movement_date'      => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->gudangUser)->delete(route('gudang.materials.destroy', $material));
        $response->assertRedirect(route('gudang.materials.index'));
        $response->assertSessionHas('error', 'Material tidak dapat dihapus karena memiliki riwayat transaksi/stok/BOM. Anda dapat menonaktifkannya.');
        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    public function test_material_deletion_succeeds_when_completely_unused(): void
    {
        $material = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Unused', 'min_stock' => 10]);
        MaterialStock::create(['material_id' => $material->id, 'qty_on_hand' => 0]);

        $response = $this->actingAs($this->gudangUser)->delete(route('gudang.materials.destroy', $material));
        $response->assertRedirect(route('gudang.materials.index'));
        $response->assertSessionHas('success', 'Material berhasil dihapus.');
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public function test_material_edit_origin_index_renders_cancel_to_index(): void
    {
        $material = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Test', 'min_stock' => 10]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.materials.edit', ['material' => $material, 'from' => 'index']));
        $response->assertOk();
        $response->assertSee(route('gudang.materials.index'));
    }

    public function test_material_edit_origin_show_renders_cancel_to_show(): void
    {
        $material = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Test', 'min_stock' => 10]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.materials.edit', ['material' => $material, 'from' => 'show']));
        $response->assertOk();
        $response->assertSee(route('gudang.materials.show', $material));
    }

    public function test_material_edit_invalid_origin_falls_back_to_index(): void
    {
        $material = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Test', 'min_stock' => 10]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.materials.edit', ['material' => $material, 'from' => 'https://malicious.com']));
        $response->assertOk();
        $response->assertSee(route('gudang.materials.index'));
    }

    public function test_material_show_renders_active_status_and_modal(): void
    {
        $material = Material::create(['unit_id' => $this->meterUnit->id, 'material_name' => 'Mat Active', 'min_stock' => 10, 'active' => true]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.materials.show', $material));
        $response->assertOk();
        $response->assertSee('AKTIF');
        $response->assertSee('Nonaktifkan material?');
        $response->assertSee('Material tidak dapat dipilih untuk transaksi baru');
    }
}
