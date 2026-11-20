<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_admin_can_create_product(): void
    {
        $payload = [
            'product_name' => 'Hijab Pashmina Silk',
            'description'  => 'Bahan pashmina berkilau premium',
            'active'       => 1,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), $payload);

        $product = Product::where('product_name', 'Hijab Pashmina Silk')->first();
        $this->assertNotNull($product);

        $response->assertRedirect(route('admin.products.show', $product));
        $this->assertDatabaseHas('products', ['product_name' => 'Hijab Pashmina Silk', 'active' => 1]);
    }

    public function test_admin_can_edit_product(): void
    {
        $product = Product::create(['product_name' => 'Hijab Segi Empat Raw', 'active' => true]);

        $payload = [
            'product_name' => 'Hijab Segi Empat Raw Edit',
            'description'  => 'Deskripsi terupdate',
            'active'       => 1,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.products.update', $product), $payload);

        $response->assertRedirect(route('admin.products.show', $product));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'product_name' => 'Hijab Segi Empat Raw Edit']);
    }

    public function test_admin_can_toggle_active_status_soft_deactivation(): void
    {
        $product = Product::create(['product_name' => 'Hijab Test Toggle', 'active' => true]);

        $response = $this->actingAs($this->admin)->post(route('admin.products.toggle-active', $product));

        $product->refresh();
        $this->assertFalse((bool) $product->active);

        // Toggle back to active
        $response = $this->actingAs($this->admin)->post(route('admin.products.toggle-active', $product));

        $product->refresh();
        $this->assertTrue((bool) $product->active);

        // Verify product record is NEVER physically deleted from database
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_destroy_route_is_not_available(): void
    {
        $product = Product::create(['product_name' => 'Product Safe', 'active' => true]);

        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");

        $response->assertStatus(405); // Method Not Allowed since destroy route does not exist
    }
}
