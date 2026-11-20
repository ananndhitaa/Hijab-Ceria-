<?php

namespace Tests\Feature\Gudang;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Material;
use App\Models\Master\Unit;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);
    }

    public function test_gudang_can_list_units(): void
    {
        Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.units.index'));
        $response->assertOk();
        $response->assertSee('Meter');
        $response->assertSee('Pieces');
    }

    public function test_gudang_can_view_create_unit_page(): void
    {
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.units.create'));
        $response->assertOk();
        $response->assertSee('Tambah Satuan Baru');
    }

    public function test_gudang_can_store_valid_unit(): void
    {
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.units.store'), [
            'unit_name' => 'Kilogram',
            'symbol'    => 'kg',
        ]);

        $response->assertRedirect(route('gudang.units.index'));
        $response->assertSessionHas('success', 'Satuan berhasil ditambahkan.');

        $this->assertDatabaseHas('units', [
            'unit_name' => 'Kilogram',
            'symbol'    => 'kg',
        ]);
    }

    public function test_unit_creation_validation_requires_fields(): void
    {
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.units.store'), []);
        $response->assertSessionHasErrors(['unit_name', 'symbol']);
    }

    public function test_unit_creation_prevents_duplicate_symbol(): void
    {
        Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $response = $this->actingAs($this->gudangUser)->post(route('gudang.units.store'), [
            'unit_name' => 'Meter Lain',
            'symbol'    => 'm',
        ]);

        $response->assertSessionHasErrors(['symbol']);
    }

    public function test_gudang_can_edit_and_update_unit(): void
    {
        $unit = Unit::create(['unit_name' => 'Lusin', 'symbol' => 'lsn']);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.units.edit', $unit));
        $response->assertOk();
        $response->assertSee('Edit Satuan');

        $updateResponse = $this->actingAs($this->gudangUser)->put(route('gudang.units.update', $unit), [
            'unit_name' => 'Lusin Baru',
            'symbol'    => 'lsn',
        ]);

        $updateResponse->assertRedirect(route('gudang.units.index'));
        $updateResponse->assertSessionHas('success', 'Perubahan satuan berhasil disimpan.');

        $this->assertDatabaseHas('units', [
            'id'        => $unit->id,
            'unit_name' => 'Lusin Baru',
            'symbol'    => 'lsn',
        ]);
    }

    public function test_unit_deletion_blocked_if_material_exists(): void
    {
        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Voal',
            'min_stock'     => 10,
        ]);

        $response = $this->actingAs($this->gudangUser)->delete(route('gudang.units.destroy', $unit));
        $response->assertRedirect(route('gudang.units.index'));
        $response->assertSessionHas('error', 'Satuan tidak dapat dihapus karena sedang digunakan oleh data material.');

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    public function test_unit_deletion_succeeds_when_unused(): void
    {
        $unit = Unit::create(['unit_name' => 'Unused Unit', 'symbol' => 'uu']);

        $response = $this->actingAs($this->gudangUser)->delete(route('gudang.units.destroy', $unit));
        $response->assertRedirect(route('gudang.units.index'));
        $response->assertSessionHas('success', 'Satuan berhasil dihapus.');

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }
}
