<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\Platform\OutletListingSlot;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\TenantSubscription;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Platform\PlatformBillingService;
use App\Services\Platform\TenantEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlatformBillingAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected User $owner;
    protected User $superAdmin;
    protected PlatformBillingService $billingService;
    protected TenantEntitlementService $entitlementService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Solo',
            'outlet_code' => 'OUT-SOLO-01',
            'phone_wa' => '081298765432',
            'status' => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'name' => 'Owner Solo',
            'email' => 'owner.solo@nayara.test',
            'role' => UserRole::OWNER,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Platform',
            'email' => 'superadmin@nayara.test',
            'role' => UserRole::SUPER_ADMIN,
            'outlet_id' => null,
        ]);

        $this->billingService = app(PlatformBillingService::class);
        $this->entitlementService = app(TenantEntitlementService::class);
    }

    public function test_premium_invoice_issuance_uses_final_approved_pricing(): void
    {
        // 1 month = 59,000; 3 months = 169,000; 6 months = 319,000; 12 months = 569,000
        $inv1 = $this->billingService->createPremiumInvoice($this->outlet->id, 1, $this->owner);
        $this->assertEquals(59000, (int) $inv1->amount);

        $inv3 = $this->billingService->createPremiumInvoice($this->outlet->id, 3, $this->owner);
        $this->assertEquals(169000, (int) $inv3->amount);

        $inv6 = $this->billingService->createPremiumInvoice($this->outlet->id, 6, $this->owner);
        $this->assertEquals(319000, (int) $inv6->amount);

        $inv12 = $this->billingService->createPremiumInvoice($this->outlet->id, 12, $this->owner);
        $this->assertEquals(569000, (int) $inv12->amount);
    }

    public function test_cannot_purchase_analytics_addon_if_premium_already_active(): void
    {
        TenantSubscription::create([
            'outlet_id' => $this->outlet->id,
            'type' => TenantSubscription::TYPE_PREMIUM,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => TenantSubscription::STATUS_ACTIVE,
        ]);

        $this->expectException(ValidationException::class);
        $this->billingService->createAnalyticsAddonInvoice($this->outlet->id, 1, $this->owner);
    }

    public function test_payment_proof_submission_and_super_admin_verification_activates_premium(): void
    {
        $invoice = $this->billingService->createPremiumInvoice($this->outlet->id, 1, $this->owner);
        $this->assertEquals(PlatformInvoice::STATUS_UNPAID, $invoice->status);
        $this->assertFalse($this->entitlementService->hasActivePremium($this->outlet->id));

        // Submit proof
        $this->billingService->submitPaymentProof($invoice, 'proofs/transfer_sample.jpg');
        $this->assertEquals(PlatformInvoice::STATUS_PAYMENT_SUBMITTED, $invoice->fresh()->status);
        $this->assertFalse($this->entitlementService->hasActivePremium($this->outlet->id)); // Proof alone does not activate!

        // Super Admin verifies
        $this->billingService->verifyPayment($invoice->fresh(), $this->superAdmin);

        $this->assertEquals(PlatformInvoice::STATUS_VERIFIED, $invoice->fresh()->status);
        $this->assertTrue($this->entitlementService->hasActivePremium($this->outlet->id));
        $this->assertEquals(15, $this->entitlementService->getListingCapacity($this->outlet->id)); // 5 free + 10 premium
    }

    public function test_repeated_payment_verification_is_idempotent(): void
    {
        $invoice = $this->billingService->createPremiumInvoice($this->outlet->id, 1, $this->owner);
        $this->billingService->submitPaymentProof($invoice, 'proofs/transfer.jpg');

        $this->billingService->verifyPayment($invoice->fresh(), $this->superAdmin);
        $firstCount = TenantSubscription::where('outlet_id', $this->outlet->id)->count();

        // Repeated verification attempt
        $this->billingService->verifyPayment($invoice->fresh(), $this->superAdmin);
        $secondCount = TenantSubscription::where('outlet_id', $this->outlet->id)->count();

        $this->assertEquals(1, $firstCount);
        $this->assertEquals(1, $secondCount);
    }

    public function test_premium_renewal_extends_from_existing_expiry_date(): void
    {
        $initialExpiry = now()->addDays(15);
        TenantSubscription::create([
            'outlet_id' => $this->outlet->id,
            'type' => TenantSubscription::TYPE_PREMIUM,
            'starts_at' => now()->subDays(15),
            'expires_at' => $initialExpiry,
            'status' => TenantSubscription::STATUS_ACTIVE,
        ]);

        $renewalInvoice = $this->billingService->createPremiumInvoice($this->outlet->id, 1, $this->owner);
        $this->billingService->submitPaymentProof($renewalInvoice, 'proofs/renewal.jpg');
        $this->billingService->verifyPayment($renewalInvoice->fresh(), $this->superAdmin);

        $activeSub = TenantSubscription::where('outlet_id', $this->outlet->id)->where('status', TenantSubscription::STATUS_ACTIVE)->first();
        // Should extend by 1 month from previous expiry, not from now()
        $this->assertTrue($activeSub->expires_at->diffInDays($initialExpiry->copy()->addMonth()) <= 1);
    }

    public function test_permanent_slots_purchased_increment_quota_correctly(): void
    {
        $invSlots = $this->billingService->createListingSlotsInvoice($this->outlet->id, 10, $this->owner);
        $this->assertEquals(89000, (int) $invSlots->amount);

        $this->billingService->submitPaymentProof($invSlots, 'proofs/slots.jpg');
        $this->billingService->verifyPayment($invSlots->fresh(), $this->superAdmin);

        $purchased = OutletListingSlot::where('outlet_id', $this->outlet->id)->value('purchased_slots');
        $this->assertEquals(10, $purchased);
        $this->assertEquals(15, $this->entitlementService->getListingCapacity($this->outlet->id)); // 5 free + 10 permanent
    }

    public function test_prorated_upgrade_from_addon_to_premium_preserves_end_date(): void
    {
        $startDate = now()->subDays(10);
        $expiresDate = now()->addDays(20); // 30 days total, 20 days remaining

        $addonSub = TenantSubscription::create([
            'outlet_id' => $this->outlet->id,
            'type' => TenantSubscription::TYPE_ANALYTICS_ADDON,
            'starts_at' => $startDate,
            'expires_at' => $expiresDate,
            'status' => TenantSubscription::STATUS_ACTIVE,
        ]);

        $quote = $this->billingService->calculateUpgradeQuote($this->outlet->id);
        $this->assertGreaterThan(0, $quote['prorated_amount']);
        $this->assertLessThanOrEqual(14000, $quote['prorated_amount']);

        $upgradeInvoice = $this->billingService->createUpgradeInvoice($this->outlet->id, $this->owner);
        $this->assertEquals(PlatformInvoice::SERVICE_UPGRADE_PREMIUM, $upgradeInvoice->service_type);

        $this->billingService->submitPaymentProof($upgradeInvoice, 'proofs/upgrade.jpg');
        $this->billingService->verifyPayment($upgradeInvoice->fresh(), $this->superAdmin);

        // Old subscription marked UPGRADED
        $this->assertEquals(TenantSubscription::STATUS_UPGRADED, $addonSub->fresh()->status);

        // New Premium subscription created with preserved end date
        $newPremium = TenantSubscription::where('outlet_id', $this->outlet->id)
            ->where('type', TenantSubscription::TYPE_PREMIUM)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->first();

        $this->assertNotNull($newPremium);
        $this->assertEquals($expiresDate->toDateTimeString(), $newPremium->expires_at->toDateTimeString());
    }

    public function test_over_quota_detected_when_premium_expires_without_deleting_products(): void
    {
        // Tenant has published 12 products while on Premium (capacity 15)
        for ($i = 1; $i <= 12; $i++) {
            Product::create([
                'outlet_id' => $this->outlet->id,
                'name' => "Product {$i}",
                'product_code' => "PRD-{$i}",
                'is_published' => true,
            ]);
        }

        // Sub expired
        TenantSubscription::create([
            'outlet_id' => $this->outlet->id,
            'type' => TenantSubscription::TYPE_PREMIUM,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subDay(), // Expired
            'status' => TenantSubscription::STATUS_EXPIRED,
        ]);

        $this->assertEquals(5, $this->entitlementService->getListingCapacity($this->outlet->id));
        $this->assertEquals(12, $this->entitlementService->getPublishedListingCount($this->outlet->id));

        // Section L: Must detect over quota
        $this->assertTrue($this->entitlementService->isOverListingQuota($this->outlet->id));

        // Section L: All 12 product records are preserved (NO silent automated deletion)
        $this->assertEquals(12, Product::where('outlet_id', $this->outlet->id)->count());
    }

    public function test_owner_can_submit_payment_proof_via_http_endpoint(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $invoice = $this->billingService->createPremiumInvoice($this->outlet->id, 3, $this->owner);

        $file = \Illuminate\Http\UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');

        $response = $this->actingAs($this->owner)
            ->post(route('owner.subscriptions.submit-proof', $invoice->id), [
                'proof_file' => $file,
                'payment_method' => 'BANK_TRANSFER',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $invoice->fresh();
        $this->assertEquals(PlatformInvoice::STATUS_PAYMENT_SUBMITTED, $fresh->status);
        $this->assertNotNull($fresh->payment_proof_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($fresh->payment_proof_path);
    }
}
