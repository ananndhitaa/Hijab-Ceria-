<?php

namespace Tests\Feature\Gudang;

use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\Purchase;
use App\Models\Master\Supplier;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
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

    public function test_gudang_can_list_suppliers_and_filter_by_active_status(): void
    {
        $s1 = Supplier::create([
            'supplier_name' => 'PT. Tekstil Nusantara',
            'phone'         => '021-5588-9900',
            'email'         => 'order@tekstil.com',
            'active'        => true,
        ]);

        $s2 = Supplier::create([
            'supplier_name' => 'CV. Nonaktif Supplier',
            'phone'         => '021-1122-3344',
            'email'         => 'sales@nonaktif.com',
            'active'        => false,
        ]);

        // General list
        $response = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.index'));
        $response->assertOk();
        $response->assertSee('PT. Tekstil Nusantara');
        $response->assertSee('CV. Nonaktif Supplier');

        // Filter active = 1
        $responseActive = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.index', ['active' => '1']));
        $responseActive->assertOk();
        $responseActive->assertSee('PT. Tekstil Nusantara');
        $responseActive->assertDontSee('CV. Nonaktif Supplier');

        // Filter active = 0
        $responseInactive = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.index', ['active' => '0']));
        $responseInactive->assertOk();
        $responseInactive->assertSee('CV. Nonaktif Supplier');
        $responseInactive->assertDontSee('PT. Tekstil Nusantara');
    }

    public function test_gudang_can_create_supplier(): void
    {
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.suppliers.store'), [
            'supplier_name' => 'CV. Kain Indah Makmur',
            'phone'         => '024-7755-3311',
            'address'       => 'Jl. Pasar Kain No. 88, Semarang',
            'email'         => 'sales@kainindah.com',
            'active'        => 1,
        ]);

        $supplier = Supplier::where('supplier_name', 'CV. Kain Indah Makmur')->first();
        $this->assertNotNull($supplier);

        $response->assertRedirect(route('gudang.suppliers.show', $supplier));
        $response->assertSessionHas('success', 'Supplier berhasil ditambahkan.');

        $this->assertDatabaseHas('suppliers', [
            'id'            => $supplier->id,
            'supplier_name' => 'CV. Kain Indah Makmur',
            'phone'         => '024-7755-3311',
            'active'        => true,
        ]);
    }

    public function test_supplier_validation_fails_on_missing_name_or_invalid_email(): void
    {
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.suppliers.store'), [
            'supplier_name' => '',
            'email'         => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['supplier_name', 'email']);
    }

    public function test_gudang_can_view_supplier_detail(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'UD. Benang Mas',
            'phone'         => '031-8844-2200',
            'address'       => 'Jl. Gembong No. 45, Surabaya',
        ]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.show', $supplier));
        $response->assertOk();
        $response->assertSee('UD. Benang Mas');
        $response->assertSee('031-8844-2200');
    }

    public function test_gudang_can_update_supplier(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'PT. Kemasan Lama',
            'phone'         => '021-1111',
        ]);

        $response = $this->actingAs($this->gudangUser)->put(route('gudang.suppliers.update', $supplier), [
            'supplier_name' => 'PT. Kemasan Baru',
            'phone'         => '021-2222',
            'active'        => 1,
        ]);

        $response->assertRedirect(route('gudang.suppliers.show', $supplier));
        $response->assertSessionHas('success', 'Perubahan supplier berhasil disimpan.');

        $this->assertDatabaseHas('suppliers', [
            'id'            => $supplier->id,
            'supplier_name' => 'PT. Kemasan Baru',
            'phone'         => '021-2222',
        ]);
    }

    public function test_gudang_can_toggle_supplier_active_status(): void
    {
        $supplier = Supplier::create([
            'supplier_name' => 'PT. Kemasan Modern',
            'active'        => true,
        ]);

        // Toggle to inactive
        $response = $this->actingAs($this->gudangUser)->post(route('gudang.suppliers.toggle-active', $supplier));
        $response->assertSessionHas('success', 'Supplier berhasil dinonaktifkan.');
        $this->assertFalse($supplier->fresh()->active);

        // Toggle back to active
        $response2 = $this->actingAs($this->gudangUser)->post(route('gudang.suppliers.toggle-active', $supplier));
        $response2->assertSessionHas('success', 'Supplier berhasil diaktifkan.');
        $this->assertTrue($supplier->fresh()->active);
    }

    public function test_supplier_deletion_prevented_if_purchases_exist(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Supplier PO']);
        Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => 'PO-2026-001',
            'purchase_date' => now()->toDateString(),
            'status'        => PurchaseStatus::ORDERED->value,
        ]);

        $response = $this->actingAs($this->gudangUser)->delete(route('gudang.suppliers.destroy', $supplier));
        $response->assertRedirect(route('gudang.suppliers.index'));
        $response->assertSessionHas('error', 'Supplier tidak dapat dihapus karena memiliki riwayat pembelian. Anda dapat menonaktifkannya.');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_deletion_allowed_if_no_purchases_exist(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Supplier Unused']);

        $response = $this->actingAs($this->gudangUser)->delete(route('gudang.suppliers.destroy', $supplier));
        $response->assertRedirect(route('gudang.suppliers.index'));
        $response->assertSessionHas('success', 'Supplier berhasil dihapus.');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_historical_purchases_remain_readable_after_supplier_deactivation(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Supplier Historical', 'active' => true]);
        $purchase = Purchase::create([
            'supplier_id'   => $supplier->id,
            'purchase_no'   => 'PO-HIST-001',
            'purchase_date' => now()->toDateString(),
            'status'        => PurchaseStatus::RECEIVED->value,
        ]);

        // Deactivate supplier
        $supplier->update(['active' => false]);

        // Relations remain fully readable
        $this->assertEquals('Supplier Historical', $purchase->fresh(['supplier'])->supplier->supplier_name);
        $this->assertFalse($purchase->fresh(['supplier'])->supplier->active);
    }

    public function test_supplier_edit_origin_index_renders_cancel_to_index(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Supplier Nav Test', 'active' => true]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.edit', ['supplier' => $supplier, 'from' => 'index']));
        $response->assertOk();
        $response->assertSee(route('gudang.suppliers.index'));
    }

    public function test_supplier_edit_origin_show_renders_cancel_to_show(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Supplier Nav Test', 'active' => true]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.edit', ['supplier' => $supplier, 'from' => 'show']));
        $response->assertOk();
        $response->assertSee(route('gudang.suppliers.show', $supplier));
    }

    public function test_supplier_show_renders_active_status_and_modal(): void
    {
        $supplier = Supplier::create(['supplier_name' => 'Supplier Active Modal', 'active' => true]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.show', $supplier));
        $response->assertOk();
        $response->assertSee('AKTIF');
        $response->assertSee('Nonaktifkan supplier?');
        $response->assertSee('Supplier tidak dapat dipilih untuk transaksi baru');
    }
}
