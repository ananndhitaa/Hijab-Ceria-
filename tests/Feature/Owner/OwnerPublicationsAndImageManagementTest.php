<?php

namespace Tests\Feature\Owner;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\Platform\OutletListingSlot;
use App\Models\Platform\TenantSubscription;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Platform\TenantEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerPublicationsAndImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected User $owner;
    protected TenantEntitlementService $entitlementService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => true]);
        Storage::fake('public');

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Kudus',
            'outlet_code' => 'OUT-001',
            'phone_wa' => '081234567890',
            'address' => 'Jl. Menara Kudus No. 10',
            'city' => 'Kudus',
            'status' => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'name' => 'Hj. Siti Owner',
            'email' => 'owner.siti@nayara.test',
            'role' => UserRole::OWNER,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->entitlementService = app(TenantEntitlementService::class);
    }

    public function test_owner_can_access_publications_page_with_correct_header_and_cards(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('owner.publications.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Etalase Publik');
        $response->assertSee('Kelola produk, foto, varian, dan publikasi katalog toko.');
        $response->assertSee('Total Kapasitas Slot');
        $response->assertSee('Produk Aktif Terbit');
        $response->assertSee('Sisa Slot Tersedia');
        $response->assertSee('Status Langganan');
        $response->assertSee('Tambah Kuota / Upgrade Premium');
        // Sidebar checks
        $response->assertSee('id="nayara-sidebar-nav"', false);
        $response->assertSee('Etalase Publik');
        $response->assertDontSee('Etalase Publik (Slot)');
        $response->assertSee('sessionStorage.getItem(\'nayara_sidebar_scroll_top\')', false);
    }

    public function test_publications_displays_table_with_search_and_filter(): void
    {
        $p1 = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Pashmina Ceruty Babydoll',
            'product_code' => 'PSH-CRTY-01',
            'public_category' => 'Pashmina',
            'public_price' => 55000,
            'is_published' => true,
        ]);

        $p2 = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Bergo Instan Maryam',
            'product_code' => 'BRG-MRY-01',
            'public_category' => 'Bergo',
            'public_price' => 35000,
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        // Search test
        $responseSearch = $this->get(route('owner.publications.index', ['search' => 'Ceruty']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Pashmina Ceruty Babydoll');
        $responseSearch->assertDontSee('Bergo Instan Maryam');

        // Filter published
        $responsePublished = $this->get(route('owner.publications.index', ['filter' => 'published']));
        $responsePublished->assertStatus(200);
        $responsePublished->assertSee('Pashmina Ceruty Babydoll');
        $responsePublished->assertDontSee('Bergo Instan Maryam');

        // Filter draft
        $responseDraft = $this->get(route('owner.publications.index', ['filter' => 'draft']));
        $responseDraft->assertStatus(200);
        $responseDraft->assertSee('Bergo Instan Maryam');
        $responseDraft->assertDontSee('Pashmina Ceruty Babydoll');
    }

    public function test_owner_can_publish_and_unpublish_product(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Hijab Segi Empat Paris',
            'product_code' => 'PRS-01',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        // Publish
        $publishResponse = $this->post(route('owner.publications.publish', $product->id));
        $publishResponse->assertRedirect();
        $this->assertTrue($product->fresh()->is_published);

        // Unpublish
        $unpublishResponse = $this->post(route('owner.publications.unpublish', $product->id));
        $unpublishResponse->assertRedirect();
        $this->assertFalse($product->fresh()->is_published);
    }

    public function test_owner_cannot_publish_product_when_quota_is_exhausted(): void
    {
        // Default free tier is 5 slots
        for ($i = 1; $i <= 5; $i++) {
            Product::create([
                'outlet_id' => $this->outlet->id,
                'name' => "Hijab Free {$i}",
                'product_code' => "FREE-{$i}",
                'is_published' => true,
            ]);
        }

        $sixth = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Hijab Ke-6 Melebihi Kuota',
            'product_code' => 'OVER-06',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        $response = $this->post(route('owner.publications.publish', $sixth->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertFalse($sixth->fresh()->is_published);
    }

    public function test_owner_can_upload_product_image_and_update_showcase(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Pashmina Silk Plisket',
            'product_code' => 'SLK-01',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        $file = UploadedFile::fake()->create('pashmina-silk.jpg', 150, 'image/jpeg');

        $response = $this->put(route('owner.publications.update-showcase', $product->id), [
            'public_category' => 'Pashmina',
            'public_price' => 65000,
            'public_description' => 'Pashmina bahan sutra premium bertekstur plisket rapi.',
            'featured_image' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $product->fresh();
        $this->assertNotNull($fresh->featured_image);
        Storage::disk('public')->assertExists($fresh->featured_image);
        $this->assertEquals('Pashmina', $fresh->public_category);
        $this->assertEquals(65000, (int) $fresh->public_price);
        $this->assertEquals('Pashmina bahan sutra premium bertekstur plisket rapi.', $fresh->public_description);
    }

    public function test_owner_can_replace_existing_image_with_automatic_cleanup(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Hijab Segi Empat Satin',
            'product_code' => 'STN-01',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        // Upload first image
        $file1 = UploadedFile::fake()->create('initial.jpg', 150, 'image/jpeg');
        $this->put(route('owner.publications.update-showcase', $product->id), [
            'featured_image' => $file1,
        ]);

        $initialPath = $product->fresh()->featured_image;
        $this->assertNotNull($initialPath);
        Storage::disk('public')->assertExists($initialPath);

        // Upload replacement image
        $file2 = UploadedFile::fake()->create('replacement.png', 150, 'image/png');
        $response = $this->put(route('owner.publications.update-showcase', $product->id), [
            'featured_image' => $file2,
        ]);

        $response->assertRedirect();
        $newPath = $product->fresh()->featured_image;

        $this->assertNotEquals($initialPath, $newPath);
        Storage::disk('public')->assertMissing($initialPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_owner_can_remove_product_image(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Khimar Jumbo Syari',
            'product_code' => 'KHM-01',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        // Upload image first
        $file = UploadedFile::fake()->create('khimar.webp', 150, 'image/webp');
        $this->put(route('owner.publications.update-showcase', $product->id), [
            'featured_image' => $file,
        ]);

        $savedPath = $product->fresh()->featured_image;
        $this->assertNotNull($savedPath);
        Storage::disk('public')->assertExists($savedPath);

        // Remove image
        $removeResponse = $this->put(route('owner.publications.update-showcase', $product->id), [
            'remove_image' => 1,
        ]);

        $removeResponse->assertRedirect();
        $this->assertNull($product->fresh()->featured_image);
        Storage::disk('public')->assertMissing($savedPath);
    }

    public function test_image_upload_validation_rules(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Hijab Voal Miracle',
            'product_code' => 'VOL-01',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        // Reject non-image file
        $pdfFile = UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf');
        $responsePdf = $this->put(route('owner.publications.update-showcase', $product->id), [
            'featured_image' => $pdfFile,
        ]);
        $responsePdf->assertSessionHasErrors('featured_image');

        // Reject file larger than 2MB (2048 KB)
        $largeFile = UploadedFile::fake()->create('huge.jpg', 3000, 'image/jpeg');
        $responseLarge = $this->put(route('owner.publications.update-showcase', $product->id), [
            'featured_image' => $largeFile,
        ]);
        $responseLarge->assertSessionHasErrors('featured_image');
    }

    public function test_uploaded_image_is_rendered_in_storefront_product_and_shop(): void
    {
        // Create product with image
        $imagePath = 'products/test-pashmina.jpg';
        Storage::disk('public')->put($imagePath, 'fake-image-content');

        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Pashmina Turban Eksklusif',
            'product_code' => 'TRB-01',
            'public_category' => 'Pashmina',
            'public_price' => 85000,
            'featured_image' => $imagePath,
            'is_published' => true,
        ]);

        // Check Storefront Product page
        $resProduct = $this->get(route('storefront.product', $product->id));
        $resProduct->assertStatus(200);
        $resProduct->assertSee('storage/' . $imagePath);
        $resProduct->assertDontSee('🧕');

        // Check Storefront Shop page
        $resShop = $this->get(route('storefront.shop', $this->outlet->id));
        $resShop->assertStatus(200);
        $resShop->assertSee('storage/' . $imagePath);
        $resShop->assertDontSee('🧕');
    }

    public function test_tenant_isolation_prevents_owner_from_mutating_other_tenant_products(): void
    {
        $otherOutlet = Outlet::create([
            'outlet_name' => 'Konveksi Solo Mandiri',
            'outlet_code' => 'OUT-SOLO',
            'status' => 'ACTIVE',
        ]);

        $foreignProduct = Product::create([
            'outlet_id' => $otherOutlet->id,
            'name' => 'Produk Milik Solo',
            'product_code' => 'SOLO-01',
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        // Attempt to publish foreign product
        $resPublish = $this->post(route('owner.publications.publish', $foreignProduct->id));
        $resPublish->assertStatus(403);

        // Attempt to unpublish foreign product
        $resUnpublish = $this->post(route('owner.publications.unpublish', $foreignProduct->id));
        $resUnpublish->assertStatus(403);

        // Attempt to update foreign product showcase
        $resUpdate = $this->put(route('owner.publications.update-showcase', $foreignProduct->id), [
            'public_price' => 50000,
            'public_category' => 'Hijab',
        ]);
        $resUpdate->assertStatus(403);
    }

    public function test_image_cleanup_preserves_files_shared_by_other_products(): void
    {
        $sharedPath = 'products/shared-catalog-image.jpg';
        Storage::disk('public')->put($sharedPath, 'shared-binary-content');

        $prod1 = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Produk A Berbagi Foto',
            'product_code' => 'SHR-01',
            'featured_image' => $sharedPath,
            'is_published' => false,
        ]);

        $prod2 = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Produk B Berbagi Foto',
            'product_code' => 'SHR-02',
            'featured_image' => $sharedPath,
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        // Remove image from Product A
        $res = $this->put(route('owner.publications.update-showcase', $prod1->id), [
            'remove_image' => 1,
        ]);
        $res->assertRedirect();

        $this->assertNull($prod1->fresh()->featured_image);
        $this->assertEquals($sharedPath, $prod2->fresh()->featured_image);

        // The file must still exist because Product B uses it!
        Storage::disk('public')->assertExists($sharedPath);
    }

    public function test_draft_product_is_not_accessible_via_public_storefront(): void
    {
        $draftProduct = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Produk Khusus Internal Draft',
            'product_code' => 'DRF-99',
            'is_published' => false,
        ]);

        $response = $this->get(route('storefront.product', $draftProduct->id));
        $response->assertStatus(404);
    }

    public function test_storage_fallback_route_blocks_traversal_and_hidden_files(): void
    {
        // Path traversal attempts must be rejected (403 Forbidden or 404 Not Found)
        $resTraversal1 = $this->get('/storage/../../.env');
        $this->assertTrue(in_array($resTraversal1->status(), [403, 404], true));

        $resTraversal2 = $this->get('/storage/products/../../composer.json');
        $this->assertTrue(in_array($resTraversal2->status(), [403, 404], true));

        // Non-existent file must return 404
        $resMissing = $this->get('/storage/products/non-existent-image.jpg');
        $resMissing->assertStatus(404);
    }

    public function test_storage_route_blocks_executable_and_hidden_extensions(): void
    {
        // Dangerous extensions must be blocked
        $resPhp = $this->get('/storage/products/malicious.php');
        $this->assertTrue(in_array($resPhp->status(), [403, 404], true));

        $resEnv = $this->get('/storage/.env');
        $this->assertTrue(in_array($resEnv->status(), [403, 404], true));

        $resSh = $this->get('/storage/shell.sh');
        $this->assertTrue(in_array($resSh->status(), [403, 404], true));
    }

    public function test_photo_replacement_atomic_rollback_on_failure(): void
    {
        // 1. Existing product with photo
        $oldPath = 'products/initial-good-image.jpg';
        Storage::disk('public')->put($oldPath, 'old-image-binary');

        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Produk Rollback Test',
            'product_code' => 'RLB-01',
            'featured_image' => $oldPath,
            'is_published' => false,
        ]);

        $this->actingAs($this->owner);

        // 2. Upload with invalid price (negative) which triggers validation failure before DB persist
        $newFile = UploadedFile::fake()->create('attempt.jpg', 100, 'image/jpeg');
        $res = $this->put(route('owner.publications.update-showcase', $product->id), [
            'featured_image' => $newFile,
            'public_price' => -500, // Invalid price!
        ]);

        $res->assertSessionHasErrors('public_price');

        // 3. Verify old file is intact and database is unchanged
        $this->assertEquals($oldPath, $product->fresh()->featured_image);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_publications_renders_responsive_grid_and_variant_counts(): void
    {
        $product = Product::create([
            'outlet_id' => $this->outlet->id,
            'name' => 'Pashmina Ceruty Premium 2026',
            'product_code' => 'PSH-CRTY-26',
            'public_category' => 'Pashmina Premium',
            'public_price' => 79000,
            'is_published' => true,
        ]);

        \App\Models\Master\ProductVariant::create([
            'outlet_id' => $this->outlet->id,
            'product_id' => $product->id,
            'variant_name' => 'Dusty Pink',
            'sku' => 'VAR-PSH-DP',
            'base_selling_price' => 79000,
            'active' => true,
        ]);

        \App\Models\Master\ProductVariant::create([
            'outlet_id' => $this->outlet->id,
            'product_id' => $product->id,
            'variant_name' => 'Milk Tea',
            'sku' => 'VAR-PSH-MT',
            'base_selling_price' => 79000,
            'active' => true,
        ]);

        $this->actingAs($this->owner);
        $response = $this->get(route('owner.publications.index'));

        $response->assertStatus(200);
        $response->assertSee('Pashmina Ceruty Premium 2026');
        $response->assertSee('2 Varian');
        $response->assertSee('Kelola Varian (2)');
        $response->assertSee('Kelola Varian');
        $response->assertSee('Edit Produk &amp; Etalase', false);
        $response->assertSee('Lihat Produk');
        $response->assertDontSee(route('admin.products.index'));
        $response->assertSee('VAR-PSH-DP');
        $response->assertSee('VAR-PSH-MT');
    }

    public function test_publications_drawer_contains_tabs_and_links(): void
    {
        $this->actingAs($this->owner);
        $response = $this->get(route('owner.publications.index'));

        $response->assertStatus(200);
        $response->assertSee('drawerOpen');
        $response->assertSee('activeDrawerTab');
        $response->assertSee('Informasi');
        $response->assertSee('Varian');
        $response->assertSee('Etalase');
        // Ensure no 403-causing admin route exists in owner view
        $response->assertDontSee(route('admin.products.index'));
    }
}
