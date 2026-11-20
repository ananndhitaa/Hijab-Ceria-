<?php

namespace Tests\Feature\Gudang;

use App\Enums\MovementType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\StockMovement;
use App\Models\Master\Material;
use App\Models\Master\Unit;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private Material $kainVoal;
    private Unit $meter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser = User::factory()->create([
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meter = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $this->kainVoal = Material::create([
            'unit_id'       => $this->meter->id,
            'material_name' => 'Kain Voal Premium',
            'min_stock'     => 20,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $this->kainVoal->id,
            'qty_on_hand'   => '100.0000',
            'avg_unit_cost' => '40000.0000',
        ]);
    }

    public function test_guest_cannot_access_stock_opname(): void
    {
        $this->get(route('gudang.materials.stock-opname', $this->kainVoal))
            ->assertRedirect(route('login'));

        $this->post(route('gudang.materials.stock-opname.store', $this->kainVoal), [
            'physical_qty' => '110.0000',
            'notes'        => 'Test opname',
        ])->assertRedirect(route('login'));
    }

    public function test_non_gudang_role_is_forbidden_from_stock_opname(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('gudang.materials.stock-opname', $this->kainVoal))
            ->assertForbidden();

        $this->actingAs($this->adminUser)
            ->post(route('gudang.materials.stock-opname.store', $this->kainVoal), [
            'physical_qty' => '110.0000',
            'notes'        => 'Test opname',
        ])->assertForbidden();
    }

    public function test_gudang_user_can_view_stock_opname_form(): void
    {
        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.materials.stock-opname', $this->kainVoal));

        $response->assertOk();
        $response->assertViewIs('gudang.materials.stock-opname');
        $response->assertSee('Kain Voal Premium');
        $response->assertSee('100 m');
        $response->assertSee('Rp 40.000,00');
        $response->assertSee('STOCK OPNAME');
    }

    public function test_gudang_user_can_submit_valid_stock_opname_adjustment(): void
    {
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.materials.stock-opname.store', $this->kainVoal), [
                'physical_qty' => '115.0000',
                'notes'        => 'Hasil audit fisik bulanan 29 Agustus 2026',
            ]);

        $response->assertRedirect(route('gudang.materials.show', $this->kainVoal));
        $response->assertSessionHas('success', 'Stock opname berhasil disimpan. Stok sistem telah disesuaikan.');

        $stock = $this->kainVoal->fresh(['stock'])->stock;
        $this->assertEquals('115.0000', $stock->qty_on_hand);
        $this->assertEquals('40000.0000', $stock->avg_unit_cost);

        $this->assertDatabaseHas('stock_movements', [
            'material_id'         => $this->kainVoal->id,
            'movement_type'       => MovementType::ADJUSTMENT->value,
            'qty'                 => '15.0000',
            'qty_before'          => '100.0000',
            'qty_after'           => '115.0000',
            'unit_cost_snapshot'  => '40000.0000',
            'total_cost_snapshot' => '600000.00',
            'notes'               => 'Hasil audit fisik bulanan 29 Agustus 2026',
            'created_by'          => $this->gudangUser->id,
        ]);
    }

    public function test_no_change_opname_redirects_with_info_message_and_no_movements(): void
    {
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.materials.stock-opname.store', $this->kainVoal), [
                'physical_qty' => '100.0000',
                'notes'        => 'Stok fisik sesuai hitungan riil.',
            ]);

        $response->assertRedirect(route('gudang.materials.show', $this->kainVoal));
        $response->assertSessionHas('info', 'Stok fisik sesuai dengan stok sistem. Tidak ada penyesuaian.');

        $this->assertEquals(0, StockMovement::count());
        $this->assertEquals('100.0000', $this->kainVoal->fresh(['stock'])->stock->qty_on_hand);
    }

    public function test_validation_fails_for_empty_negative_or_invalid_inputs(): void
    {
        // Missing fields
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.materials.stock-opname.store', $this->kainVoal), []);
        $response->assertSessionHasErrors(['physical_qty', 'notes']);

        // Negative quantity
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.materials.stock-opname.store', $this->kainVoal), [
                'physical_qty' => -10,
                'notes'        => 'Notes',
            ]);
        $response->assertSessionHasErrors(['physical_qty']);

        // Whitespace only notes
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.materials.stock-opname.store', $this->kainVoal), [
                'physical_qty' => 120,
                'notes'        => '    ',
            ]);
        $response->assertSessionHasErrors(['notes']);

        // Notes exceeding 500 chars
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.materials.stock-opname.store', $this->kainVoal), [
                'physical_qty' => 120,
                'notes'        => str_repeat('A', 501),
            ]);
        $response->assertSessionHasErrors(['notes']);
    }

    public function test_server_authoritative_fields_ignore_tampered_payload(): void
    {
        $otherUser = User::factory()->create(['role' => UserRole::GUDANG]);

        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang.materials.stock-opname.store', $this->kainVoal), [
                'physical_qty'        => '90.0000',
                'notes'               => 'Penyesuaian stok berkurang 10m',
                'qty_before'          => '500.0000', // tampered
                'movement_type'       => 'IN', // tampered
                'unit_cost_snapshot'  => '999999.0000', // tampered
                'total_cost_snapshot' => '1.00', // tampered
                'created_by'          => $otherUser->id, // tampered
                'movement_date'       => '2020-01-01', // tampered
            ]);

        $response->assertRedirect(route('gudang.materials.show', $this->kainVoal));

        $movement = StockMovement::first();
        $this->assertNotNull($movement);
        $this->assertEquals(MovementType::ADJUSTMENT, $movement->movement_type);
        $this->assertEquals('10.0000', $movement->qty);
        $this->assertEquals('100.0000', $movement->qty_before);
        $this->assertEquals('90.0000', $movement->qty_after);
        $this->assertEquals('40000.0000', $movement->unit_cost_snapshot);
        $this->assertEquals('400000.00', $movement->total_cost_snapshot);
        $this->assertEquals($this->gudangUser->id, $movement->created_by);
        $this->assertEquals(now('Asia/Jakarta')->toDateString(), $movement->movement_date->toDateString());
    }

    public function test_material_show_page_displays_stock_opname_button_and_adjustment_movement(): void
    {
        // Create an adjustment movement
        StockMovement::create([
            'material_id'         => $this->kainVoal->id,
            'movement_type'       => MovementType::ADJUSTMENT,
            'qty'                 => '10.0000',
            'unit_cost_snapshot'  => '40000.0000',
            'total_cost_snapshot' => '400000.00',
            'qty_before'          => '100.0000',
            'qty_after'           => '110.0000',
            'movement_date'       => now('Asia/Jakarta')->toDateString(),
            'notes'               => 'Audit bulanan fisik gudang',
            'created_by'          => $this->gudangUser->id,
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.materials.show', $this->kainVoal));

        $response->assertOk();
        $response->assertSee(route('gudang.materials.stock-opname', $this->kainVoal));
        $response->assertSee('Stock Opname');
        $response->assertSee('Penyesuaian');
        $response->assertSee('+10,00 m');
        $response->assertSee('Audit bulanan fisik gudang');
    }

    public function test_stock_movements_index_page_displays_adjustment_movement(): void
    {
        StockMovement::create([
            'material_id'         => $this->kainVoal->id,
            'movement_type'       => MovementType::ADJUSTMENT,
            'qty'                 => '12.0000',
            'unit_cost_snapshot'  => '40000.0000',
            'total_cost_snapshot' => '480000.00',
            'qty_before'          => '100.0000',
            'qty_after'           => '88.0000',
            'movement_date'       => now('Asia/Jakarta')->toDateString(),
            'notes'               => 'Kain rusak terbuang 12m',
            'created_by'          => $this->gudangUser->id,
        ]);

        $response = $this->actingAs($this->gudangUser)
            ->get(route('gudang.stock-movements.index'));

        $response->assertOk();
        $response->assertSee('Penyesuaian');
        $response->assertSee('-12 m');
        $response->assertSee('Rp 480.000,00');
        $response->assertSee('Kain rusak terbuang 12m');
    }
}
