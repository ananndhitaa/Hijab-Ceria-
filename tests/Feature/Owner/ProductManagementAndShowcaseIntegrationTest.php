<?php

namespace Tests\Feature\Owner;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementAndShowcaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected User $ownerA;
    protected User $adminA;
    protected User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => true]);

        // Outlet A (Tenant 1)
        $this->outletA = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Berkah',
            'outlet_code' => 'KHB-01',
            'phone_wa'    => '081234567890',
            'city'        => 'Kudus',
            'status'      => 'ACTIVE',
        ]);

        $this->ownerA = User::factory()->create([
            'name'      => 'Owner Berkah',
            'email'     => 'owner.berkah@nayara.test',
            'role'      => UserRole::OWNER,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminA = User::factory()->create([
            'name'      => 'Admin Berkah',
            'email'     => 'admin.berkah@nayara.test',
            'role'      => UserRole::ADMIN,
            'outlet_id' => $this->outletA->id,
        ]);

        // Outlet B (Tenant 2)
        $this->outletB = Outlet::create([
            'outlet_name' => 'Konveksi Hijab Sejahtera',
            'outlet_code' => 'KHS-02',
            'phone_wa'    => '089876543210',
            'city'        => 'Pekalongan',
            'status'      => 'ACTIVE',
        ]);

        $this->ownerB = User::factory()->create([
            'name'      => 'Owner Sejahtera',
            'email'     => 'owner.sejahtera@nayara.test',
            'role'      => UserRole::OWNER,
            'outlet_id' => $this->outletB->id,
        ]);
    }

    /**
     * Test 1 & 2: Buat produk baru melalui Manajemen Produk (Admin) dan pastikan muncul di Etalase Owner.
     */
    public function test_1_and_2_create_product_in_product_management_appears_in_owner_showcase(): void
    {
        // 1. Admin creates product in Manajemen Produk
        $this->actingAs($this->adminA);
        $resStore = $this->post(route('admin.products.store'), [
            'product_name' => 'Hijab Segi Empat Voal Miracle',
            'code'         => 'VM',
            'description'  => 'Hijab voal miracle lembut kualitas ekspor.',
            'active'       => 1,
            'public_price' => 75000,
        ]);

        $resStore->assertSessionHasNoErrors();
        $product = Product::where('product_name', 'Hijab Segi Empat Voal Miracle')->firstOrFail();
        $this->assertEquals($this->outletA->id, $product->outlet_id);
        $this->assertFalse((bool) $product->is_published);

        // 2. Owner checks Etalase Owner (/owner/publications)
        $this->actingAs($this->ownerA);
        $resShowcase = $this->get(route('owner.publications.index'));
        $resShowcase->assertStatus(200);
        $resShowcase->assertSee('Hijab Segi Empat Voal Miracle');
        $resShowcase->assertSee('Internal / Draft');
        $resShowcase->assertSee('Rp 75.000');
    }

    /**
     * Test 3: Ubah harga produk tanpa varian dan pastikan Etalase mengikuti harga terbaru.
     */
    public function test_3_updating_product_price_without_variants_updates_showcase(): void
    {
        $product = Product::create([
            'outlet_id'    => $this->outletA->id,
            'name'         => 'Hijab Rawis Polos',
            'product_code' => 'HRP-01',
            'public_price' => 45000,
            'is_published' => false,
        ]);

        // Price initially Rp 45.000
        $this->assertEquals('Rp 45.000', $product->getPriceSummary()['formatted']);

        // Update via Admin Product Management
        $this->actingAs($this->adminA);
        $resUpdate = $this->put(route('admin.products.update', $product), [
            'product_name' => 'Hijab Rawis Polos',
            'description'  => 'Deskripsi baru',
            'price'        => 55000,
            'active'       => 1,
        ]);
        $resUpdate->assertSessionHasNoErrors();

        // Check on Owner Publications
        $this->actingAs($this->ownerA);
        $resOwner = $this->get(route('owner.publications.index'));
        $resOwner->assertStatus(200);
        $resOwner->assertSee('Hijab Rawis Polos');
        $resOwner->assertSee('Rp 55.000');
        $resOwner->assertDontSee('Rp 45.000');
    }

    /**
     * Test 4: Ubah harga salah satu varian dan pastikan harga varian dan rentang harga ikut berubah.
     */
    public function test_4_variant_price_changes_reflect_in_price_summary_and_owner_showcase(): void
    {
        $product = Product::create([
            'outlet_id'    => $this->outletA->id,
            'name'         => 'Pashmina Silk Premium',
            'product_code' => 'PSP-01',
            'is_published' => false,
        ]);

        // Add 2 variants with same price (Rp 80.000)
        $var1 = ProductVariant::create([
            'outlet_id'          => $this->outletA->id,
            'product_id'         => $product->id,
            'variant_name'       => 'Champagne',
            'sku'                => 'PSP-CHM-01',
            'base_selling_price' => 80000,
            'requires_printing'  => false,
            'active'             => true,
        ]);

        $var2 = ProductVariant::create([
            'outlet_id'          => $this->outletA->id,
            'product_id'         => $product->id,
            'variant_name'       => 'Rose Gold',
            'sku'                => 'PSP-RSG-02',
            'base_selling_price' => 80000,
            'requires_printing'  => false,
            'active'             => true,
        ]);

        // Since both variants are Rp 80.000, single price is shown
        $summary = $product->fresh()->getPriceSummary();
        $this->assertTrue($summary['has_price']);
        $this->assertFalse($summary['is_range']);
        $this->assertEquals('Rp 80.000', $summary['formatted']);

        // Admin updates Var2 price to Rp 95.000
        $this->actingAs($this->adminA);
        $resVarUpdate = $this->put(route('admin.variants.update', $var2), [
            'variant_name'       => 'Rose Gold',
            'base_selling_price' => 95000,
            'requires_printing'  => false,
            'active'             => 1,
        ]);
        $resVarUpdate->assertSessionHasNoErrors();

        // Now variants have different prices (80.000 and 95.000) -> range shown
        $freshSummary = $product->fresh()->getPriceSummary();
        $this->assertTrue($freshSummary['has_price']);
        $this->assertTrue($freshSummary['is_range']);
        $this->assertEquals('Rp 80.000 - Rp 95.000', $freshSummary['formatted']);

        // Check on Owner publications
        $this->actingAs($this->ownerA);
        $resOwner = $this->get(route('owner.publications.index'));
        $resOwner->assertStatus(200);
        $resOwner->assertSee('Rp 80.000 - Rp 95.000');
    }

    /**
     * Test 5: Terbitkan produk dan pastikan muncul pada website publik.
     */
    public function test_5_owner_can_publish_product_and_it_appears_on_storefront(): void
    {
        $product = Product::create([
            'outlet_id'       => $this->outletA->id,
            'name'            => 'Hijab Instan Jersey Bergo Sport',
            'product_code'    => 'HIJ-BRG-01',
            'public_category' => 'Hijab Instan',
            'public_price'    => 65000,
            'is_published'    => false,
        ]);

        $this->actingAs($this->ownerA);

        // Publish product
        $resPublish = $this->post(route('owner.publications.publish', $product));
        $resPublish->assertSessionHas('success');
        $this->assertTrue((bool) $product->fresh()->is_published);

        // Check on public storefront
        $resPublic = $this->get(route('storefront.index'));
        $resPublic->assertStatus(200);
        $resPublic->assertSee('Hijab Instan Jersey Bergo Sport');
        $resPublic->assertSee('Rp 65.000');

        // Check public detail page
        $resDetail = $this->get(route('storefront.product', $product));
        $resDetail->assertStatus(200);
        $resDetail->assertSee('Hijab Instan Jersey Bergo Sport');
        $resDetail->assertSee('Rp 65.000');
    }

    /**
     * Test 6: Tarik publikasi dan pastikan tidak lagi muncul pada website publik.
     */
    public function test_6_owner_can_unpublish_product_and_it_disappears_from_storefront(): void
    {
        $product = Product::create([
            'outlet_id'       => $this->outletA->id,
            'name'            => 'Hijab Voal Motif Edisi Terbatas',
            'product_code'    => 'HVM-LTD-01',
            'public_category' => 'Hijab Motif',
            'public_price'    => 120000,
            'is_published'    => true,
        ]);

        // Initially visible on storefront
        $this->get(route('storefront.index'))->assertSee('Hijab Voal Motif Edisi Terbatas');

        // Owner unpublishes
        $this->actingAs($this->ownerA);
        $resUnpublish = $this->post(route('owner.publications.unpublish', $product));
        $resUnpublish->assertSessionHas('success');
        $this->assertFalse((bool) $product->fresh()->is_published);

        // Storefront catalog no longer displays it
        $this->get(route('storefront.index'))->assertDontSee('Hijab Voal Motif Edisi Terbatas');

        // Public visitor cannot access unpublished product (404)
        auth()->logout();
        $this->get(route('storefront.product', $product))->assertStatus(404);

        // But Owner of that product can preview it without 404
        $this->actingAs($this->ownerA);
        $resOwnerPreview = $this->get(route('storefront.product', $product));
        $resOwnerPreview->assertStatus(200);
        $resOwnerPreview->assertSee('Mode Pratinjau Owner');
    }

    /**
     * Test 7: Kelola Varian membuka modul produk Owner yang benar tanpa link 403.
     */
    public function test_7_variant_management_in_owner_view_opens_drawer_without_forbidden_route(): void
    {
        $product = Product::create([
            'outlet_id'    => $this->outletA->id,
            'name'         => 'Hijab Paris Premium',
            'product_code' => 'HPP-01',
            'is_published' => false,
        ]);

        ProductVariant::create([
            'outlet_id'          => $this->outletA->id,
            'product_id'         => $product->id,
            'variant_name'       => 'Hitam Pekat',
            'sku'                => 'HPP-BLK-01',
            'base_selling_price' => 50000,
            'requires_printing'  => false,
            'active'             => true,
        ]);

        $this->actingAs($this->ownerA);
        $response = $this->get(route('owner.publications.index'));
        $response->assertStatus(200);

        // Drawer handles variants cleanly
        $response->assertSee('openDrawer');
        $response->assertSee('Kelola Varian (1)');
        $response->assertSee('Hitam Pekat');
        $response->assertSee('HPP-BLK-01');

        // Must NOT link to admin.products.index which triggers 403
        $response->assertDontSee(route('admin.products.index'));
        $response->assertDontSee('/admin/products');
    }

    /**
     * Test 8: Uji akses produk tenant lain dan pastikan tetap ditolak (Multi-Tenant Isolation).
     */
    public function test_8_cross_tenant_access_is_strictly_forbidden(): void
    {
        $productA = Product::create([
            'outlet_id'    => $this->outletA->id,
            'name'         => 'Produk Rahasia Outlet A',
            'product_code' => 'PRA-01',
            'public_price' => 100000,
            'is_published' => false,
        ]);

        // Owner B tries to publish Product A -> 403 Forbidden
        $this->actingAs($this->ownerB);
        $resPublish = $this->post(route('owner.publications.publish', $productA));
        $resPublish->assertStatus(403);

        // Owner B tries to unpublish Product A -> 403 Forbidden
        $resUnpublish = $this->post(route('owner.publications.unpublish', $productA));
        $resUnpublish->assertStatus(403);

        // Owner B tries to update showcase of Product A -> 403 Forbidden
        $resUpdate = $this->put(route('owner.publications.update-showcase', $productA), [
            'public_price' => 50000,
        ]);
        $resUpdate->assertStatus(403);

        // Owner B cannot preview Product A when it is draft -> 404 Not Found
        $resPreview = $this->get(route('storefront.product', $productA));
        $resPreview->assertStatus(404);
    }

    /**
     * Test 9: Seluruh navigasi produk Owner aman dan bebas dari HTTP 403.
     */
    public function test_9_all_owner_product_navigation_routes_are_free_of_403_errors(): void
    {
        $product = Product::create([
            'outlet_id'       => $this->outletA->id,
            'name'            => 'Hijab Daily Bella Square',
            'product_code'    => 'HBS-01',
            'public_category' => 'Hijab Segi Empat',
            'public_price'    => 35000,
            'is_published'    => true,
        ]);

        $this->actingAs($this->ownerA);

        // Etalase Owner index loads with 200
        $resIndex = $this->get(route('owner.publications.index'));
        $resIndex->assertStatus(200);

        // Assert all rendered links in the view do not point to admin.products
        $resIndex->assertDontSee('/admin/products');

        // Detail storefront page loads with 200
        $resDetail = $this->get(route('storefront.product', $product));
        $resDetail->assertStatus(200);

        // Subscriptions & slot management loads with 200
        $resSub = $this->get(route('owner.subscriptions.index'));
        $resSub->assertStatus(200);
    }
}

