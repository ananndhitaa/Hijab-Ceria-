<?php

namespace Tests\Feature\System;

use App\Models\Master\BomTemplate;
use App\Models\Master\Customer;
use App\Models\Master\Material;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Models\System\DocumentSequence;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MasterDataOutletOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected array $targetTables = [
        'products',
        'product_variants',
        'materials',
        'suppliers',
        'customers',
        'bom_templates',
        'document_sequences',
    ];

    /**
     * 1. Semua tabel target master data memiliki kolom outlet_id setelah migration.
     */
    public function test_01_all_target_master_data_tables_have_outlet_id_column(): void
    {
        foreach ($this->targetTables as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'outlet_id'),
                "Tabel '{$table}' tidak memiliki kolom 'outlet_id'."
            );
        }
    }

    /**
     * 2 & 3. Kolom outlet_id nullable pada fase transisi dan record tanpa outlet_id dapat dibuat.
     */
    public function test_02_outlet_id_is_nullable_and_allows_unassigned_master_data(): void
    {
        $unit = Unit::create(['unit_name' => 'Meter', 'symbol' => 'm']);

        // 1. Product tanpa outlet_id
        $product = Product::create([
            'product_name' => 'Pashmina Ceruty',
            'outlet_id'   => null,
        ]);
        $this->assertNull($product->fresh()->outlet_id);

        // 2. ProductVariant tanpa outlet_id
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Hitam',
            'sku'          => 'PAS-CER-BLK',
            'outlet_id'    => null,
        ]);
        $this->assertNull($variant->fresh()->outlet_id);

        // 3. Material tanpa outlet_id
        $material = Material::create([
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Ceruty Baby Doll',
            'outlet_id'     => null,
        ]);
        $this->assertNull($material->fresh()->outlet_id);

        // 4. Supplier tanpa outlet_id
        $supplier = Supplier::create([
            'supplier_name' => 'CV Tekstil Nusantara',
            'outlet_id'     => null,
        ]);
        $this->assertNull($supplier->fresh()->outlet_id);

        // 5. Customer tanpa outlet_id
        $customer = Customer::create([
            'customer_name' => 'Boutique Zahra',
            'outlet_id'     => null,
        ]);
        $this->assertNull($customer->fresh()->outlet_id);

        // 6. BomTemplate tanpa outlet_id
        $bomTemplate = BomTemplate::create([
            'product_id' => $product->id,
            'name'       => 'Standard Pashmina BOM',
            'outlet_id'  => null,
        ]);
        $this->assertNull($bomTemplate->fresh()->outlet_id);

        // 7. DocumentSequence tanpa outlet_id
        $sequence = DocumentSequence::create([
            'prefix'        => 'INV',
            'period'        => '202610',
            'last_sequence' => 10,
            'outlet_id'     => null,
        ]);
        $this->assertNull($sequence->fresh()->outlet_id);
    }

    /**
     * 4. Model Product terhubung ke Outlet (belongsTo & hasMany).
     */
    public function test_04_product_model_connects_to_outlet(): void
    {
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-PRD-01']);
        $product = Product::create([
            'outlet_id'    => $outlet->id,
            'product_name' => 'Bergo Maryam Daily',
        ]);

        $this->assertInstanceOf(Outlet::class, $product->outlet);
        $this->assertSame($outlet->id, $product->outlet->id);
        $this->assertTrue($outlet->products->contains($product));
    }

    /**
     * 5. Model Material terhubung ke Outlet.
     */
    public function test_05_material_model_connects_to_outlet(): void
    {
        $unit = Unit::create(['unit_name' => 'Kilogram', 'symbol' => 'kg']);
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-MAT-01']);
        $material = Material::create([
            'outlet_id'     => $outlet->id,
            'unit_id'       => $unit->id,
            'material_name' => 'Benang Jahit Hitam',
        ]);

        $this->assertInstanceOf(Outlet::class, $material->outlet);
        $this->assertSame($outlet->id, $material->outlet->id);
        $this->assertTrue($outlet->materials->contains($material));
    }

    /**
     * 6. Model Supplier terhubung ke Outlet.
     */
    public function test_06_supplier_model_connects_to_outlet(): void
    {
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-SUP-01']);
        $supplier = Supplier::create([
            'outlet_id'     => $outlet->id,
            'supplier_name' => 'PT Mitra Benang Abadi',
        ]);

        $this->assertInstanceOf(Outlet::class, $supplier->outlet);
        $this->assertSame($outlet->id, $supplier->outlet->id);
        $this->assertTrue($outlet->suppliers->contains($supplier));
    }

    /**
     * 7. Model Customer terhubung ke Outlet.
     */
    public function test_07_customer_model_connects_to_outlet(): void
    {
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-CUST-01']);
        $customer = Customer::create([
            'outlet_id'     => $outlet->id,
            'customer_name' => 'Toko Hijab Syari',
        ]);

        $this->assertInstanceOf(Outlet::class, $customer->outlet);
        $this->assertSame($outlet->id, $customer->outlet->id);
        $this->assertTrue($outlet->customers->contains($customer));
    }

    /**
     * 8. Model ProductVariant terhubung ke Outlet.
     */
    public function test_08_product_variant_model_connects_to_outlet(): void
    {
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-VAR-01']);
        $product = Product::create([
            'outlet_id'    => $outlet->id,
            'product_name' => 'Gamis Crinkle Airflow',
        ]);

        $variant = ProductVariant::create([
            'outlet_id'    => $outlet->id,
            'product_id'   => $product->id,
            'variant_name' => 'Moccachino',
            'sku'          => 'GMS-CRK-MCC',
        ]);

        $this->assertInstanceOf(Outlet::class, $variant->outlet);
        $this->assertSame($outlet->id, $variant->outlet->id);
        $this->assertTrue($outlet->productVariants->contains($variant));
    }

    /**
     * 9. Model BomTemplate terhubung ke Outlet.
     */
    public function test_09_bom_template_model_connects_to_outlet(): void
    {
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-BOM-01']);
        $product = Product::create([
            'outlet_id'    => $outlet->id,
            'product_name' => 'Mukena Silk Premium',
        ]);

        $template = BomTemplate::create([
            'outlet_id'   => $outlet->id,
            'product_id'  => $product->id,
            'name'        => 'Master BOM Mukena Silk',
            'description' => 'Komposisi silk dan renda',
        ]);

        $this->assertInstanceOf(Outlet::class, $template->outlet);
        $this->assertSame($outlet->id, $template->outlet->id);
        $this->assertTrue($outlet->bomTemplates->contains($template));
    }

    /**
     * 10. Model DocumentSequence terhubung ke Outlet.
     */
    public function test_10_document_sequence_model_connects_to_outlet(): void
    {
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-SEQ-01']);
        $sequence = DocumentSequence::create([
            'outlet_id'     => $outlet->id,
            'prefix'        => 'SO',
            'period'        => '202610',
            'last_sequence' => 1,
        ]);

        $this->assertInstanceOf(Outlet::class, $sequence->outlet);
        $this->assertSame($outlet->id, $sequence->outlet->id);
        $this->assertTrue($outlet->documentSequences->contains($sequence));
    }

    /**
     * 11. Foreign key restrictOnDelete menolak penghapusan Outlet yang memiliki master data terhubung.
     */
    public function test_11_foreign_key_restricts_deletion_of_outlet_with_linked_master_data(): void
    {
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-RESTRICT-01']);

        // Kaitkan master data (Product)
        Product::create([
            'outlet_id'    => $outlet->id,
            'product_name' => 'Pashmina Tali Instan',
        ]);

        $this->expectException(QueryException::class);
        $outlet->delete();
    }

    /**
     * 12. Foreign key restrictOnDelete juga melindungi Material yang terhubung ke Outlet.
     */
    public function test_12_foreign_key_restricts_deletion_of_outlet_with_linked_material(): void
    {
        $unit = Unit::create(['unit_name' => 'Yard', 'symbol' => 'yd']);
        $outlet = Outlet::factory()->create(['outlet_code' => 'OUT-RESTRICT-02']);

        Material::create([
            'outlet_id'     => $outlet->id,
            'unit_id'       => $unit->id,
            'material_name' => 'Kain Rayon Twill',
        ]);

        $this->expectException(QueryException::class);
        $outlet->delete();
    }

    /**
     * 13. Unique index global existing tetap utuh dan berfungsi normal.
     */
    public function test_13_existing_global_unique_indexes_remain_strictly_intact(): void
    {
        $product = Product::create([
            'product_name' => 'French Khimar',
        ]);
        $this->assertNotNull($product->code);

        // 1. products.code unique index
        $this->expectException(QueryException::class);
        Product::create([
            'product_name' => 'French Khimar Duplikat',
            'code'         => $product->code,
        ]);
    }

    /**
     * 14. ProductVariant.sku global unique index tetap terlindungi.
     */
    public function test_14_product_variant_sku_unique_index_remains_intact(): void
    {
        $product = Product::create(['product_name' => 'Khimar Basic']);

        ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Navy',
            'sku'          => 'KHM-BSC-NVY',
        ]);

        // Duplicate SKU harus ditolak oleh unique index
        $this->expectException(QueryException::class);
        ProductVariant::create([
            'product_id'   => $product->id,
            'variant_name' => 'Navy Copy',
            'sku'          => 'KHM-BSC-NVY',
        ]);
    }

    /**
     * 15. DocumentSequence(prefix, period) composite unique index tetap terlindungi.
     */
    public function test_15_document_sequence_composite_unique_index_remains_intact(): void
    {
        DocumentSequence::create([
            'prefix'        => 'PO',
            'period'        => '202610',
            'last_sequence' => 5,
        ]);

        // Duplicate prefix + period harus ditolak oleh composite unique index
        $this->expectException(QueryException::class);
        DocumentSequence::create([
            'prefix'        => 'PO',
            'period'        => '202610',
            'last_sequence' => 6,
        ]);
    }

    /**
     * 16. Factory existing dan alur creation master data ERP lama tetap kompatibel 100%.
     */
    public function test_16_existing_factories_and_erp_workflow_creations_remain_fully_compatible(): void
    {
        // 1. OutletFactory tetap bekerja
        $outlet = Outlet::factory()->create();
        $this->assertNotNull($outlet->id);

        // 2. UserFactory tetap bekerja tanpa forOutlet
        $userDefault = User::factory()->create();
        $this->assertNull($userDefault->outlet_id);

        // 3. UserFactory dengan forOutlet tetap bekerja
        $userWithOutlet = User::factory()->forOutlet($outlet)->create();
        $this->assertSame($outlet->id, $userWithOutlet->outlet_id);

        // 4. Model master data ERP lama dapat dibuat tanpa mendefinisikan outlet_id
        $product = Product::create(['product_name' => 'Khimar Daily']);
        $this->assertDatabaseHas('products', [
            'id'        => $product->id,
            'outlet_id' => null,
        ]);
    }
}
