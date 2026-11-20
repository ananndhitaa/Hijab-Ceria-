<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_admin_can_view_customer_list(): void
    {
        Customer::create(['customer_name' => 'Toko Aisyah']);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Toko Aisyah');
    }

    public function test_admin_can_create_customer_valid(): void
    {
        $payload = [
            'customer_name' => 'Reseller Nayyara',
            'phone'         => '08123456789',
            'address'       => 'Jl. Merdeka No. 10 Bandung',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.customers.store'), $payload);

        $response->assertRedirect(route('admin.customers.index'));
        $this->assertDatabaseHas('customers', ['customer_name' => 'Reseller Nayyara']);
    }

    public function test_customer_creation_fails_when_name_missing(): void
    {
        $payload = [
            'customer_name' => '',
            'phone'         => '08123456789',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.customers.store'), $payload);

        $response->assertSessionHasErrors('customer_name');
        $this->assertDatabaseMissing('customers', ['phone' => '08123456789']);
    }

    public function test_admin_can_update_customer(): void
    {
        $customer = Customer::create(['customer_name' => 'Customer Lama']);

        $payload = [
            'customer_name' => 'Customer Baru Edit',
            'phone'         => '08999999999',
            'address'       => 'Alamat Baru',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.customers.update', $customer), $payload);

        $response->assertRedirect(route('admin.customers.index'));
        $this->assertDatabaseHas('customers', [
            'id'            => $customer->id,
            'customer_name' => 'Customer Baru Edit',
        ]);
    }

    public function test_customer_destroy_route_is_not_available(): void
    {
        $customer = Customer::create(['customer_name' => 'Customer Safe']);

        $response = $this->actingAs($this->admin)->delete("/admin/customers/{$customer->id}");

        $response->assertStatus(405); // Method Not Allowed since destroy route does not exist
    }

    public function test_non_customer_user_cannot_be_linked_to_customer(): void
    {
        $gudangUser = User::factory()->create(['role' => UserRole::GUDANG]);

        $payload = [
            'customer_name' => 'Toko Palsu',
            'user_id'       => $gudangUser->id, // Non-CUSTOMER role
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.customers.store'), $payload);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('customers', ['customer_name' => 'Toko Palsu']);
    }

    public function test_customer_user_cannot_be_linked_twice(): void
    {
        $customerUser = User::factory()->create(['role' => UserRole::CUSTOMER]);

        Customer::create([
            'customer_name' => 'Customer Profil 1',
            'user_id'       => $customerUser->id,
        ]);

        $payload = [
            'customer_name' => 'Customer Profil 2 Duplikat',
            'user_id'       => $customerUser->id, // Attempting to link same user_id twice
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.customers.store'), $payload);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('customers', ['customer_name' => 'Customer Profil 2 Duplikat']);
    }
}
