<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Master\BomItem;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use Database\Seeders\MaterialSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeederTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            UnitSeeder::class,
            MaterialSeeder::class,
            ProductSeeder::class,
        ]);

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_pashmina_kaos_exists_after_seeding(): void
    {
        $product = Product::where('product_name', 'Pashmina Kaos')->first();

        $this->assertNotNull($product);
        $this->assertTrue((bool) $product->active);
    }

    public function test_exactly_three_intended_variants_exist_for_pashmina_kaos(): void
    {
        $product = Product::where('product_name', 'Pashmina Kaos')->first();
        $this->assertNotNull($product);

        $variants = $product->variants;
        $this->assertCount(3, $variants);

        $variantNames = $variants->pluck('variant_name')->sort()->values()->all();
        $this->assertSame(['Cream', 'Hitam', 'Mocca'], $variantNames);
    }

    public function test_variant_skus_are_unique_and_match_conventions(): void
    {
        $product = Product::where('product_name', 'Pashmina Kaos')->first();
        $variants = $product->variants->keyBy('variant_name');

        $this->assertSame('PSH-KS-001', $variants['Hitam']->sku);
        $this->assertSame('PSH-KS-002', $variants['Mocca']->sku);
        $this->assertSame('PSH-KS-003', $variants['Cream']->sku);

        $allSkus = ProductVariant::pluck('sku')->all();
        $this->assertCount(count($allSkus), array_unique($allSkus));
    }

    public function test_variant_prices_and_printing_routes(): void
    {
        $product = Product::where('product_name', 'Pashmina Kaos')->first();
        $variants = $product->variants;

        foreach ($variants as $variant) {
            $this->assertEquals('50000.00', $variant->base_selling_price);
            $this->assertFalse((bool) $variant->requires_printing);
            $this->assertTrue((bool) $variant->active);
        }
    }

    public function test_existing_products_and_variants_remain_unchanged(): void
    {
        $voalPolos = Product::where('product_name', 'Hijab Voal Polos Premium')->first();
        $this->assertNotNull($voalPolos);
        $this->assertCount(4, $voalPolos->variants);

        $voalMotif = Product::where('product_name', 'Hijab Voal Motif')->first();
        $this->assertNotNull($voalMotif);
        $this->assertCount(2, $voalMotif->variants);

        $pashmina = Product::where('product_name', 'Hijab Pashmina')->first();
        $this->assertNotNull($pashmina);
        $this->assertCount(2, $pashmina->variants);

        $bergoJersey = Product::where('product_name', 'Hijab Bergo Jersey')->first();
        $this->assertNotNull($bergoJersey);
        $this->assertCount(2, $bergoJersey->variants);
    }

    public function test_no_fabricated_bom_rows_created_for_pashmina_kaos_variants(): void
    {
        $product = Product::where('product_name', 'Pashmina Kaos')->first();

        foreach ($product->variants as $variant) {
            $this->assertSame(0, $variant->bomItems()->count());
            $this->assertFalse(BomItem::where('variant_id', $variant->id)->exists());
        }
    }

    public function test_product_seeder_is_idempotent(): void
    {
        $initialProductCount = Product::count();
        $initialVariantCount = ProductVariant::count();
        $initialBomCount = BomItem::count();

        // Run ProductSeeder again
        $this->seed(ProductSeeder::class);

        $this->assertSame($initialProductCount, Product::count());
        $this->assertSame($initialVariantCount, ProductVariant::count());
        $this->assertSame($initialBomCount, BomItem::count());

        // Run ProductSeeder a third time
        $this->seed(ProductSeeder::class);

        $this->assertSame($initialProductCount, Product::count());
        $this->assertSame($initialVariantCount, ProductVariant::count());
        $this->assertSame($initialBomCount, BomItem::count());
    }

    public function test_admin_ui_shows_pashmina_kaos_variants_and_unconfigured_bom(): void
    {
        $product = Product::where('product_name', 'Pashmina Kaos')->first();

        // Check Product Show page
        $response = $this->actingAs($this->admin)->get(route('admin.products.show', $product));
        $response->assertOk();
        $response->assertSee('Pashmina Kaos');
        $response->assertSee('Hitam');
        $response->assertSee('Mocca');
        $response->assertSee('Cream');
        $response->assertSee('PSH-KS-001');
        $response->assertSee('PSH-KS-002');
        $response->assertSee('PSH-KS-003');
        $response->assertSee('Rp 50.000');
        $response->assertSee('Tanpa Printing');
        $response->assertSee('0 Material');

        // Check Variant Detail page for Hitam
        $hitamVariant = $product->variants()->where('variant_name', 'Hitam')->first();
        $variantResponse = $this->actingAs($this->admin)->get(route('admin.variants.show', $hitamVariant));
        $variantResponse->assertOk();
        $variantResponse->assertSee('Pashmina Kaos');
        $variantResponse->assertSee('Hitam');
        $variantResponse->assertSee('PSH-KS-001');
        $variantResponse->assertSee('Rp 50.000');
        $variantResponse->assertSee('Tanpa Printing');
        $variantResponse->assertSee('BOM Belum Diatur');
        $variantResponse->assertSee('Belum ada Material BOM');
        $variantResponse->assertSee('0 Jenis Material');
    }
}
