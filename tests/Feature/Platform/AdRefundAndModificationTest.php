<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\AdCampaignRevision;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformNotification;
use App\Models\Platform\PlatformRefund;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Platform\AdCapacityAndScheduleService;
use App\Services\Platform\AdRefundService;
use App\Services\Platform\PlatformNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdRefundAndModificationTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected User $owner;
    protected User $superAdmin;
    protected AdCapacityAndScheduleService $scheduleService;
    protected AdRefundService $refundService;
    protected PlatformNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->outlet = Outlet::create([
            'outlet_name' => 'Nayara Hijab Semarang',
            'outlet_code' => 'OUT-SMG-01',
            'status' => 'ACTIVE',
        ]);

        $this->owner = User::factory()->create([
            'name' => 'Owner Semarang',
            'email' => 'owner.semarang@nayara.test',
            'role' => UserRole::OWNER,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Platform',
            'email' => 'admin.refunds@nayara.test',
            'role' => UserRole::SUPER_ADMIN,
            'outlet_id' => null,
        ]);

        $this->scheduleService = app(AdCapacityAndScheduleService::class);
        $this->refundService = app(AdRefundService::class);
        $this->notificationService = app(PlatformNotificationService::class);
    }

    public function test_owner_can_reschedule_at_most_two_times_before_airing(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id' => $this->outlet->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Campaign Semarang',
            'duration_days' => 3,
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addDays(8),
            'status' => AdCampaign::STATUS_SCHEDULED,
            'schedule_change_count' => 0,
        ]);

        // Reschedule 1: Success
        $this->scheduleService->reschedule($campaign, now()->addDays(6));
        $this->assertEquals(1, $campaign->fresh()->schedule_change_count);

        // Reschedule 2: Success
        $this->scheduleService->reschedule($campaign->fresh(), now()->addDays(7));
        $this->assertEquals(2, $campaign->fresh()->schedule_change_count);

        // Reschedule 3: Fails (exceeded max 2)
        $this->expectException(ValidationException::class);
        $this->scheduleService->reschedule($campaign->fresh(), now()->addDays(8));
    }

    public function test_reschedule_fails_after_airing_begins(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id' => $this->outlet->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Already Running',
            'duration_days' => 2,
            'starts_at' => now()->subHours(2), // Already started airing
            'ends_at' => now()->addDays(2),
            'status' => AdCampaign::STATUS_ACTIVE,
            'schedule_change_count' => 0,
        ]);

        $this->expectException(ValidationException::class);
        $this->scheduleService->reschedule($campaign, now()->addDays(1));
    }

    public function test_cancellation_before_airing_entitles_one_hundred_percent_refund(): void
    {
        $invoice = PlatformInvoice::create([
            'invoice_no' => 'INV-TEST-001',
            'outlet_id' => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_SHOP_AD,
            'amount' => 200000,
            'status' => PlatformInvoice::STATUS_VERIFIED,
            'expires_at' => now()->addDay(),
        ]);

        $campaign = AdCampaign::create([
            'outlet_id' => $this->outlet->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Pre-airing Campaign',
            'total_budget' => 200000,
            'duration_days' => 2,
            'starts_at' => now()->addDays(3), // Future start
            'ends_at' => now()->addDays(5),
            'status' => AdCampaign::STATUS_SCHEDULED,
            'platform_invoice_id' => $invoice->id,
        ]);

        $result = $this->refundService->requestCancellation(
            campaign: $campaign,
            reason: 'Toko libur mendadak',
            bankDetails: [
                'bank_name' => 'BCA',
                'account_number' => '12345678',
                'account_name' => 'Owner Semarang',
            ]
        );

        $this->assertTrue($result['eligible_for_refund']);
        $this->assertEquals(200000, (int) $result['refund_amount']);
        $this->assertEquals(AdCampaign::STATUS_CANCELLED, $campaign->fresh()->status);

        $refund = PlatformRefund::where('ad_campaign_id', $campaign->id)->first();
        $this->assertNotNull($refund);
        $this->assertEquals(PlatformRefund::TYPE_FULL_100_PERCENT, $refund->refund_type);
        $this->assertEquals(PlatformRefund::STATUS_REQUESTED, $refund->status);
    }

    public function test_cancellation_after_airing_begins_entitles_zero_refund(): void
    {
        $invoice = PlatformInvoice::create([
            'invoice_no' => 'INV-TEST-002',
            'outlet_id' => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_SHOP_AD,
            'amount' => 150000,
            'status' => PlatformInvoice::STATUS_VERIFIED,
            'expires_at' => now()->addDay(),
        ]);

        $campaign = AdCampaign::create([
            'outlet_id' => $this->outlet->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Running Campaign',
            'total_budget' => 150000,
            'duration_days' => 2,
            'starts_at' => now()->subHours(5), // Already airing
            'ends_at' => now()->addDays(2),
            'status' => AdCampaign::STATUS_ACTIVE,
            'platform_invoice_id' => $invoice->id,
        ]);

        $result = $this->refundService->requestCancellation(
            campaign: $campaign,
            reason: 'Ingin stop di tengah jalan',
            bankDetails: [
                'bank_name' => 'BCA',
                'account_number' => '12345678',
                'account_name' => 'Owner Semarang',
            ]
        );

        // Section U: Cancelled AFTER airing begins -> 0% refund
        $this->assertFalse($result['eligible_for_refund']);
        $this->assertEquals(0, (int) $result['refund_amount']);
        $this->assertEquals(AdCampaign::STATUS_CANCELLED, $campaign->fresh()->status);
        $this->assertNull(PlatformRefund::where('ad_campaign_id', $campaign->id)->first());
    }

    public function test_super_admin_approves_and_completes_refund_with_evidence(): void
    {
        $refund = PlatformRefund::create([
            'outlet_id' => $this->outlet->id,
            'platform_invoice_id' => null,
            'ad_campaign_id' => null,
            'refund_type' => PlatformRefund::TYPE_FULL_100_PERCENT,
            'amount' => 100000,
            'reason' => 'Pembatalan jadwal sebelum tayang',
            'status' => PlatformRefund::STATUS_REQUESTED,
            'destination_bank_name' => 'BCA',
            'destination_account_number' => '88889999',
            'destination_account_name' => 'Owner',
        ]);

        // Step 1: Approve
        $this->refundService->approveRefund($refund, $this->superAdmin);
        $this->assertEquals(PlatformRefund::STATUS_APPROVED, $refund->fresh()->status);
        $this->assertEquals($this->superAdmin->id, $refund->fresh()->approved_by);

        // Step 2: Settle with transfer evidence
        $this->refundService->completeRefund($refund->fresh(), 'proofs/refund_transfer_01.jpg', $this->superAdmin);
        $this->assertEquals(PlatformRefund::STATUS_COMPLETED, $refund->fresh()->status);
        $this->assertEquals('proofs/refund_transfer_01.jpg', $refund->fresh()->transfer_proof_path);
        $this->assertNotNull($refund->fresh()->completed_at);
    }

    public function test_proportional_compensation_calculated_correctly_for_service_failure(): void
    {
        $invoice = PlatformInvoice::create([
            'invoice_no' => 'INV-TEST-003',
            'outlet_id' => $this->outlet->id,
            'service_type' => PlatformInvoice::SERVICE_SHOP_AD,
            'amount' => 200000,
            'status' => PlatformInvoice::STATUS_VERIFIED,
            'expires_at' => now()->addDay(),
        ]);

        $campaign = AdCampaign::create([
            'outlet_id' => $this->outlet->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Server Outage Campaign',
            'total_budget' => 200000,
            'duration_days' => 2, // 2 days = 172,800 seconds
            'status' => AdCampaign::STATUS_ACTIVE,
            'platform_invoice_id' => $invoice->id,
        ]);

        // Half day lost = 43,200 seconds out of 172,800 seconds = 25% of 200,000 = Rp 50,000
        $lostSeconds = 43200;
        $compRefund = $this->refundService->issueProportionalCompensation(
            campaign: $campaign,
            lostSeconds: $lostSeconds,
            incidentReason: 'Data Center Maintenance',
            bankDetails: [
                'bank_name' => 'Mandiri',
                'account_number' => '999888',
                'account_name' => 'Owner Semarang',
            ],
            admin: $this->superAdmin
        );

        $this->assertEquals(PlatformRefund::TYPE_PROPORTIONAL, $compRefund->refund_type);
        $this->assertEquals(50000, (int) $compRefund->amount);
    }

    public function test_revision_reminders_dispatched_within_sla_window(): void
    {
        // 1-day campaign with 8 hours remaining needing revision (<= 12h)
        $camp = AdCampaign::create([
            'outlet_id' => $this->outlet->id,
            'created_by' => $this->owner->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Needs Urgent Revision',
            'duration_days' => 1,
            'starts_at' => now()->subHours(16),
            'ends_at' => now()->addHours(8), // 8h remaining <= 12h
            'status' => AdCampaign::STATUS_NEEDS_REVISION,
        ]);

        $sent = $this->notificationService->sendRevisionReminders();
        $this->assertGreaterThanOrEqual(1, $sent);

        $notif = PlatformNotification::where('outlet_id', $this->outlet->id)->first();
        $this->assertNotNull($notif);
        $this->assertEquals('AD_REVISION_REMINDER', $notif->type);
    }
}
