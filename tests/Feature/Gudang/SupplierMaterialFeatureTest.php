<?php

namespace Tests\Feature\Gudang;

use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Inventory\MaterialStock;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseItem;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\User;
use App\Services\Master\SupplierMaterialBackfillService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupplierMaterialFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $adminUser;
    private User $produksiUser;
    private User $customerUser;
    private Unit $meterUnit;
    private Unit $pcsUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create([
            'name'   => 'Staf Gudang',
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->adminUser = User::factory()->create([
            'name'   => 'Admin',
            'role'   => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->produksiUser = User::factory()->create([
            'name'   => 'Produksi',
            'role'   => UserRole::PRODUKSI,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->customerUser = User::factory()->create([
            'name'   => 'Customer',
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->meterUnit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);
        $this->pcsUnit = Unit::create(['unit_name' => 'Pieces', 'symbol' => 'pcs']);
    }

    private function createMaterial(string $name, Unit $unit): Material
    {
        $material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => $name,
            'min_stock'     => 10,
            'active'        => true,
        ]);

        MaterialStock::create([
            'material_id'   => $material->id,
            'qty_on_hand'   => 50,
            'avg_unit_cost' => 20000,
        ]);

        return $material;
    }

    private function createSupplier(string $name): Supplier
    {
        return Supplier::create([
            'supplier_name' => $name,
            'phone'         => '0812345678',
            'active'        => true,
        ]);
    }

    /**
     * 1. Supplier can have multiple materials.
     */
    public function test_supplier_can_have_multiple_materials(): void
    {
        $supplier = $this->createSupplier('PT Sumber Kain');
        $mat1 = $this->createMaterial('Kain Kaos', $this->meterUnit);
        $mat2 = $this->createMaterial('Kain Voal Premium', $this->meterUnit);

        $supplier->materials()->attach([$mat1->id, $mat2->id]);

        $this->assertCount(2, $supplier->fresh()->materials);
        $this->assertTrue($supplier->fresh()->materials->contains($mat1));
        $this->assertTrue($supplier->fresh()->materials->contains($mat2));
    }

    /**
     * 2. Material can have multiple suppliers.
     */
    public function test_material_can_have_multiple_suppliers(): void
    {
        $mat = $this->createMaterial('Kain Ceruty Premium', $this->meterUnit);
        $sup1 = $this->createSupplier('PT Sumber Kain');
        $sup2 = $this->createSupplier('PT Barang Hijab');

        $mat->suppliers()->attach([$sup1->id, $sup2->id]);

        $this->assertCount(2, $mat->fresh()->suppliers);
        $this->assertTrue($mat->fresh()->suppliers->contains($sup1));
        $this->assertTrue($mat->fresh()->suppliers->contains($sup2));
    }

    /**
     * 3. Duplicate pivot relation prevented by database unique constraint.
     */
    public function test_duplicate_pivot_relation_prevented(): void
    {
        $supplier = $this->createSupplier('PT Benang');
        $material = $this->createMaterial('Benang Jahit', $this->meterUnit);

        DB::table('supplier_materials')->insert([
            'supplier_id' => $supplier->id,
            'material_id' => $material->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('supplier_materials')->insert([
            'supplier_id' => $supplier->id,
            'material_id' => $material->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * 4. Backfill from real purchase history works.
     * 5. Backfill is idempotent.
     * 6. Backfill does not modify Purchase.
     * 7. Backfill does not modify PurchaseItem.
     * 8. Backfill does not modify stock.
     */
    public function test_backfill_is_idempotent_and_preserves_purchase_and_stock(): void
    {
        $sup1 = $this->createSupplier('PT Sumber Kain');
        $sup2 = $this->createSupplier('PT Benang');

        $mat1 = $this->createMaterial('Kain Kaos', $this->meterUnit);
        $mat2 = $this->createMaterial('Benang Jahit', $this->meterUnit);

        $stock1Before = MaterialStock::where('material_id', $mat1->id)->first()->qty_on_hand;
        $stock2Before = MaterialStock::where('material_id', $mat2->id)->first()->qty_on_hand;

        $po1 = Purchase::create([
            'purchase_no'   => 'PO-202610-0001',
            'supplier_id'   => $sup1->id,
            'purchase_date' => '2026-10-01',
            'status'        => PurchaseStatus::RECEIVED,
            'total_amount'  => 100000,
        ]);
        $pi1 = PurchaseItem::create([
            'purchase_id'  => $po1->id,
            'material_id'  => $mat1->id,
            'qty'          => 10,
            'unit_price'   => 10000,
            'qty_received' => 10,
        ]);

        $po2 = Purchase::create([
            'purchase_no'   => 'PO-202610-0002',
            'supplier_id'   => $sup2->id,
            'purchase_date' => '2026-10-02',
            'status'        => PurchaseStatus::RECEIVED,
            'total_amount'  => 50000,
        ]);
        $pi2 = PurchaseItem::create([
            'purchase_id'  => $po2->id,
            'material_id'  => $mat2->id,
            'qty'          => 5,
            'unit_price'   => 10000,
            'qty_received' => 5,
        ]);

        $service = app(SupplierMaterialBackfillService::class);

        // Run 1st time
        $result1 = $service->backfill();
        $this->assertEquals(2, $result1['inserted']);
        $this->assertEquals(2, $result1['total_relations']);

        // Run 2nd time (idempotent)
        $result2 = $service->backfill();
        $this->assertEquals(0, $result2['inserted']);
        $this->assertEquals(2, $result2['total_relations']);

        // Check purchases and items unchanged
        $this->assertEquals('PO-202610-0001', $po1->fresh()->purchase_no);
        $this->assertEquals('PO-202610-0002', $po2->fresh()->purchase_no);
        $this->assertEquals('10.0000', $pi1->fresh()->qty);
        $this->assertEquals('5.0000', $pi2->fresh()->qty);

        // Check stock unchanged
        $this->assertEquals($stock1Before, MaterialStock::where('material_id', $mat1->id)->first()->qty_on_hand);
        $this->assertEquals($stock2Before, MaterialStock::where('material_id', $mat2->id)->first()->qty_on_hand);
    }

    /**
     * Master Supplier-Material authoritative correction and backfill safety.
     * 1. Initial bootstrap produces relations from historical POs.
     * 2. Authoritative correction removes Kain Ceruty Premium from PT Barang Hijab.
     * 3. PT Sumber Kain retains Kain Ceruty Premium.
     * 4. PT Barang Hijab final material count = 4.
     * 5. Total authoritative relations = 13.
     * 6. Rerunning backfill does NOT re-add Ceruty to PT Barang Hijab.
     * 7. Search "Ceruty" returns PT Sumber Kain and does NOT return PT Barang Hijab.
     * 8. Historical PO & PurchaseItems remain unchanged.
     * 9. Material stock remains unchanged.
     */
    public function test_master_mapping_correction_removes_ceruty_from_pt_barang_hijab_and_preserves_13_authoritative_relations(): void
    {
        $ptSumberKain = $this->createSupplier('PT Sumber Kain');
        $ptBenang = $this->createSupplier('PT Benang');
        $ptBarangHijab = $this->createSupplier('PT Barang Hijab');

        $kainKaos = $this->createMaterial('Kain Kaos', $this->meterUnit);
        $kainVoal = $this->createMaterial('Kain Voal Premium', $this->meterUnit);
        $kainVoalPrint = $this->createMaterial('Kain Voal Printing', $this->meterUnit);
        $kainCeruty = $this->createMaterial('Kain Ceruty Premium', $this->meterUnit);
        $kainJersey = $this->createMaterial('Kain Jersey Premium', $this->meterUnit);
        $kainViscose = $this->createMaterial('Kain Viscose Printing', $this->meterUnit);
        $kainRayon = $this->createMaterial('Kain Kaos Rayon Spandex', $this->meterUnit);
        $kainBawal = $this->createMaterial('Kain Bawal Cotton Premium', $this->meterUnit);

        $benangJahit = $this->createMaterial('Benang Jahit', $this->meterUnit);

        $hangtag = $this->createMaterial('Hangtag', $this->pcsUnit);
        $labelBrand = $this->createMaterial('Label Brand Hijab', $this->pcsUnit);
        $polybag = $this->createMaterial('Polybag 25x35', $this->pcsUnit);
        $stikerBarcode = $this->createMaterial('Stiker Barcode', $this->pcsUnit);

        // Record stock before
        $stockCerutyBefore = MaterialStock::where('material_id', $kainCeruty->id)->value('qty_on_hand');

        // Create POs mimicking the audited dataset
        $po1 = Purchase::create(['purchase_no' => 'PO-01', 'supplier_id' => $ptSumberKain->id, 'purchase_date' => '2026-08-01', 'status' => PurchaseStatus::RECEIVED]);
        foreach ([$kainKaos, $kainVoal, $kainVoalPrint, $kainCeruty, $kainJersey, $kainViscose, $kainRayon, $kainBawal] as $mat) {
            PurchaseItem::create(['purchase_id' => $po1->id, 'material_id' => $mat->id, 'qty' => 10, 'unit_price' => 1000]);
        }

        $po2 = Purchase::create(['purchase_no' => 'PO-02', 'supplier_id' => $ptBenang->id, 'purchase_date' => '2026-08-02', 'status' => PurchaseStatus::RECEIVED]);
        PurchaseItem::create(['purchase_id' => $po2->id, 'material_id' => $benangJahit->id, 'qty' => 10, 'unit_price' => 1000]);

        $po3 = Purchase::create(['purchase_no' => 'PO-03', 'supplier_id' => $ptBarangHijab->id, 'purchase_date' => '2026-08-03', 'status' => PurchaseStatus::RECEIVED]);
        $po3Items = [];
        foreach ([$hangtag, $labelBrand, $polybag, $stikerBarcode, $kainCeruty] as $mat) {
            $po3Items[] = PurchaseItem::create(['purchase_id' => $po3->id, 'material_id' => $mat->id, 'qty' => 10, 'unit_price' => 1000]);
        }

        $service = app(SupplierMaterialBackfillService::class);
        $result = $service->backfill();

        // 1. Initial bootstrap produces 14 historical pairs
        $this->assertEquals(14, $result['total_relations']);

        // 2. Perform safe authoritative master correction: Remove ONLY PT Barang Hijab ↔ Kain Ceruty Premium
        $deletedCount = DB::table('supplier_materials')
            ->where('supplier_id', $ptBarangHijab->id)
            ->where('material_id', $kainCeruty->id)
            ->delete();
        $this->assertEquals(1, $deletedCount);

        // 3. Expected total relations after correction: 13
        $this->assertEquals(13, DB::table('supplier_materials')->count());

        // 4. PT Barang Hijab does NOT have Kain Ceruty Premium in current master mapping
        $ptBarangHijabMaterials = $ptBarangHijab->fresh()->materials;
        $this->assertFalse($ptBarangHijabMaterials->contains($kainCeruty));

        // 5. PT Sumber Kain DOES have Kain Ceruty Premium
        $ptSumberKainMaterials = $ptSumberKain->fresh()->materials;
        $this->assertTrue($ptSumberKainMaterials->contains($kainCeruty));

        // 6. PT Barang Hijab current material count = 4
        $this->assertCount(4, $ptBarangHijabMaterials);
        $this->assertTrue($ptBarangHijabMaterials->contains($hangtag));
        $this->assertTrue($ptBarangHijabMaterials->contains($labelBrand));
        $this->assertTrue($ptBarangHijabMaterials->contains($polybag));
        $this->assertTrue($ptBarangHijabMaterials->contains($stikerBarcode));

        // 7. PT Sumber Kain retains 8 materials, PT Benang retains 1
        $this->assertCount(8, $ptSumberKainMaterials);
        $this->assertCount(1, $ptBenang->fresh()->materials);

        // 8. Kain Ceruty Premium now only maps to PT Sumber Kain in master
        $cerutySuppliers = $kainCeruty->fresh()->suppliers;
        $this->assertCount(1, $cerutySuppliers);
        $this->assertTrue($cerutySuppliers->contains($ptSumberKain));
        $this->assertFalse($cerutySuppliers->contains($ptBarangHijab));

        // 9. Backfill rerun does NOT re-add PT Barang Hijab ↔ Kain Ceruty Premium
        $rerunResult = $service->backfill();
        $this->assertEquals(0, $rerunResult['inserted']);
        $this->assertEquals(13, DB::table('supplier_materials')->count());
        $this->assertFalse($ptBarangHijab->fresh()->materials->contains($kainCeruty));

        // 10. Search supplier by "Ceruty" returns PT Sumber Kain and NOT PT Barang Hijab
        $searchResponse = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.index', ['search' => 'Ceruty']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('PT Sumber Kain');
        $searchResponse->assertDontSee('PT Barang Hijab');

        // 11. Historical Purchase and PurchaseItem remain completely unchanged
        $this->assertDatabaseHas('purchases', ['id' => $po3->id, 'supplier_id' => $ptBarangHijab->id]);
        $this->assertCount(5, PurchaseItem::where('purchase_id', $po3->id)->get());
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $po3->id,
            'material_id' => $kainCeruty->id,
        ]);

        // 12. Stock remains unchanged
        $this->assertEquals($stockCerutyBefore, MaterialStock::where('material_id', $kainCeruty->id)->value('qty_on_hand'));
    }

    /**
     * 13, 14. Supplier list shows material preview and limits preview to max 2 + remaining count (+N lainnya).
     */
    public function test_supplier_list_shows_compact_material_preview_and_plus_n_badge(): void
    {
        $supplier = $this->createSupplier('PT Sumber Kain');
        $mat1 = $this->createMaterial('Kain Kaos', $this->meterUnit);
        $mat2 = $this->createMaterial('Kain Voal Premium', $this->meterUnit);
        $mat3 = $this->createMaterial('Kain Ceruty Premium', $this->meterUnit);

        $supplier->materials()->attach([$mat1->id, $mat2->id, $mat3->id]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.index'));
        $response->assertStatus(200);
        $response->assertSee('PT Sumber Kain');
        $response->assertSee('Kain Ceruty Premium');
        $response->assertSee('Kain Kaos');
        $response->assertSee('+1 lainnya');
    }

    /**
     * 15. Supplier detail shows complete material list and units.
     */
    public function test_supplier_detail_shows_complete_material_list_and_units(): void
    {
        $supplier = $this->createSupplier('PT Benang');
        $mat = $this->createMaterial('Benang Jahit', $this->meterUnit);
        $supplier->materials()->attach([$mat->id]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.show', $supplier));
        $response->assertStatus(200);
        $response->assertSee('Material Disuplai');
        $response->assertSee('Benang Jahit');
        $response->assertSee('Meter');
    }

    /**
     * 16. Create Supplier can assign multiple materials.
     */
    public function test_create_supplier_assigns_multiple_materials(): void
    {
        $mat1 = $this->createMaterial('Kain Voal Premium', $this->meterUnit);
        $mat2 = $this->createMaterial('Benang Jahit', $this->meterUnit);

        $payload = [
            'supplier_name' => 'PT Tekstil Baru',
            'phone'         => '0812999900',
            'address'       => 'Jl. Baru No. 1',
            'active'        => 1,
            'material_ids'  => [$mat1->id, $mat2->id],
        ];

        $response = $this->actingAs($this->gudangUser)->post(route('gudang.suppliers.store'), $payload);

        $supplier = Supplier::where('supplier_name', 'PT Tekstil Baru')->first();
        $this->assertNotNull($supplier);
        $response->assertRedirect(route('gudang.suppliers.show', $supplier));
        $this->assertCount(2, $supplier->materials);
        $this->assertTrue($supplier->materials->contains($mat1));
        $this->assertTrue($supplier->materials->contains($mat2));
    }

    /**
     * 17, 18, 19, 20, 21. Edit Supplier preselects existing materials, can add, can remove safely without deleting material or purchase history.
     */
    public function test_edit_supplier_can_add_and_remove_materials_safely(): void
    {
        $supplier = $this->createSupplier('PT Mitra Tekstil');
        $mat1 = $this->createMaterial('Kain Rayon', $this->meterUnit);
        $mat2 = $this->createMaterial('Kain Jersey', $this->meterUnit);
        $mat3 = $this->createMaterial('Kain Katun', $this->meterUnit);

        $supplier->materials()->attach([$mat1->id, $mat2->id]);

        // Historical purchase
        $po = Purchase::create([
            'purchase_no'   => 'PO-202610-HIST',
            'supplier_id'   => $supplier->id,
            'purchase_date' => '2026-10-01',
            'status'        => PurchaseStatus::RECEIVED,
        ]);
        $pi = PurchaseItem::create([
            'purchase_id'  => $po->id,
            'material_id'  => $mat1->id,
            'qty'          => 15,
            'unit_price'   => 20000,
            'qty_received' => 15,
        ]);

        // Visit edit page (preselected)
        $editPage = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.edit', $supplier));
        $editPage->assertStatus(200);
        $editPage->assertSee('Kain Rayon');
        $editPage->assertSee('Kain Jersey');

        // Update: remove mat1, keep mat2, add mat3
        $updatePayload = [
            'supplier_name' => 'PT Mitra Tekstil Update',
            'phone'         => '0812999900',
            'active'        => 1,
            'material_ids'  => [$mat2->id, $mat3->id],
        ];

        $updateResponse = $this->actingAs($this->gudangUser)->put(route('gudang.suppliers.update', $supplier), $updatePayload);
        $updateResponse->assertRedirect(route('gudang.suppliers.show', $supplier));

        $supplier = $supplier->fresh();
        $this->assertCount(2, $supplier->materials);
        $this->assertFalse($supplier->materials->contains($mat1));
        $this->assertTrue($supplier->materials->contains($mat2));
        $this->assertTrue($supplier->materials->contains($mat3));

        // CRITICAL: mat1 is NOT deleted from materials table!
        $this->assertDatabaseHas('materials', ['id' => $mat1->id]);

        // CRITICAL: Historical purchase and purchase item are NOT deleted!
        $this->assertDatabaseHas('purchases', ['id' => $po->id]);
        $this->assertDatabaseHas('purchase_items', ['id' => $pi->id]);
    }

    /**
     * 22, 23, 24. PO create/edit validation rejects materials not assigned to supplier.
     */
    public function test_backend_validation_rejects_material_not_assigned_to_supplier(): void
    {
        $supplier = $this->createSupplier('PT Benang');
        $benang = $this->createMaterial('Benang Jahit', $this->meterUnit);
        $kainVoal = $this->createMaterial('Kain Voal Premium', $this->meterUnit);

        // Only benang is assigned
        $supplier->materials()->attach([$benang->id]);

        // Attempt to create PO with Kain Voal
        $payload = [
            'supplier_id'   => $supplier->id,
            'purchase_date' => '2026-10-10',
            'items'         => [
                [
                    'material_id' => $kainVoal->id,
                    'qty'         => 10,
                    'unit_price'  => 30000,
                ],
            ],
        ];

        $response = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.store'), $payload);
        $response->assertSessionHasErrors('items.0.material_id');

        $errorMessage = session('errors')->first('items.0.material_id');
        $this->assertStringContainsString('Material Kain Voal Premium tidak terdaftar pada supplier PT Benang.', $errorMessage);

        // Valid submission with Benang Jahit succeeds
        $validPayload = [
            'supplier_id'   => $supplier->id,
            'purchase_date' => '2026-10-10',
            'items'         => [
                [
                    'material_id' => $benang->id,
                    'qty'         => 10,
                    'unit_price'  => 15000,
                ],
            ],
        ];

        $validResponse = $this->actingAs($this->gudangUser)->post(route('gudang.purchases.store'), $validPayload);
        $validResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('purchases', ['supplier_id' => $supplier->id]);
    }

    /**
     * 26. Supplier search by material works.
     */
    public function test_supplier_search_by_material_name_finds_matching_suppliers(): void
    {
        $sup1 = $this->createSupplier('PT Sumber Kain');
        $sup2 = $this->createSupplier('PT Barang Hijab');
        $sup3 = $this->createSupplier('PT Benang');

        $ceruty = $this->createMaterial('Kain Ceruty Premium', $this->meterUnit);
        $benang = $this->createMaterial('Benang Jahit', $this->meterUnit);
        $polybag = $this->createMaterial('Polybag 25x35', $this->pcsUnit);

        $sup1->materials()->attach([$ceruty->id]);
        $sup2->materials()->attach([$polybag->id]);
        $sup3->materials()->attach([$benang->id]);

        $searchResponse = $this->actingAs($this->gudangUser)->get(route('gudang.suppliers.index', ['search' => 'Ceruty']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('PT Sumber Kain');
        $searchResponse->assertDontSee('PT Barang Hijab');
        $searchResponse->assertDontSee('PT Benang');
    }

    /**
     * 27, 28. Role authorization: Gudang allowed, Produksi/Customer forbidden.
     */
    public function test_role_authorization_for_supplier_material_mapping(): void
    {
        $supplier = $this->createSupplier('PT Toko');
        $mat = $this->createMaterial('Kain', $this->meterUnit);

        // Gudang can access create form
        $this->actingAs($this->gudangUser)
            ->get(route('gudang.suppliers.create'))
            ->assertStatus(200);

        // Produksi forbidden
        $this->actingAs($this->produksiUser)
            ->get(route('gudang.suppliers.create'))
            ->assertForbidden();

        // Customer forbidden
        $this->actingAs($this->customerUser)
            ->get(route('gudang.suppliers.create'))
            ->assertForbidden();

        // Produksi forbidden from storing supplier
        $this->actingAs($this->produksiUser)
            ->post(route('gudang.suppliers.store'), [
                'supplier_name' => 'Supplier Terlarang',
                'material_ids'  => [$mat->id],
            ])
            ->assertForbidden();
    }

    /**
     * Material detail shows registered suppliers.
     */
    public function test_material_detail_shows_registered_suppliers(): void
    {
        $mat = $this->createMaterial('Kain Ceruty Premium', $this->meterUnit);
        $sup1 = $this->createSupplier('PT Sumber Kain');

        $mat->suppliers()->attach([$sup1->id]);

        $response = $this->actingAs($this->gudangUser)->get(route('gudang.materials.show', $mat));
        $response->assertStatus(200);
        $response->assertSee('Supplier Terdaftar');
        $response->assertSee('PT Sumber Kain');
        $response->assertDontSee('PT Barang Hijab');
    }

    /**
     * Update PO also rejects materials not assigned to supplier.
     */
    public function test_update_purchase_order_rejects_unassigned_material(): void
    {
        $sup = $this->createSupplier('PT Sumber Kain');
        $mat1 = $this->createMaterial('Kain Kaos', $this->meterUnit);
        $mat2 = $this->createMaterial('Benang Jahit', $this->meterUnit);

        $sup->materials()->attach([$mat1->id]);

        $po = Purchase::create([
            'purchase_no'   => 'PO-202610-DRAFT',
            'supplier_id'   => $sup->id,
            'purchase_date' => '2026-10-10',
            'status'        => PurchaseStatus::DRAFT,
            'total_amount'  => 10000,
        ]);
        PurchaseItem::create([
            'purchase_id'  => $po->id,
            'material_id'  => $mat1->id,
            'qty'          => 1,
            'unit_price'   => 10000,
            'qty_received' => 0,
        ]);

        $updateResponse = $this->actingAs($this->gudangUser)->put(route('gudang.purchases.update', $po), [
            'supplier_id'   => $sup->id,
            'purchase_date' => '2026-10-11',
            'items'         => [
                [
                    'material_id' => $mat2->id, // Not supplied by PT Sumber Kain
                    'qty'         => 5,
                    'unit_price'  => 2000,
                ],
            ],
        ]);

        $updateResponse->assertSessionHasErrors('items.0.material_id');
        $errorMessage = session('errors')->first('items.0.material_id');
        $this->assertStringContainsString('Material Benang Jahit tidak terdaftar pada supplier PT Sumber Kain.', $errorMessage);
    }
}
