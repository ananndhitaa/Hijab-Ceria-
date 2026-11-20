<?php

namespace Tests\Feature\Owner;

use App\Enums\UserRole;
use App\Models\Platform\PlatformServiceRequest;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerServiceRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::factory()->create();

        $this->owner = User::factory()->create([
            'role' => UserRole::OWNER,
            'outlet_id' => $this->outlet->id,
        ]);
    }

    public function test_owner_can_view_services_index_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.services.index'));

        $response->assertOk();
        $response->assertSee('Layanan Implementasi, Setup & Pelatihan');
        $response->assertSee('EXECUTIVE MONITORING');
        $response->assertSee('POLICY PENDING · SECTION 7');
        $response->assertSee('Permintaan Layanan');
        $response->assertSee('Belum Ada Permintaan Layanan');
    }

    public function test_owner_can_view_create_page(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.services.create'));

        $response->assertOk();
        $response->assertSee('Ajukan Permintaan Layanan');
        $response->assertSee('Tipe Layanan yang Dibutuhkan');
    }

    public function test_owner_can_submit_service_request(): void
    {
        $payload = [
            'service_type' => PlatformServiceRequest::TYPE_SETUP_ONBOARDING,
            'scope_details' => 'Membutuhkan setup master data bahan baku kain voal dan pelatihan 3 orang admin.',
            'preferred_date' => now()->addDays(5)->toDateString(),
        ];

        $response = $this->actingAs($this->owner)->post(route('owner.services.store'), $payload);

        $response->assertRedirect(route('owner.services.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('platform_service_requests', [
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->owner->id,
            'service_type' => PlatformServiceRequest::TYPE_SETUP_ONBOARDING,
            'status' => PlatformServiceRequest::STATUS_SUBMITTED,
        ]);

        // Verify it is displayed in the table
        $indexResponse = $this->actingAs($this->owner)->get(route('owner.services.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Setup Awal & Migrasi Master Data');
        $indexResponse->assertSee('Diajukan');
    }

    public function test_validation_fails_for_invalid_service_type(): void
    {
        $payload = [
            'service_type' => 'INVALID_TYPE',
            'scope_details' => 'Test details',
        ];

        $response = $this->actingAs($this->owner)->post(route('owner.services.store'), $payload);
        $response->assertSessionHasErrors(['service_type']);
    }
}
