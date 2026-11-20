<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\BomTemplate;
use App\Models\Master\BomTemplateItem;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\DocumentSequence;
use App\Models\System\Outlet;
use App\Models\System\User;
use Database\Seeders\LegacyMasterDataOutletSeeder;
use Database\Seeders\LegacyOutletSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyMasterDataOutletBackfillTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper membuat outlet legacy standar beserta akun Owner.
     */
    protected function createStandardLegacyOutletAndOwner(int $outletId = 1): array
    {
        $outlet = new Outlet([
            'outlet_name'                => LegacyOutletSeeder::LEGACY_OUTLET_NAME,
            'outlet_code'                => LegacyOutletSeeder::LEGACY_OUTLET_CODE,
            'slug'                       => LegacyOutletSeeder::LEGACY_OUTLET_SLUG,
            'phone_wa'                   => '081234567890',
            'address'                    => 'Kantor Pusat Konveksi NAYARA',
            'status'                     => 'ACTIVE',
            'is_public_showcase_enabled' => true,
        ]);
        $outlet->id = $outletId;
        $outlet->save();

        $owner = User::factory()->create([
            'name'      => 'Owner Konveksi Legacy',
            'email'     => 'owner@nayara.test',
            'role'      => UserRole::OWNER,
            'status'    => UserStatus::ACTIVE,
            'outlet_id' => $outlet->id,
        ]);

        return [$outlet, $owner];
    }

    /**
     * Helper membuat data master legacy unassigned (outlet_id = null).
     */
    protected function createUnassignedLegacyMasterData(): array
    {
        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $product = Product::create([
            'product_name' => 'Pashmina Ceruty Premium',
            'code'         => 'PROD-PAS-CER',
            'description'  => 'Hijab pashmina bahan ceruty babydoll',
            'active'       => true,
            'outlet_id'    => null,
        ]);

        $variant = ProductVariant::create([
            'product_id'                 => $product->id,
            'variant_name'               => 'Hitam',
            'sku'                        => 'PAS-CER-BLK',
            'base_selling_price'         => 45000,
            'requires_printing'          => false,
            'printing_cost_per_unit'     => 0,
            'direct_labor_cost_per_unit' => 5000,
            'active'                     => true,
            'outlet_id'                  => null,
        ]);

        $material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Ceruty Babydoll',
            'min_stock'     => 20.0000,
            'active'        => true,
            'outlet_id'     => null,
        ]);

        $supplier = Supplier::create([
            'supplier_name' => 'PT Tekstil Jaya Abadi',
            'phone'         => '08111222333',
            'address'       => 'Jl. Industri No. 45',
            'active'        => true,
            'outlet_id'     => null,
        ]);

        $supplier->materials()->attach($material->id);

        $customer = Customer::create([
            'customer_name' => 'Toko Hijab Citra',
            'phone'         => '089988776655',
            'address'       => 'Pasar Grosir Blok A',
            'active'        => true,
            'outlet_id'     => null,
        ]);

        $bomTemplate = BomTemplate::create([
            'product_id'  => $product->id,
            'name'        => 'Standard Pashmina Ceruty',
            'description' => 'BOM standard pashmina ceruty',
            'is_active'   => true,
            'outlet_id'   => null,
        ]);

        BomTemplateItem::create([
            'bom_template_id' => $bomTemplate->id,
            'material_id'     => $material->id,
            'qty_per_unit'    => 1.7500,
        ]);

        $sequence = DocumentSequence::create([
            'prefix'        => 'SO',
            'period'        => '202610',
            'last_sequence' => 15,
            'outlet_id'     => null,
        ]);

        return compact('unit', 'product', 'variant', 'material', 'supplier', 'customer', 'bomTemplate', 'sequence');
    }

    /**
     * 1. Seeder menautkan master legacy ke outlet yang benar.
     */
    public function test_01_seeder_links_unassigned_legacy_master_data_to_correct_legacy_outlet(): void
    {
        [$outlet] = $this->createStandardLegacyOutletAndOwner();
        $records = $this->createUnassignedLegacyMasterData();

        (new LegacyMasterDataOutletSeeder())->run();

        $this->assertEquals($outlet->id, $records['product']->fresh()->outlet_id);
        $this->assertEquals($outlet->id, $records['variant']->fresh()->outlet_id);
        $this->assertEquals($outlet->id, $records['material']->fresh()->outlet_id);
        $this->assertEquals($outlet->id, $records['supplier']->fresh()->outlet_id);
        $this->assertEquals($outlet->id, $records['customer']->fresh()->outlet_id);
        $this->assertEquals($outlet->id, $records['bomTemplate']->fresh()->outlet_id);
        $this->assertEquals($outlet->id, $records['sequence']->fresh()->outlet_id);
    }

    /**
     * 2. Seeder tidak mengasumsikan outlet ID = 1.
     */
    public function test_02_seeder_does_not_assume_outlet_id_is_one(): void
    {
        $nonOneId = 99;
        [$outlet] = $this->createStandardLegacyOutletAndOwner($nonOneId);
        $records = $this->createUnassignedLegacyMasterData();

        (new LegacyMasterDataOutletSeeder())->run();

        $this->assertEquals($nonOneId, $records['product']->fresh()->outlet_id);
        $this->assertEquals($nonOneId, $records['variant']->fresh()->outlet_id);
        $this->assertEquals($nonOneId, $records['material']->fresh()->outlet_id);
        $this->assertEquals($nonOneId, $records['supplier']->fresh()->outlet_id);
        $this->assertEquals($nonOneId, $records['customer']->fresh()->outlet_id);
        $this->assertEquals($nonOneId, $records['bomTemplate']->fresh()->outlet_id);
        $this->assertEquals($nonOneId, $records['sequence']->fresh()->outlet_id);
    }

    /**
     * 3. Pengulangan seeder tidak membuat duplikasi dan bersifat idempotent.
     */
    public function test_03_seeder_is_idempotent_and_does_not_duplicate_records(): void
    {
        [$outlet] = $this->createStandardLegacyOutletAndOwner();
        $this->createUnassignedLegacyMasterData();

        $seeder = new LegacyMasterDataOutletSeeder();
        $seeder->run();

        $productCount1 = Product::count();
        $variantCount1 = ProductVariant::count();
        $materialCount1 = Material::count();

        // Eksekusi kedua kali
        $seeder->run();

        $this->assertEquals($productCount1, Product::count());
        $this->assertEquals($variantCount1, ProductVariant::count());
        $this->assertEquals($materialCount1, Material::count());
        $this->assertEquals($outlet->id, Product::first()->outlet_id);
    }

    /**
     * 4. ProductVariant mengikuti outlet dari parent Product.
     */
    public function test_04_product_variant_follows_parent_product_outlet(): void
    {
        [$outlet] = $this->createStandardLegacyOutletAndOwner();
        $records = $this->createUnassignedLegacyMasterData();

        (new LegacyMasterDataOutletSeeder())->run();

        $variant = $records['variant']->fresh();
        $parentProduct = $records['product']->fresh();

        $this->assertEquals($parentProduct->outlet_id, $variant->outlet_id);
        $this->assertEquals($outlet->id, $variant->outlet_id);
    }

    /**
     * 5. Record milik outlet lain tidak berubah.
     */
    public function test_05_records_belonging_to_other_outlets_are_never_modified(): void
    {
        [$legacyOutlet] = $this->createStandardLegacyOutletAndOwner(1);

        $otherOutlet = Outlet::create([
            'id'          => 2,
            'outlet_name' => 'Outlet Cabang Barat',
            'outlet_code' => 'OUT-CABANG-BARAT',
            'slug'        => 'outlet-cabang-barat',
            'status'      => 'ACTIVE',
        ]);

        $unit = Unit::create(['unit_name' => 'Pcs', 'symbol' => 'pcs']);

        // Data milik otherOutlet
        $otherProduct = Product::create([
            'product_name' => 'Produk Cabang Barat',
            'code'         => 'PROD-BRT-01',
            'outlet_id'    => $otherOutlet->id,
        ]);
        $otherMaterial = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Material Cabang Barat',
            'outlet_id'     => $otherOutlet->id,
        ]);

        // Data legacy sudah terpetakan sebelumnya
        $legacyProduct = Product::create([
            'product_name' => 'Produk Pusat Legacy',
            'code'         => 'PROD-PST-01',
            'outlet_id'    => $legacyOutlet->id,
        ]);

        (new LegacyMasterDataOutletSeeder())->run();

        $this->assertEquals($otherOutlet->id, $otherProduct->fresh()->outlet_id);
        $this->assertEquals($otherOutlet->id, $otherMaterial->fresh()->outlet_id);
        $this->assertEquals($legacyOutlet->id, $legacyProduct->fresh()->outlet_id);
    }

    /**
     * 6. Pemetaan ambigu diblokir jika multi-outlet dan ada data unassigned.
     */
    public function test_06_ambiguous_mapping_is_blocked_when_multi_outlet_and_unassigned_records_coexist(): void
    {
        $this->createStandardLegacyOutletAndOwner(1);

        Outlet::create([
            'id'          => 2,
            'outlet_name' => 'Outlet Cabang Timur',
            'outlet_code' => 'OUT-CABANG-TIMUR',
            'slug'        => 'outlet-cabang-timur',
            'status'      => 'ACTIVE',
        ]);

        Product::create([
            'product_name' => 'Produk Tanpa Pemilik di Era Multi-Tenant',
            'code'         => 'PROD-AMBIGU',
            'outlet_id'    => null,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Terdeteksi multi-outlet');

        (new LegacyMasterDataOutletSeeder())->run();
    }

    /**
     * 7. Relasi supplier-material konsisten dan mendeteksi inkonsistensi lintas outlet.
     */
    public function test_07_supplier_material_cross_outlet_inconsistency_is_detected_and_blocked(): void
    {
        [$legacyOutlet] = $this->createStandardLegacyOutletAndOwner(1);

        $otherOutlet = Outlet::create([
            'id'          => 2,
            'outlet_name' => 'Outlet Cabang',
            'outlet_code' => 'OUT-CABANG',
            'slug'        => 'outlet-cabang',
            'status'      => 'ACTIVE',
        ]);

        $unit = Unit::create(['unit_name' => 'Yard', 'symbol' => 'yd']);

        $legacySupplier = Supplier::create([
            'supplier_name' => 'Supplier Pusat',
            'outlet_id'     => $legacyOutlet->id,
        ]);

        $otherMaterial = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Material Cabang Lain',
            'outlet_id'     => $otherOutlet->id,
        ]);

        // Hubungkan supplier legacy ke material milik outlet cabang (inkonsisten)
        $legacySupplier->materials()->attach($otherMaterial->id);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas gagal: Supplier legacy');

        (new LegacyMasterDataOutletSeeder())->run();
    }

    /**
     * 8. Relasi BOM konsisten dan mendeteksi penggunaan material lintas outlet.
     */
    public function test_08_bom_template_cross_outlet_inconsistency_is_detected_and_blocked(): void
    {
        [$legacyOutlet] = $this->createStandardLegacyOutletAndOwner(1);

        $otherOutlet = Outlet::create([
            'id'          => 2,
            'outlet_name' => 'Outlet Cabang',
            'outlet_code' => 'OUT-CABANG',
            'slug'        => 'outlet-cabang',
            'status'      => 'ACTIVE',
        ]);

        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        $product = Product::create([
            'product_name' => 'Produk Pusat',
            'code'         => 'PROD-PST',
            'outlet_id'    => $legacyOutlet->id,
        ]);

        $otherMaterial = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Material Cabang',
            'outlet_id'     => $otherOutlet->id,
        ]);

        $bom = BomTemplate::create([
            'product_id' => $product->id,
            'name'       => 'BOM Invalid Cross Outlet',
            'outlet_id'  => $legacyOutlet->id,
        ]);

        BomTemplateItem::create([
            'bom_template_id' => $bom->id,
            'material_id'     => $otherMaterial->id,
            'qty_per_unit'    => 1.0,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Integritas gagal: BOM Template');

        (new LegacyMasterDataOutletSeeder())->run();
    }

    /**
     * 9. Data customer dan role user aman.
     */
    public function test_09_customer_data_and_user_relations_are_safeguarded(): void
    {
        [$outlet] = $this->createStandardLegacyOutletAndOwner(1);

        $customerUser = User::factory()->create([
            'name'      => 'Akun Login Pelanggan',
            'email'     => 'pelanggan@nayara.test',
            'role'      => UserRole::CUSTOMER,
            'outlet_id' => $outlet->id,
        ]);

        $customer = Customer::create([
            'customer_name' => 'Ibu Rahma',
            'phone'         => '0812345678',
            'user_id'       => $customerUser->id,
            'outlet_id'     => null,
        ]);

        (new LegacyMasterDataOutletSeeder())->run();

        $this->assertEquals($outlet->id, $customer->fresh()->outlet_id);
        $this->assertEquals(UserRole::CUSTOMER, $customerUser->fresh()->role);
        $this->assertEquals('Ibu Rahma', $customer->fresh()->customer_name);
    }

    /**
     * 10. Document sequence tidak berubah kecuali penambahan outlet_id.
     */
    public function test_10_document_sequences_retain_prefix_period_and_counter(): void
    {
        [$outlet] = $this->createStandardLegacyOutletAndOwner(1);

        $seq = DocumentSequence::create([
            'prefix'        => 'INV',
            'period'        => '202610',
            'last_sequence' => 999,
            'outlet_id'     => null,
        ]);

        (new LegacyMasterDataOutletSeeder())->run();

        $freshSeq = $seq->fresh();
        $this->assertEquals($outlet->id, $freshSeq->outlet_id);
        $this->assertEquals('INV', $freshSeq->prefix);
        $this->assertEquals('202610', $freshSeq->period);
        $this->assertEquals(999, $freshSeq->last_sequence);
    }

    /**
     * 11. Jumlah record master tetap sama sebelum dan setelah seeding.
     */
    public function test_11_total_record_counts_remain_strictly_identical(): void
    {
        $this->createStandardLegacyOutletAndOwner(1);
        $this->createUnassignedLegacyMasterData();

        $countsBefore = [
            'products'           => Product::count(),
            'product_variants'   => ProductVariant::count(),
            'materials'          => Material::count(),
            'suppliers'          => Supplier::count(),
            'customers'          => Customer::count(),
            'bom_templates'      => BomTemplate::count(),
            'document_sequences' => DocumentSequence::count(),
        ];

        (new LegacyMasterDataOutletSeeder())->run();

        $countsAfter = [
            'products'           => Product::count(),
            'product_variants'   => ProductVariant::count(),
            'materials'          => Material::count(),
            'suppliers'          => Supplier::count(),
            'customers'          => Customer::count(),
            'bom_templates'      => BomTemplate::count(),
            'document_sequences' => DocumentSequence::count(),
        ];

        $this->assertSame($countsBefore, $countsAfter);
    }

    /**
     * 12. Nilai kolom bisnis tidak berubah.
     */
    public function test_12_business_column_values_are_not_modified(): void
    {
        $this->createStandardLegacyOutletAndOwner(1);
        $records = $this->createUnassignedLegacyMasterData();

        (new LegacyMasterDataOutletSeeder())->run();

        $freshProduct = $records['product']->fresh();
        $freshVariant = $records['variant']->fresh();
        $freshMaterial = $records['material']->fresh();

        $this->assertEquals('Pashmina Ceruty Premium', $freshProduct->product_name);
        $this->assertEquals('PROD-PAS-CER', $freshProduct->code);
        $this->assertEquals('PAS-CER-BLK', $freshVariant->sku);
        $this->assertEquals('45000.00', (string) $freshVariant->base_selling_price);
        $this->assertEquals('20.0000', (string) $freshMaterial->min_stock);
    }

    /**
     * 13. Timestamps historis (created_at dan updated_at) tetap sama persis.
     */
    public function test_13_historical_timestamps_created_at_and_updated_at_are_preserved(): void
    {
        $this->createStandardLegacyOutletAndOwner(1);

        $fixedPastCreatedAt = '2026-01-10 08:30:00';
        $fixedPastUpdatedAt = '2026-02-15 14:45:00';

        $unit = Unit::create(['unit_name' => 'Roll', 'symbol' => 'roll']);

        // Insert record dengan timestamps spesifik masa lalu menggunakan DB table
        $productId = DB::table('products')->insertGetId([
            'product_name' => 'Produk Timestamp Lama',
            'code'         => 'PROD-OLD-TIME',
            'active'       => 1,
            'outlet_id'    => null,
            'created_at'   => $fixedPastCreatedAt,
            'updated_at'   => $fixedPastUpdatedAt,
        ]);

        $materialId = DB::table('materials')->insertGetId([
            'unit_id'       => $unit->id,
            'material_name' => 'Material Timestamp Lama',
            'min_stock'     => 10,
            'active'        => 1,
            'outlet_id'    => null,
            'created_at'   => $fixedPastCreatedAt,
            'updated_at'   => $fixedPastUpdatedAt,
        ]);

        (new LegacyMasterDataOutletSeeder())->run();

        $updatedProduct = DB::table('products')->where('id', $productId)->first();
        $updatedMaterial = DB::table('materials')->where('id', $materialId)->first();

        $this->assertEquals($fixedPastCreatedAt, $updatedProduct->created_at);
        $this->assertEquals($fixedPastUpdatedAt, $updatedProduct->updated_at);
        $this->assertEquals($fixedPastCreatedAt, $updatedMaterial->created_at);
        $this->assertEquals($fixedPastUpdatedAt, $updatedMaterial->updated_at);
    }

    /**
     * 14. Rollback transaksi berhasil ketika terjadi kegagalan integritas atau error sistem.
     */
    public function test_14_database_transaction_rolls_back_completely_on_error(): void
    {
        $this->createStandardLegacyOutletAndOwner(1);
        $records = $this->createUnassignedLegacyMasterData();

        // Uji 1: Rollback atomik saat terjadi error di tengah proses transaksi backfill
        $faultySeeder = new class extends LegacyMasterDataOutletSeeder {
            public function run(): void
            {
                DB::transaction(function () {
                    // Update tabel pertama
                    DB::table('products')->whereNull('outlet_id')->update(['outlet_id' => 1]);

                    // Simulasikan kegagalan tak terduga di tengah eksekusi
                    throw new \RuntimeException('Simulasi kegagalan tak terduga di tengah transaksi.');
                });
            }
        };

        try {
            $faultySeeder->run();
            $this->fail('Seeder seharusnya melempar RuntimeException.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Simulasi kegagalan tak terduga', $e->getMessage());
        }

        // Pastikan tidak ada data yang ter-update sebagian (transaksi di-rollback)
        $this->assertNull($records['product']->fresh()->outlet_id);
        $this->assertNull($records['variant']->fresh()->outlet_id);
        $this->assertNull($records['material']->fresh()->outlet_id);
        $this->assertNull($records['supplier']->fresh()->outlet_id);
        $this->assertNull($records['customer']->fresh()->outlet_id);
        $this->assertNull($records['bomTemplate']->fresh()->outlet_id);
        $this->assertNull($records['sequence']->fresh()->outlet_id);
    }

    /**
     * 15. Database kosong tidak membuat data master palsu.
     */
    public function test_15_empty_database_does_not_create_spurious_master_data(): void
    {
        $this->createStandardLegacyOutletAndOwner(1);

        // Database tidak memiliki master data apa pun
        (new LegacyMasterDataOutletSeeder())->run();

        $this->assertEquals(0, Product::count());
        $this->assertEquals(0, ProductVariant::count());
        $this->assertEquals(0, Material::count());
        $this->assertEquals(0, Supplier::count());
        $this->assertEquals(0, Customer::count());
        $this->assertEquals(0, BomTemplate::count());
        $this->assertEquals(0, DocumentSequence::count());
    }

    /**
     * 16. Foreign key dan relasi Eloquent terhubung dengan sempurna setelah backfill.
     */
    public function test_16_foreign_keys_and_eloquent_relations_resolve_correctly_after_backfill(): void
    {
        [$outlet] = $this->createStandardLegacyOutletAndOwner(1);
        $records = $this->createUnassignedLegacyMasterData();

        (new LegacyMasterDataOutletSeeder())->run();

        $product = $records['product']->fresh();
        $variant = $records['variant']->fresh();
        $material = $records['material']->fresh();
        $supplier = $records['supplier']->fresh();
        $customer = $records['customer']->fresh();
        $bom = $records['bomTemplate']->fresh();
        $seq = $records['sequence']->fresh();

        $this->assertInstanceOf(Outlet::class, $product->outlet);
        $this->assertEquals($outlet->outlet_code, $product->outlet->outlet_code);

        $this->assertInstanceOf(Outlet::class, $variant->outlet);
        $this->assertEquals($outlet->outlet_code, $variant->outlet->outlet_code);

        $this->assertInstanceOf(Outlet::class, $material->outlet);
        $this->assertEquals($outlet->outlet_code, $material->outlet->outlet_code);

        $this->assertInstanceOf(Outlet::class, $supplier->outlet);
        $this->assertEquals($outlet->outlet_code, $supplier->outlet->outlet_code);

        $this->assertInstanceOf(Outlet::class, $customer->outlet);
        $this->assertEquals($outlet->outlet_code, $customer->outlet->outlet_code);

        $this->assertInstanceOf(Outlet::class, $bom->outlet);
        $this->assertEquals($outlet->outlet_code, $bom->outlet->outlet_code);

        $this->assertInstanceOf(Outlet::class, $seq->outlet);
        $this->assertEquals($outlet->outlet_code, $seq->outlet->outlet_code);

        // Reverse relations dari model Outlet
        $freshOutlet = $outlet->fresh();
        $this->assertTrue($freshOutlet->products->contains('id', $product->id));
        $this->assertTrue($freshOutlet->productVariants->contains('id', $variant->id));
        $this->assertTrue($freshOutlet->materials->contains('id', $material->id));
        $this->assertTrue($freshOutlet->suppliers->contains('id', $supplier->id));
        $this->assertTrue($freshOutlet->customers->contains('id', $customer->id));
        $this->assertTrue($freshOutlet->bomTemplates->contains('id', $bom->id));
        $this->assertTrue($freshOutlet->documentSequences->contains('id', $seq->id));
    }
}
