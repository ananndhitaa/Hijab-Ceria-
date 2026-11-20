<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\Platform\OutletListingSlot;
use App\Models\Platform\TenantSubscription;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Platform\ProductPublicationService;
use App\Services\Platform\TenantEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StorefrontAndListingSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected User $owner;
    protected TenantEntitlementService $entitlementService;
    protected ProductPublicationService $publicationService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Kudus',
            'outlet_code' => 'OUT-001',
            'phone_wa' => '081234567890',
            'address' => 'Jl. Menara No. 10',
            'status' => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'name' => 'Owner Kudus',
            'email' => 'owner.kudus@nayara.test',
            'role' => UserRole::OWNER,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->entitlementService = app(TenantEntitlementService::class);
        $this->publicationService = app(ProductPublicationService::class);
    }

    public function test_public_storefront_can_be_viewed_without_authentication(): void
    {
        $response = $this->get(route('storefront.index'));
        $response->assertStatus(200);
        $response->assertSee('NAYARA');
        $response->assertSee('Modest Hijab');
    }

    public function test_public_storefront_only_displays_published_products(): void
    {
        $published = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Pashmina Silk Premium',
            'product_code' => 'PSH-SILK-01',
            'is_published' => true,
            'public_price' => 75000,
            'public_category' => 'Pashmina',
        ]);

        $draft = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Bergo Maryam Internal',
            'product_code' => 'BRG-INT-01',
            'is_published' => false,
            'public_price' => 35000,
        ]);

        $response = $this->get(route('storefront.index'));
        $response->assertStatus(200);
        $response->assertSee('Pashmina Silk Premium');
        $response->assertDontSee('Bergo Maryam Internal');
    }

    public function test_public_storefront_does_not_leak_cost_price_bom_or_internal_finance(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Hijab Segi Empat Voal',
            'product_code' => 'VOAL-01',
            'is_published' => true,
            'public_price' => 45000,
            'public_description' => 'Hijab voal lembut nyaman seharian.',
        ]);

        $response = $this->get(route('storefront.product', $product->id));
        $response->assertStatus(200);
        $response->assertSee('Hijab Segi Empat Voal');
        $response->assertSee('45.000');
        $response->assertDontSee('HPP');
        $response->assertDontSee('Bill of Materials');
        $response->assertDontSee('Harga Pokok');
    }

    public function test_public_storefront_whatsapp_link_uses_authorized_shop_phone(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Pashmina Plisket',
            'product_code' => 'PLIS-01',
            'is_published' => true,
            'public_price' => 50000,
        ]);

        $response = $this->get(route('storefront.product', $product->id));
        $response->assertStatus(200);
        // Phone 081234567890 converted to 6281234567890
        $response->assertSee('https://wa.me/6281234567890');
    }

    public function test_free_tier_listing_capacity_is_strictly_5_slots(): void
    {
        $capacity = $this->entitlementService->getListingCapacity($this->outlet->id);
        $this->assertEquals(5, $capacity);
        $this->assertEquals(0, $this->entitlementService->getPublishedListingCount($this->outlet->id));
        $this->assertEquals(5, $this->entitlementService->getRemainingListingSlots($this->outlet->id));
    }

    public function test_publishing_product_consumes_slot_quota(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Hijab Paris Premium',
            'product_code' => 'PRS-01',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        $response = $this->post(route('owner.publications.publish', $product->id));
        $response->assertRedirect();

        $this->assertTrue($product->fresh()->is_published);
        $this->assertEquals(1, $this->entitlementService->getPublishedListingCount($this->outlet->id));
        $this->assertEquals(4, $this->entitlementService->getRemainingListingSlots($this->outlet->id));
    }

    public function test_publishing_fails_when_slot_quota_exceeded(): void
    {
        // Publish 5 products to exhaust free tier
        for ($i = 1; $i <= 5; $i++) {
            Product::create([
                'outlet_id' => $this->outlet->id,
                'name' => "Product {$i}",
                'product_code' => "PROD-{$i}",
                'is_published' => true,
            ]);
        }

        $this->assertEquals(5, $this->entitlementService->getPublishedListingCount($this->outlet->id));
        $this->assertEquals(0, $this->entitlementService->getRemainingListingSlots($this->outlet->id));

        $sixthProduct = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Product 6',
            'product_code' => 'PROD-6',
            'is_published' => false,
        ]);

        $this->expectException(ValidationException::class);
        $this->publicationService->publish($sixthProduct, $this->outlet->id);

        $this->assertFalse($sixthProduct->fresh()->is_published);
    }

    public function test_unpublishing_restores_available_quota(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Product To Unpublish',
            'product_code' => 'UNPUB-01',
            'is_published' => true,
        ]);

        $this->assertEquals(1, $this->entitlementService->getPublishedListingCount($this->outlet->id));

        $this->publicationService->unpublish($product, $this->outlet->id);

        $this->assertFalse($product->fresh()->is_published);
        $this->assertEquals(0, $this->entitlementService->getPublishedListingCount($this->outlet->id));
        $this->assertEquals(5, $this->entitlementService->getRemainingListingSlots($this->outlet->id));
    }

    public function test_owner_can_update_public_showcase_details(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Hijab Segi Tiga',
            'product_code' => 'SGT-01',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        $response = $this->put(route('owner.publications.update-showcase', $product->id), [
            'public_description' => 'Hijab instan segitiga praktis.',
            'public_price' => 38000,
            'public_category' => 'Instan',
        ]);

        $response->assertRedirect();
        $fresh = $product->fresh();
        $this->assertEquals('Hijab instan segitiga praktis.', $fresh->public_description);
        $this->assertEquals(38000, (int) $fresh->public_price);
        $this->assertEquals('Instan', $fresh->public_category);
    }

    public function test_category_showcase_resolves_image_from_real_database_product(): void
    {
        Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Hijab Segi Empat Voal Exclusive',
            'product_name' => 'Hijab Segi Empat Voal Exclusive',
            'product_code' => 'SGE-01',
            'public_category' => 'Hijab Segi Empat',
            'featured_image' => 'products/sample-segiempat.jpg',
            'is_published' => true,
        ]);

        $response = $this->get(route('landing'));
        $response->assertStatus(200);
        $response->assertViewHas('categoryShowcases');

        $categories = $response->viewData('categoryShowcases');
        $segiEmpatCat = collect($categories)->firstWhere('name', 'Hijab Segi Empat');

        $this->assertNotNull($segiEmpatCat);
        $this->assertEquals('Hijab Segi Empat', $segiEmpatCat['filter']);
    }

    public function test_storefront_product_detail_renders_interactive_variant_selector_for_published_product(): void
    {
        $product = Product::create([
            'outlet_id'       => $this->outlet->id,
            'name'            => 'Hijab Voal Motif',
            'product_code'    => 'VM-01',
            'public_category' => 'Pashmina Premium',
            'is_published'    => true,
        ]);

        $product->variants()->createMany([
            [
                'variant_name'       => 'Floral Mauve',
                'sku'                => 'HJB-VM-FM-001',
                'base_selling_price' => 105000,
                'active'             => true,
                'outlet_id'          => $this->outlet->id,
            ],
            [
                'variant_name'       => 'Geometric Sage',
                'sku'                => 'HJB-VM-GS-002',
                'base_selling_price' => 115000,
                'active'             => true,
                'outlet_id'          => $this->outlet->id,
            ],
        ]);

        $response = $this->get(route('storefront.product', $product->id));
        $response->assertStatus(200);
        $response->assertSee('Hijab Voal Motif');
        $response->assertSee('Floral Mauve');
        $response->assertSee('Geometric Sage');
        $response->assertSee('HJB-VM-FM-001');
        $response->assertSee('HJB-VM-GS-002');
        $response->assertSee('selectVariant');
        $response->assertSee('PILIHAN VARIAN &amp; HARGA MASTER', false);
    }

    public function test_storefront_product_detail_renders_variant_selector_for_unpublished_draft_product_when_viewed_by_staff(): void
    {
        $product = Product::create([
            'outlet_id'       => $this->outlet->id,
            'name'            => 'Hijab Segi Empat Oval',
            'product_code'    => 'SO-01',
            'public_category' => 'Hijab Segi Empat',
            'is_published'    => false,
        ]);

        $product->variants()->create([
            'variant_name'       => 'Sage Green',
            'sku'                => 'HJB-SO-SG-001',
            'base_selling_price' => 89000,
            'active'             => true,
            'outlet_id'          => $this->outlet->id,
        ]);

        // Public visitor cannot access unpublished product (404)
        $this->get(route('storefront.product', $product->id))->assertStatus(404);

        // Owner can preview it
        $this->actingAs($this->owner);
        $resOwner = $this->get(route('storefront.product', $product->id));
        $resOwner->assertStatus(200);
        $resOwner->assertSee('Mode Pratinjau Owner');
        $resOwner->assertSee('Sage Green');
        $resOwner->assertSee('HJB-SO-SG-001');
    }

    public function test_storefront_product_detail_includes_variant_image_url_and_dynamic_photo_switching(): void
    {
        $product = Product::create([
            'outlet_id'       => $this->outlet->id,
            'name'            => 'Hijab Viscose Premium Linen',
            'product_code'    => 'VL-01',
            'public_category' => 'Viscose Premium',
            'featured_image'  => 'products/default_product.jpg',
            'is_published'    => true,
            'public_price'    => 67000,
        ]);

        $v1 = $product->variants()->create([
            'variant_name'       => 'Nude',
            'sku'                => 'HJB-VL-ND-001',
            'image_path'         => 'products/variants/nude.jpg',
            'base_selling_price' => 67000,
            'active'             => true,
            'outlet_id'          => $this->outlet->id,
        ]);

        $v2 = $product->variants()->create([
            'variant_name'       => 'Taro',
            'sku'                => 'HJB-VL-TR-002',
            'image_path'         => 'products/variants/taro.jpg',
            'base_selling_price' => 67000,
            'active'             => true,
            'outlet_id'          => $this->outlet->id,
        ]);

        $response = $this->get(route('storefront.product', $product->id));
        $response->assertStatus(200);

        // Verify variant images are passed in Alpine json payload
        $response->assertSee('nude.jpg');
        $response->assertSee('taro.jpg');
        $response->assertSee('currentImageUrl');
        $response->assertSee(':src="currentImageUrl"', false);

        // Test deep linking with ?variant={id}
        $responseWithVariant = $this->get(route('storefront.product', ['product' => $product->id, 'variant' => $v2->id]));
        $responseWithVariant->assertStatus(200);
        $responseWithVariant->assertSee("initialParam: '{$v2->id}'", false);
    }

    public function test_owner_can_update_variant_image(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'outlet_id'    => $this->outlet->id,
            'name'         => 'Hijab Voal Motif',
            'product_code' => 'VM-01',
            'is_published' => true,
        ]);

        $variant = $product->variants()->create([
            'variant_name'       => 'Geometric Sage',
            'sku'                => 'HJB-VM-GS-002',
            'base_selling_price' => 105000,
            'active'             => true,
            'outlet_id'          => $this->outlet->id,
        ]);

        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->create('geometric-sage.jpg', 100, 'image/jpeg');

        $response = $this->put(route('owner.publications.update-variant-image', $variant->id), [
            'image' => $file,
        ]);

        $response->assertRedirect();
        $variant->refresh();

        $this->assertNotNull($variant->image_path);
        Storage::disk('public')->assertExists($variant->image_path);
    }

    public function test_storefront_hero_slider_has_continuous_autoplay_and_high_res_slides(): void
    {
        $response = $this->get(route('storefront.index'));
        $response->assertStatus(200);

        // Verify autoplay logic exists in alpine x-data
        $response->assertSee('startAutoplay', false);
        $response->assertSee('setInterval', false);
        $response->assertSee('4000', false);

        // Verify hero slides render HD images
        $response->assertSee('hero-model.jpg');
        $response->assertDontSee('🧕');
    }
}
