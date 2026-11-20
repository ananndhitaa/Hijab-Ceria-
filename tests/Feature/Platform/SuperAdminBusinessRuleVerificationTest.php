<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Master\Product;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\AdCampaignRevision;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformNotification;
use App\Models\Platform\PlatformRefund;
use App\Models\Platform\TenantSubscription;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Platform\AdCapacityAndScheduleService;
use App\Services\Platform\AdRefundService;
use App\Services\Platform\PlatformBillingService;
use App\Services\Platform\TenantEntitlementService;
use App\Services\Platform\ProductPublicationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SuperAdminBusinessRuleVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Outlet $outletA;
    protected Outlet $outletB;
    protected User $ownerA;
    protected User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => false]);

        $this->superAdmin = User::create([
            'name'      => 'Super Admin Test',
            'email'     => 'superadmin@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::SUPER_ADMIN->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => null,
        ]);

        $this->outletA = Outlet::create([
            'outlet_name' => 'Konveksi Hijab A',
            'outlet_code' => 'OUT-A',
            'status'      => 'ACTIVE',
        ]);

        $this->outletB = Outlet::create([
            'outlet_name' => 'Konveksi Hijab B',
            'outlet_code' => 'OUT-B',
            'status'      => 'ACTIVE',
        ]);

        $this->ownerA = User::create([
            'name'      => 'Owner A',
            'email'     => 'owner.a@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->ownerB = User::create([
            'name'      => 'Owner B',
            'email'     => 'owner.b@nayara.test',
            'password'  => Hash::make('Secret123!'),
            'role'      => UserRole::OWNER->value,
            'status'    => UserStatus::ACTIVE->value,
            'outlet_id' => $this->outletB->id,
        ]);
    }

    /**
     * Requirement 1: Ad duration beyond 30 days is accepted and calculates schedule correctly.
     */
    public function test_ad_duration_beyond_30_days_is_accepted_and_calculates_schedule_correctly(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Kampanye Akbar Ramadan 60 Hari',
            'total_budget'  => 6000000,
            'duration_days' => 60,
            'status'        => AdCampaign::STATUS_APPROVED,
        ]);

        $service = app(AdCapacityAndScheduleService::class);
        $startsAt = Carbon::now()->addDays(2)->setTime(0, 0, 0);

        $invoice = $service->reserveAndCreateInvoice(
            campaign: $campaign,
            totalBudget: 6000000,
            durationDays: 60,
            startsAt: $startsAt,
            user: $this->ownerA
        );

        $campaign->refresh();
        $this->assertEquals(60, $campaign->duration_days);
        $this->assertEquals(100000.00, (float) $campaign->effective_bid_per_day);
        $this->assertEquals($startsAt->toDateTimeString(), $campaign->starts_at->toDateTimeString());
        $this->assertEquals($startsAt->copy()->addDays(60)->toDateTimeString(), $campaign->ends_at->toDateTimeString());
        $this->assertEquals(AdCampaign::STATUS_PAYMENT_PENDING, $campaign->status);
        $this->assertNotNull($invoice);
        $this->assertEquals(6000000, (float) $invoice->amount);
    }

    /**
     * Requirement 2: Ad invalid duration (zero or negative) is rejected.
     */
    public function test_ad_invalid_duration_zero_or_negative_is_rejected(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Kampanye Invalid',
            'total_budget'  => 100000,
            'duration_days' => 0,
            'status'        => AdCampaign::STATUS_APPROVED,
        ]);

        $service = app(AdCapacityAndScheduleService::class);
        $startsAt = Carbon::now()->addDays(1);

        $this->expectException(ValidationException::class);
        $service->reserveAndCreateInvoice(
            campaign: $campaign,
            totalBudget: 100000,
            durationDays: 0,
            startsAt: $startsAt,
            user: $this->ownerA
        );
    }

    /**
     * Requirement 3: 12-hour review deadlines for moderation decisions and resubmissions.
     * Late review must NOT result in automatic approval.
     */
    public function test_twelve_hour_review_deadlines_and_no_automatic_approval(): void
    {
        $submittedAt = Carbon::now()->subHours(14); // Overdue beyond 12 hours
        $campaign = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Kampanye Butuh Review Cepat',
            'status'        => AdCampaign::STATUS_PENDING_REVIEW,
            'created_at'    => $submittedAt,
            'updated_at'    => $submittedAt,
        ]);

        // Review is late (> 12 hours), but status MUST remain PENDING_REVIEW (no automatic approval)
        $this->assertEquals(AdCampaign::STATUS_PENDING_REVIEW, $campaign->status);

        // Super Admin reviews within platform controller
        $response = $this->actingAs($this->superAdmin)->post(route('platform.ads.review', $campaign->id), [
            'decision' => 'APPROVE',
            'notes'    => 'Materi terverifikasi sesuai standar NAYARA.',
        ]);

        $response->assertRedirect(route('platform.ads.show', $campaign->id));
        $campaign->refresh();
        $this->assertEquals(AdCampaign::STATUS_APPROVED, $campaign->status);
        $this->assertEquals($this->superAdmin->id, $campaign->reviewed_by);
    }

    /**
     * Requirement 4: 2-hour invoice expiry and additional 24-hour verification hold.
     */
    public function test_two_hour_invoice_expiry_and_additional_24_hour_hold_clocks(): void
    {
        $service = app(AdCapacityAndScheduleService::class);

        // Case 1: Unpaid invoice older than 2 hours -> Released
        $campUnpaid = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Unpaid Expired Campaign',
            'status'        => AdCampaign::STATUS_PAYMENT_PENDING,
            'starts_at'     => now()->addDays(2),
            'ends_at'       => now()->addDays(5),
            'duration_days' => 3,
            'total_budget'  => 300000,
        ]);

        $invUnpaid = PlatformInvoice::create([
            'outlet_id'            => $this->outletA->id,
            'user_id'              => $this->ownerA->id,
            'service_type'         => PlatformInvoice::SERVICE_SHOP_AD,
            'invoice_no'           => 'INV-TEST-001',
            'amount'               => 300000,
            'status'               => PlatformInvoice::STATUS_UNPAID,
            'expires_at'           => now()->subMinutes(5), // 2h clock expired
        ]);

        $campUnpaid->update(['platform_invoice_id' => $invUnpaid->id]);

        // Case 2: Payment submitted within hold (< 24h past expiry) -> NOT released
        $campHold = AdCampaign::create([
            'outlet_id'     => $this->outletB->id,
            'created_by'    => $this->ownerB->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Submitted Payment on Hold',
            'status'        => AdCampaign::STATUS_PAYMENT_PENDING,
            'starts_at'     => now()->addDays(3),
            'ends_at'       => now()->addDays(6),
            'duration_days' => 3,
            'total_budget'  => 300000,
        ]);

        $invHold = PlatformInvoice::create([
            'outlet_id'            => $this->outletB->id,
            'user_id'              => $this->ownerB->id,
            'service_type'         => PlatformInvoice::SERVICE_SHOP_AD,
            'invoice_no'     => 'INV-TEST-002',
            'amount'               => 300000,
            'status'               => PlatformInvoice::STATUS_PAYMENT_SUBMITTED,
            'expires_at'           => now()->subHours(2), // Past 2h expiry, but within +24h hold
        ]);

        $campHold->update(['platform_invoice_id' => $invHold->id]);

        $released = $service->releaseExpiredReservations();

        $this->assertEquals(1, $released, 'Only unpaid campaign past 2h expiry must be released; submitted payment on hold must be protected');

        $campUnpaid->refresh();
        $this->assertEquals(AdCampaign::STATUS_APPROVED, $campUnpaid->status);
        $this->assertNull($campUnpaid->platform_invoice_id);

        $campHold->refresh();
        $this->assertEquals(AdCampaign::STATUS_PAYMENT_PENDING, $campHold->status);
    }

    /**
     * Requirement 5: Fractional-duration refund precision:
     * Refund = Verified paid amount * actual unfulfilled duration / purchased duration.
     * Use actual elapsed seconds, do not round lost duration to whole days.
     */
    public function test_fractional_duration_refund_precision(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Kampanye 3 Hari Gangguan 5.5 Jam',
            'status'        => AdCampaign::STATUS_ACTIVE,
            'duration_days' => 3, // 3 days = 259,200 seconds
            'total_budget'  => 300000,
            'starts_at'     => now()->subDays(1),
            'ends_at'       => now()->addDays(2),
        ]);

        $invoice = PlatformInvoice::create([
            'outlet_id'      => $this->outletA->id,
            'user_id'        => $this->ownerA->id,
            'service_type'   => PlatformInvoice::SERVICE_SHOP_AD,
            'invoice_no'     => 'INV-TEST-REFUND-001',
            'amount'         => 300000,
            'status'         => PlatformInvoice::STATUS_VERIFIED,
            'verified_at'    => now()->subDays(1),
            'expires_at'     => now()->addHours(2),
        ]);

        $campaign->update(['platform_invoice_id' => $invoice->id]);

        // Lost duration: exactly 5.5 hours = 5.5 * 3600 = 19,800 seconds
        // Purchased duration: 3 * 86,400 = 259,200 seconds
        // Ratio = 19,800 / 259,200 = 0.07638888...
        // Expected refund = round(300,000 * 0.07638888...) = Rp 22,917
        $lostSeconds = (int) (5.5 * 3600);
        $expectedAmount = round(300000 * ($lostSeconds / (3 * 86400)));
        $this->assertEquals(22917, $expectedAmount);

        $refundService = app(AdRefundService::class);
        $refund = $refundService->issueProportionalCompensation(
            campaign: $campaign,
            lostSeconds: $lostSeconds,
            incidentReason: 'Server downtime jaringan telekomunikasi',
            bankDetails: [
                'bank_name'      => 'BCA',
                'account_number' => '1234567890',
                'account_name'   => 'Owner Konveksi A',
            ],
            admin: $this->superAdmin
        );

        $this->assertEquals(22917, (float) $refund->amount);
        $this->assertEquals(PlatformRefund::TYPE_PROPORTIONAL, $refund->refund_type);
        $this->assertEquals(PlatformRefund::STATUS_APPROVED, $refund->status);
    }

    /**
     * Requirement 6: Refund idempotency and over-refund prevention.
     */
    public function test_refund_idempotency_and_over_refund_prevention(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Idempotency Test Campaign',
            'status'        => AdCampaign::STATUS_ACTIVE,
            'duration_days' => 1,
            'total_budget'  => 100000,
            'starts_at'     => now()->subHours(12),
            'ends_at'       => now()->addHours(12),
        ]);

        $invoice = PlatformInvoice::create([
            'outlet_id'      => $this->outletA->id,
            'user_id'        => $this->ownerA->id,
            'service_type'   => PlatformInvoice::SERVICE_SHOP_AD,
            'invoice_no'     => 'INV-TEST-IDEMP-001',
            'amount'         => 100000,
            'status'         => PlatformInvoice::STATUS_VERIFIED,
            'verified_at'    => now()->subHours(12),
            'expires_at'     => now()->addHours(2),
        ]);

        $campaign->update(['platform_invoice_id' => $invoice->id]);

        $refund = PlatformRefund::create([
            'outlet_id'                  => $this->outletA->id,
            'platform_invoice_id'        => $invoice->id,
            'ad_campaign_id'             => $campaign->id,
            'refund_type'                => PlatformRefund::TYPE_FULL_100_PERCENT,
            'amount'                     => 100000,
            'reason'                     => 'Pembatalan sebelum tayang',
            'status'                     => PlatformRefund::STATUS_APPROVED,
            'destination_bank_name'      => 'Mandiri',
            'destination_account_number' => '987654321',
            'destination_account_name'   => 'Owner A',
        ]);

        $refundService = app(AdRefundService::class);

        // First completion
        $completed1 = $refundService->completeRefund($refund, 'proofs/refund1.jpg', $this->superAdmin);
        $this->assertEquals(PlatformRefund::STATUS_COMPLETED, $completed1->status);

        // Second completion retry (idempotent: does not error, does not double-settle)
        $completed2 = $refundService->completeRefund($refund, 'proofs/refund2.jpg', $this->superAdmin);
        $this->assertEquals(PlatformRefund::STATUS_COMPLETED, $completed2->status);

        // Further compensation request on this fully-refunded invoice must be rejected
        $this->expectException(ValidationException::class);
        $refundService->issueProportionalCompensation(
            campaign: $campaign,
            lostSeconds: 3600,
            incidentReason: 'Incident retry',
            bankDetails: [
                'bank_name'      => 'Mandiri',
                'account_number' => '987654321',
                'account_name'   => 'Owner A',
            ],
            admin: $this->superAdmin
        );
    }

    /**
     * Requirement 7: Broadcast targeting and deduplication.
     * Duplicate identical message within 5 minutes is prevented with feedback.
     * Distinct messages within the same window are dispatched without blockage.
     */
    public function test_broadcast_targeting_and_deduplication(): void
    {
        // 1. Target single outlet
        $responseSingle = $this->actingAs($this->superAdmin)->post(route('platform.notifications.broadcast'), [
            'target'     => 'specific',
            'outlet_id'  => $this->outletA->id,
            'type'       => 'POLICY_UPDATE',
            'title'      => 'Pembaruan Ketentuan Outlet A',
            'message'    => 'Instruksi khusus untuk konveksi A.',
            'action_url' => null,
        ]);

        $responseSingle->assertRedirect(route('platform.notifications.index'));
        $this->assertDatabaseHas('platform_notifications', [
            'outlet_id' => $this->outletA->id,
            'title'     => 'Pembaruan Ketentuan Outlet A',
        ]);
        $this->assertDatabaseMissing('platform_notifications', [
            'outlet_id' => $this->outletB->id,
            'title'     => 'Pembaruan Ketentuan Outlet A',
        ]);

        // 2. Duplicate submission within 5 minutes is blocked
        $dupResponse = $this->actingAs($this->superAdmin)->post(route('platform.notifications.broadcast'), [
            'target'     => 'specific',
            'outlet_id'  => $this->outletA->id,
            'type'       => 'POLICY_UPDATE',
            'title'      => 'Pembaruan Ketentuan Outlet A',
            'message'    => 'Instruksi khusus untuk konveksi A.',
            'action_url' => null,
        ]);

        $dupResponse->assertSessionHas('info');
        $this->assertEquals(1, PlatformNotification::where('title', 'Pembaruan Ketentuan Outlet A')->count());

        // 3. Distinct message with different title/body is immediately allowed
        $distinctResponse = $this->actingAs($this->superAdmin)->post(route('platform.notifications.broadcast'), [
            'target'     => 'specific',
            'outlet_id'  => $this->outletA->id,
            'type'       => 'ANNOUNCEMENT',
            'title'      => 'Pesan Berbeda Outlet A',
            'message'    => 'Pesan kedua dengan isi materi berbeda.',
            'action_url' => null,
        ]);

        $distinctResponse->assertSessionHas('success');
        $this->assertDatabaseHas('platform_notifications', [
            'outlet_id' => $this->outletA->id,
            'title'     => 'Pesan Berbeda Outlet A',
        ]);
    }

    /**
     * Requirement 8: Ad duration beyond 365 days is accepted and calculates schedule accurately
     * without arbitrary business limits.
     */
    public function test_ad_duration_beyond_365_days_is_accepted_and_calculates_schedule_accurately(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Kampanye Multitahun 730 Hari',
            'total_budget'  => 7300000,
            'duration_days' => 730, // 2 full years
            'status'        => AdCampaign::STATUS_APPROVED,
        ]);

        $service = app(AdCapacityAndScheduleService::class);
        $startsAt = Carbon::now()->addDays(5)->setTime(0, 0, 0);

        $invoice = $service->reserveAndCreateInvoice(
            campaign: $campaign,
            totalBudget: 7300000,
            durationDays: 730,
            startsAt: $startsAt,
            user: $this->ownerA
        );

        $campaign->refresh();
        $this->assertEquals(730, $campaign->duration_days);
        $this->assertEquals(10000.00, (float) $campaign->effective_bid_per_day);
        $this->assertEquals($startsAt->toDateTimeString(), $campaign->starts_at->toDateTimeString());
        $this->assertEquals($startsAt->copy()->addDays(730)->toDateTimeString(), $campaign->ends_at->toDateTimeString());
        $this->assertEquals(AdCampaign::STATUS_PAYMENT_PENDING, $campaign->status);
        $this->assertNotNull($invoice);
        $this->assertEquals(7300000, (float) $invoice->amount);
    }

    /**
     * Requirement 9: Extreme ad duration causing timestamp/date overflow beyond technical limits is safely rejected.
     */
    public function test_ad_duration_overflowing_technical_date_limit_is_safely_rejected(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Kampanye Overflow Tanggal',
            'total_budget'  => 50000000,
            'duration_days' => 50000,
            'status'        => AdCampaign::STATUS_APPROVED,
        ]);

        $service = app(AdCapacityAndScheduleService::class);
        $startsAt = Carbon::now()->addDays(1);

        $this->expectException(ValidationException::class);
        $service->reserveAndCreateInvoice(
            campaign: $campaign,
            totalBudget: 50000000,
            durationDays: 50000, // Year exceeds 2099
            startsAt: $startsAt,
            user: $this->ownerA
        );
    }

    /**
     * Requirement 9b: HTTP endpoint validation accepts duration > 365 days
     * and rejects technical overflow with validation feedback.
     */
    public function test_owner_controller_booking_endpoint_accepts_duration_beyond_365_days(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Kampanye HTTP 500 Hari',
            'status'        => AdCampaign::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->ownerA)->post(route('owner.ads.book', $campaign->id), [
            'total_budget'  => 5000000,
            'duration_days' => 500, // Beyond 365 days
            'starts_at'     => Carbon::now()->addDays(2)->format('Y-m-d\TH:i'),
        ]);

        $campaign->refresh();
        $this->assertEquals(500, $campaign->duration_days);
        $this->assertEquals(AdCampaign::STATUS_PAYMENT_PENDING, $campaign->status);
        $this->assertNotNull($campaign->platform_invoice_id);
        $response->assertRedirect(route('owner.subscriptions.invoice', $campaign->platform_invoice_id));

        // Overflow case via HTTP
        $campaign2 = AdCampaign::create([
            'outlet_id'     => $this->outletA->id,
            'created_by'    => $this->ownerA->id,
            'ad_type'       => AdCampaign::TYPE_SHOP_AD,
            'title'         => 'Kampanye HTTP Overflow',
            'status'        => AdCampaign::STATUS_APPROVED,
        ]);

        $overflowResponse = $this->actingAs($this->ownerA)->post(route('owner.ads.book', $campaign2->id), [
            'total_budget'  => 5000000,
            'duration_days' => 50000, // Beyond technical year 2099 limit
            'starts_at'     => Carbon::now()->addDays(2)->format('Y-m-d\TH:i'),
        ]);

        $overflowResponse->assertSessionHasErrors(['duration_days']);
    }

    /**
     * Requirement 10: Official over-quota policy when Premium subscription expires:
     * - Does NOT delete any product records.
     * - Preserves 100% of internal ERP product data.
     * - Detects isOverListingQuota() condition.
     * - Blocks publishing new products while in over-quota state.
     * - Unpublishing products back below capacity re-enables publishing.
     */
    public function test_over_quota_policy_when_premium_expires_preserves_products_and_blocks_new_publications(): void
    {
        $entitlementService = app(TenantEntitlementService::class);
        $publicationService = app(ProductPublicationService::class);

        // 1. Initial base capacity = 5 slots
        $this->assertEquals(5, $entitlementService->getListingCapacity($this->outletA->id));

        // 2. Activate Premium subscription (+10 slots -> total 15)
        $sub = TenantSubscription::create([
            'outlet_id'   => $this->outletA->id,
            'type'        => TenantSubscription::TYPE_PREMIUM,
            'status'      => TenantSubscription::STATUS_ACTIVE,
            'starts_at'   => now()->subDays(10),
            'expires_at'  => now()->addDays(20),
        ]);

        $this->assertEquals(15, $entitlementService->getListingCapacity($this->outletA->id));

        // 3. Publish 6 products (legal under Premium 15-slot capacity)
        $products = [];
        for ($i = 1; $i <= 6; $i++) {
            $prod = Product::create([
                'outlet_id'    => $this->outletA->id,
                'product_name' => "Hijab Elegan Koleksi {$i}",
                'active'       => true,
                'is_published' => false,
            ]);
            $publicationService->publish($prod, $this->outletA->id);
            $products[] = $prod;
        }

        $this->assertEquals(6, $entitlementService->getPublishedListingCount($this->outletA->id));
        $this->assertFalse($entitlementService->isOverListingQuota($this->outletA->id));

        // 4. Premium subscription expires
        $sub->update([
            'status'     => TenantSubscription::STATUS_EXPIRED,
            'expires_at' => now()->subDay(),
        ]);

        // Capacity drops back to base 5 slots
        $this->assertEquals(5, $entitlementService->getListingCapacity($this->outletA->id));

        // System detects over-quota condition (6 published > 5 capacity)
        $this->assertTrue($entitlementService->isOverListingQuota($this->outletA->id));

        // CRITICAL: All 6 products MUST STILL EXIST in the database (NO DELETION!)
        $this->assertEquals(6, Product::where('outlet_id', $this->outletA->id)->count());
        $this->assertEquals(6, Product::where('outlet_id', $this->outletA->id)->where('is_published', true)->count());

        // 5. Attempting to publish a 7th product MUST BE BLOCKED
        $prod7 = Product::create([
            'outlet_id'    => $this->outletA->id,
            'product_name' => 'Hijab Koleksi Baru Ke-7',
            'active'       => true,
            'is_published' => false,
        ]);

        try {
            $publicationService->publish($prod7, $this->outletA->id);
            $this->fail('Publishing in over-quota state must be blocked with ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('listing_quota', $e->errors());
        }

        // 6. Owner manually unpublishes 2 products -> published count becomes 4 (< 5 capacity)
        $publicationService->unpublish($products[0], $this->outletA->id);
        $publicationService->unpublish($products[1], $this->outletA->id);

        $this->assertEquals(4, $entitlementService->getPublishedListingCount($this->outletA->id));
        $this->assertFalse($entitlementService->isOverListingQuota($this->outletA->id));

        // 7. Now publishing the 7th product SUCCEEDS!
        $publicationService->publish($prod7, $this->outletA->id);
        $prod7->refresh();
        $this->assertTrue($prod7->is_published);
        $this->assertEquals(5, $entitlementService->getPublishedListingCount($this->outletA->id));
    }
}

