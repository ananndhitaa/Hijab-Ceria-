<?php

namespace Tests\Feature\MultiTenant;

use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\Outlet;
use App\Services\Master\ProductVariantSkuGenerator;
use App\Services\System\DocumentNumberGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SkuAndSequenceHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);
    }

    /**
     * Requirement 23: Same SKU across two distinct tenants is allowed.
     */
    public function test_same_sku_across_two_distinct_tenants_is_accepted(): void
    {
        $outletA = Outlet::create(['outlet_name' => 'Outlet Convection A', 'outlet_code' => 'OUT-A', 'status' => 'ACTIVE']);
        $outletB = Outlet::create(['outlet_name' => 'Outlet Convection B', 'outlet_code' => 'OUT-B', 'status' => 'ACTIVE']);

        $productA = Product::create([
            'outlet_id'    => $outletA->id,
            'product_name' => 'Hijab Bergo Maryam A',
            'code'         => 'PRD-A',
            'active'       => true,
        ]);

        $productB = Product::create([
            'outlet_id'    => $outletB->id,
            'product_name' => 'Hijab Bergo Maryam B',
            'code'         => 'PRD-B',
            'active'       => true,
        ]);

        // Outlet A creates SKU BERGO-001
        $variantA = ProductVariant::create([
            'outlet_id'          => $outletA->id,
            'product_id'         => $productA->id,
            'sku'                => 'BERGO-001',
            'variant_name'       => 'Bergo Maryam Hitam Outlet A',
            'base_selling_price' => 45000,
            'active'             => true,
        ]);

        // Outlet B creates the SAME SKU BERGO-001 -> MUST SUCCEED without SQLSTATE 23000!
        $variantB = ProductVariant::create([
            'outlet_id'          => $outletB->id,
            'product_id'         => $productB->id,
            'sku'                => 'BERGO-001',
            'variant_name'       => 'Bergo Maryam Hitam Outlet B',
            'base_selling_price' => 48000,
            'active'             => true,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id'        => $variantA->id,
            'outlet_id' => $outletA->id,
            'sku'       => 'BERGO-001',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id'        => $variantB->id,
            'outlet_id' => $outletB->id,
            'sku'       => 'BERGO-001',
        ]);
    }

    /**
     * Requirement 24: Duplicate SKU within the same tenant is strictly rejected with unique violation.
     */
    public function test_duplicate_sku_within_the_same_tenant_is_rejected(): void
    {
        $outlet = Outlet::create(['outlet_name' => 'Outlet Solo', 'outlet_code' => 'SOLO', 'status' => 'ACTIVE']);
        $product = Product::create([
            'outlet_id'    => $outlet->id,
            'product_name' => 'Pashmina Ceruty',
            'code'         => 'PRD-PASHMINA',
            'active'       => true,
        ]);

        ProductVariant::create([
            'outlet_id'          => $outlet->id,
            'product_id'         => $product->id,
            'sku'                => 'PASH-CERUTY-01',
            'variant_name'       => 'Pashmina Mocca',
            'base_selling_price' => 35000,
            'active'             => true,
        ]);

        $this->expectException(QueryException::class);

        // Duplicate SKU within the same outlet -> Must throw QueryException (SQLSTATE 23000)
        ProductVariant::create([
            'outlet_id'          => $outlet->id,
            'product_id'         => $product->id,
            'sku'                => 'PASH-CERUTY-01',
            'variant_name'       => 'Pashmina Mocca Duplikat',
            'base_selling_price' => 35000,
            'active'             => true,
        ]);
    }

    /**
     * Requirement 25: Legacy NULL outlet_id records retain SKU uniqueness without backfill.
     */
    public function test_legacy_null_outlet_sku_uniqueness_is_preserved(): void
    {
        $product = Product::create([
            'outlet_id'    => null,
            'product_name' => 'Legacy Hijab',
            'code'         => 'PRD-LEGACY',
            'active'       => true,
        ]);

        ProductVariant::create([
            'outlet_id'          => null,
            'product_id'         => $product->id,
            'sku'                => 'LEGACY-SKU-99',
            'variant_name'       => 'Legacy Variant 1',
            'base_selling_price' => 20000,
            'active'             => true,
        ]);

        // A second legacy variant with DIFFERENT SKU is allowed
        $var2 = ProductVariant::create([
            'outlet_id'          => null,
            'product_id'         => $product->id,
            'sku'                => 'LEGACY-SKU-100',
            'variant_name'       => 'Legacy Variant 2',
            'base_selling_price' => 20000,
            'active'             => true,
        ]);
        $this->assertNotNull($var2);

        // A duplicate SKU within legacy NULL must be rejected
        $this->expectException(QueryException::class);
        ProductVariant::create([
            'outlet_id'          => null,
            'product_id'         => $product->id,
            'sku'                => 'LEGACY-SKU-99',
            'variant_name'       => 'Legacy Variant Duplicate',
            'base_selling_price' => 20000,
            'active'             => true,
        ]);
    }

    /**
     * Requirement 26 & 27: Independent document sequences across outlets and monotonic uniqueness.
     */
    public function test_independent_document_sequences_across_outlets(): void
    {
        $generator = app(DocumentNumberGenerator::class);

        $outletA = Outlet::create(['outlet_name' => 'Outlet Alpha', 'outlet_code' => 'ALP', 'status' => 'ACTIVE']);
        $outletB = Outlet::create(['outlet_name' => 'Outlet Beta',  'outlet_code' => 'BET', 'status' => 'ACTIVE']);

        $date = \Carbon\Carbon::parse('2026-10-01');

        // Generate sequences for Outlet A
        $docA1 = $generator->generate('INV', $date, $outletA->id);
        $docA2 = $generator->generate('INV', $date, $outletA->id);

        $this->assertEquals('INV-202610-0001', $docA1);
        $this->assertEquals('INV-202610-0002', $docA2);

        // Generate sequences for Outlet B for the SAME prefix & period
        // MUST start at 0001 independently without colliding with Outlet A!
        $docB1 = $generator->generate('INV', $date, $outletB->id);
        $docB2 = $generator->generate('INV', $date, $outletB->id);

        $this->assertEquals('INV-202610-0001', $docB1);
        $this->assertEquals('INV-202610-0002', $docB2);

        // Verify sequence table has separate records
        $seqA = DB::table('document_sequences')
            ->where('prefix', 'INV')
            ->where('period', '202610')
            ->where('outlet_id', $outletA->id)
            ->first();

        $seqB = DB::table('document_sequences')
            ->where('prefix', 'INV')
            ->where('period', '202610')
            ->where('outlet_id', $outletB->id)
            ->first();

        $this->assertNotNull($seqA);
        $this->assertNotNull($seqB);
        $this->assertEquals(2, $seqA->last_sequence);
        $this->assertEquals(2, $seqB->last_sequence);
    }
}
