<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCodeFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_auto_suggests_vp_for_hijab_voal_polos_premium_if_code_omitted(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'product_name' => 'Hijab Voal Polos Premium',
            'description'  => 'Test Voal',
        ]);

        $response->assertRedirect();
        $product = Product::where('product_name', 'Hijab Voal Polos Premium')->first();
        $this->assertNotNull($product);
        $this->assertEquals('VP', $product->code, 'Hijab Voal Polos Premium should automatically derive code VP (Voal Premium)');
    }

    public function test_admin_can_adjust_product_code_before_first_save(): void
    {
        // Admin manually overrides suggestion to "VPR"
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'product_name' => 'Hijab Voal Polos Premium',
            'code'         => 'VPR',
            'description'  => 'Custom code',
        ]);

        $response->assertRedirect();
        $product = Product::where('product_name', 'Hijab Voal Polos Premium')->first();
        $this->assertNotNull($product);
        $this->assertEquals('VPR', $product->code, 'Admin-adjusted product code must be honored');
    }

    public function test_duplicate_product_code_is_rejected(): void
    {
        // Existing product with code VP
        Product::create([
            'product_name' => 'Voal Existing',
            'code'         => 'VP',
            'active'       => true,
        ]);

        // Attempting to create another product with code VP
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'product_name' => 'Voal Another',
            'code'         => 'VP',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertEquals(
            'Kode produk sudah digunakan. Gunakan kode lain.',
            session('errors')->first('code')
        );
    }

    public function test_product_code_is_normalized_to_uppercase(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'product_name' => 'Hijab Viscose',
            'code'         => 'vs', // lowercase
        ]);

        $response->assertRedirect();
        $product = Product::where('product_name', 'Hijab Viscose')->first();
        $this->assertNotNull($product);
        $this->assertEquals('VS', $product->code);
    }

    public function test_invalid_symbols_in_product_code_are_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'product_name' => 'Hijab Pashmina',
            'code'         => 'P S', // contains spaces
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_after_save_product_code_cannot_change_and_rename_preserves_code(): void
    {
        $product = Product::create([
            'product_name' => 'Hijab Voal Polos Premium',
            'code'         => 'VP',
            'active'       => true,
        ]);

        // Renaming product and attempting to alter code via PUT
        $response = $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'product_name' => 'Hijab Voal Premium Updated',
            'code'         => 'HACKED',
            'active'       => 1,
        ]);

        $response->assertRedirect();
        $product->refresh();

        $this->assertEquals('Hijab Voal Premium Updated', $product->product_name);
        $this->assertEquals('VP', $product->code, 'Product code must remain immutable after save');
    }

    public function test_full_sku_lifecycle_with_semantic_codes(): void
    {
        $product = Product::create([
            'product_name' => 'Hijab Voal Polos Premium',
            'code'         => 'VP',
            'active'       => true,
        ]);

        // 1. Existing Dusty Pink
        $v1 = ProductVariant::create([
            'product_id'        => $product->id,
            'variant_name'      => 'Dusty Pink',
            'sku'               => 'HJB-VP-DP-001',
            'requires_printing' => false,
            'active'            => true,
        ]);

        // 2. Next Mocca -> HJB-VP-MC-002
        $respMocca = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $product),
            [
                'variant_name'      => 'Mocca',
                'requires_printing' => 0,
                'active'            => 1,
            ]
        );
        $respMocca->assertRedirect();
        $v2 = ProductVariant::where('variant_name', 'Mocca')->first();
        $this->assertEquals('HJB-VP-MC-002', $v2->sku);

        // 3. Next Cream -> HJB-VP-CR-003
        $respCream = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $product),
            [
                'variant_name'      => 'Cream',
                'requires_printing' => 0,
                'active'            => 1,
            ]
        );
        $respCream->assertRedirect();
        $v3 = ProductVariant::where('variant_name', 'Cream')->first();
        $this->assertEquals('HJB-VP-CR-003', $v3->sku);

        // 4. Different product: Hijab Viscose (VS) -> Black -> HJB-VS-BL-001
        $viscose = Product::create([
            'product_name' => 'Hijab Viscose',
            'code'         => 'VS',
            'active'       => true,
        ]);
        $respBlack = $this->actingAs($this->admin)->post(
            route('admin.products.variants.store', $viscose),
            [
                'variant_name'      => 'Black',
                'requires_printing' => 0,
                'active'            => 1,
            ]
        );
        $respBlack->assertRedirect();
        $vBlack = ProductVariant::where('variant_name', 'Black')->first();
        $this->assertEquals('HJB-VS-BL-001', $vBlack->sku);
    }
}
