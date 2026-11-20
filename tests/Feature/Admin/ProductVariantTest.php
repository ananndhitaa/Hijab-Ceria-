<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin   = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->product = Product::create([
            'product_name' => 'Hijab Voal Polos Premium',
            'code'         => 'VP',
            'active'       => true,
        ]);
    }

    public function test_variant_can_be_created_without_sku_and_sku_is_automatically_generated(): void
    {
        $payload = [
            'variant_name'                         => 'Dusty Pink',
            'base_selling_price'                   => 75000,
            'requires_printing'                    => 0,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => 1,
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $this->product),
            $payload
        );

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Dusty Pink')->first();
        $this->assertNotNull($variant);
        $this->assertNotEmpty($variant->sku);
        $this->assertMatchesRegularExpression('/^HJB-VP-[A-Z0-9]+-001$/', $variant->sku);
        $this->assertEquals('HJB-VP-DP-001', $variant->sku);
    }

    public function test_multiple_variants_created_sequentially_get_different_unique_skus(): void
    {
        $variantsData = ['Dusty Pink', 'Mocca', 'Cream'];
        $generatedSkus = [];

        foreach ($variantsData as $name) {
            $response = $this->actingAs($this->admin)->post(
                route('admin.products.variants.store', $this->product),
                [
                    'variant_name'      => $name,
                    'requires_printing' => 0,
                    'active'            => 1,
                ]
            );
            $response->assertRedirect();

            $variant = ProductVariant::where('variant_name', $name)->first();
            $this->assertNotNull($variant);
            $this->assertNotEmpty($variant->sku);
            $generatedSkus[] = $variant->sku;
        }

        // Assert all SKUs are strictly unique and match semantic format
        $this->assertCount(3, array_unique($generatedSkus));
        $this->assertEquals('HJB-VP-DP-001', $generatedSkus[0]);
        $this->assertEquals('HJB-VP-MC-002', $generatedSkus[1]);
        $this->assertEquals('HJB-VP-CR-003', $generatedSkus[2]);
    }

    public function test_sequence_resets_for_different_products(): void
    {
        // First variant under Voal Polos
        $response1 = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $this->product),
            [
                'variant_name'      => 'Dusty Pink',
                'requires_printing' => 0,
                'active'            => 1,
            ]
        );
        $response1->assertRedirect();
        $var1 = ProductVariant::where('variant_name', 'Dusty Pink')->first();
        $this->assertEquals('HJB-VP-DP-001', $var1->sku);

        // Different Product: Hijab Viscose
        $viscoseProduct = Product::create([
            'product_name' => 'Hijab Viscose',
            'code'         => 'VS',
            'active'       => true,
        ]);

        $response2 = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $viscoseProduct),
            [
                'variant_name'      => 'Black',
                'requires_printing' => 0,
                'active'            => 1,
            ]
        );
        $response2->assertRedirect();
        $var2 = ProductVariant::where('variant_name', 'Black')->first();
        $this->assertEquals('HJB-VS-BL-001', $var2->sku);
    }

    public function test_store_ignores_arbitrary_sku_in_request_payload(): void
    {
        $payload = [
            'variant_name'      => 'Lavender',
            'sku'               => 'HACKED-CUSTOM-SKU-999', // Crafted SKU
            'requires_printing' => 0,
            'active'            => 1,
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $this->product),
            $payload
        );

        $response->assertRedirect();
        $variant = ProductVariant::where('variant_name', 'Lavender')->first();
        $this->assertNotNull($variant);
        $this->assertNotEquals('HACKED-CUSTOM-SKU-999', $variant->sku);
        $this->assertMatchesRegularExpression('/^HJB-VP-[A-Z0-9]+-001$/', $variant->sku);
    }

    public function test_sku_generator_handles_collisions_with_preexisting_manual_sku(): void
    {
        // Pre-existing manual variant HJB-VP-DP-001
        ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'HJB-VP-DP-001',
            'requires_printing' => false,
            'active'            => true,
        ]);

        // Creating next variant (Mocca) should detect 001 and skip to 002
        $response = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $this->product),
            [
                'variant_name'      => 'Mocca',
                'requires_printing' => 0,
                'active'            => 1,
            ]
        );

        $response->assertRedirect();
        $newVariant = ProductVariant::where('variant_name', 'Mocca')->first();
        $this->assertNotNull($newVariant);
        $this->assertEquals('HJB-VP-MC-002', $newVariant->sku);
    }

    public function test_base_selling_price_is_nullable(): void
    {
        $payload = [
            'variant_name'                         => 'Navy',
            'base_selling_price'                   => null,
            'requires_printing'                    => 1,
            'printing_cost_per_unit'               => '2500.00',
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => 1,
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $this->product),
            $payload
        );

        $variant = ProductVariant::where('variant_name', 'Navy')->first();
        $this->assertNotNull($variant);
        $this->assertNull($variant->base_selling_price);
        $this->assertTrue($variant->requires_printing);
        $this->assertNotEmpty($variant->sku);
    }

    public function test_base_selling_price_validates_as_decimal(): void
    {
        $payload = [
            'variant_name'                         => 'Mocca',
            'base_selling_price'                   => 85000.50,
            'requires_printing'                    => 0,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => 1,
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $this->product),
            $payload
        );

        $variant = ProductVariant::where('variant_name', 'Mocca')->first();
        $this->assertNotNull($variant);
        $this->assertEquals(85000.50, $variant->base_selling_price);
        $this->assertFalse($variant->requires_printing);
    }

    public function test_nested_variant_creation_cannot_spoof_product_id(): void
    {
        $otherProduct = Product::create(['product_name' => 'Produk Lain', 'code' => 'PL', 'active' => true]);

        $payload = [
            'variant_name'                         => 'Maroon',
            'base_selling_price'                   => 75000,
            'requires_printing'                    => 0,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'product_id'                           => $otherProduct->id, // Attempting to spoof product_id
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $this->product),
            $payload
        );

        $variant = ProductVariant::where('variant_name', 'Maroon')->first();
        $this->assertNotNull($variant);
        $this->assertEquals($this->product->id, $variant->product_id);
    }

    public function test_requires_printing_is_required_on_store_and_update(): void
    {
        // Missing requires_printing on store
        $responseStore = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $this->product),
            [
                'variant_name'                         => 'Tosca',
                'direct_labor_cost_per_unit'           => '5000.00',
                'packaging_process_cost_per_good_unit' => '1000.00',
                'other_direct_cost_per_unit'           => '500.00',
            ]
        );
        $responseStore->assertSessionHasErrors('requires_printing');

        // Variant created
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Tosca',
            'sku'                                  => 'HJB-VP-TS-099',
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '5000.00',
            'packaging_process_cost_per_good_unit' => '1000.00',
            'other_direct_cost_per_unit'           => '500.00',
            'active'                               => true,
        ]);

        // Missing requires_printing on update
        $responseUpdate = $this->actingAs($this->admin)->put(
            route('admin.variants.update', $variant),
            [
                'variant_name'                         => 'Tosca Updated',
                'direct_labor_cost_per_unit'           => '5000.00',
                'packaging_process_cost_per_good_unit' => '1000.00',
                'other_direct_cost_per_unit'           => '500.00',
            ]
        );
        $responseUpdate->assertSessionHasErrors('requires_printing');
    }

    public function test_update_variant_preserves_existing_sku_even_with_crafted_request(): void
    {
        $originalSku = 'HJB-VP-DP-001';
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => $originalSku,
            'requires_printing' => false,
            'active'            => true,
        ]);

        // Attempting to change SKU via PUT payload
        $response = $this->actingAs($this->admin)->put(
            route('admin.variants.update', $variant),
            [
                'variant_name'      => 'Dusty Pink Updated',
                'sku'               => 'ATTEMPTED-NEW-SKU-999', // Crafted change
                'requires_printing' => 0,
                'active'            => 1,
            ]
        );

        $response->assertRedirect();
        $variant->refresh();

        $this->assertEquals('Dusty Pink Updated', $variant->variant_name);
        $this->assertEquals($originalSku, $variant->sku, 'SKU must remain immutable across updates');
    }

    public function test_rename_variant_and_tariff_update_preserves_sku(): void
    {
        $variant = ProductVariant::create([
            'product_id'                           => $this->product->id,
            'variant_name'                         => 'Dusty Pink',
            'sku'                                  => 'HJB-VP-DP-001',
            'base_selling_price'                   => 70000,
            'requires_printing'                    => false,
            'direct_labor_cost_per_unit'           => '3000.00',
            'packaging_process_cost_per_good_unit' => '500.00',
            'other_direct_cost_per_unit'           => '200.00',
            'active'                               => true,
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('admin.variants.update', $variant),
            [
                'variant_name'                         => 'Dusty Pink Luxury',
                'base_selling_price'                   => 85000,
                'requires_printing'                    => 1,
                'printing_cost_per_unit'               => '3500.00',
                'direct_labor_cost_per_unit'           => '4500.00',
                'packaging_process_cost_per_good_unit' => '800.00',
                'other_direct_cost_per_unit'           => '300.00',
                'active'                               => 1,
            ]
        );

        $response->assertRedirect();
        $variant->refresh();

        $this->assertEquals('Dusty Pink Luxury', $variant->variant_name);
        $this->assertEquals(85000, $variant->base_selling_price);
        $this->assertTrue($variant->requires_printing);
        $this->assertEquals('3500.00', $variant->printing_cost_per_unit);
        $this->assertEquals('HJB-VP-DP-001', $variant->sku, 'SKU must not change when name or tariffs are updated');
    }

    public function test_rename_product_preserves_product_code_and_sku(): void
    {
        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'HJB-VP-DP-001',
            'requires_printing' => false,
            'active'            => true,
        ]);

        // Rename product
        $response = $this->actingAs($this->admin)->put(
            route('admin.products.update', $this->product),
            [
                'product_name' => 'Hijab Voal Super Premium Renamed',
                'code'         => 'HACKED_CODE', // Attempt to tamper code
                'active'       => 1,
            ]
        );

        $response->assertRedirect();
        $this->product->refresh();
        $variant->refresh();

        $this->assertEquals('Hijab Voal Super Premium Renamed', $this->product->product_name);
        $this->assertEquals('VP', $this->product->code, 'Product code must remain immutable');
        $this->assertEquals('HJB-VP-DP-001', $variant->sku, 'Variant SKU must remain immutable');
    }

    public function test_admin_can_update_variant_with_image(): void
    {
        Storage::fake('public');

        $variant = ProductVariant::create([
            'product_id'        => $this->product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'HJB-VP-DP-001',
            'requires_printing' => false,
            'active'            => true,
        ]);

        $file = UploadedFile::fake()->create('dusty-pink.jpg', 150, 'image/jpeg');

        $response = $this->actingAs($this->admin)->put(
            route('admin.variants.update', $variant),
            [
                'variant_name'      => 'Dusty Pink Premium',
                'requires_printing' => 0,
                'active'            => 1,
                'image'             => $file,
            ]
        );

        $response->assertRedirect(route('admin.variants.show', $variant));
        $variant->refresh();

        $this->assertEquals('Dusty Pink Premium', $variant->variant_name);
        $this->assertNotNull($variant->image_path);
        Storage::disk('public')->assertExists($variant->image_path);
    }
}
