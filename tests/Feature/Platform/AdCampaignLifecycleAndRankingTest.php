<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Models\Master\Product;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\PlatformInvoice;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\Platform\AdCapacityAndScheduleService;
use App\Services\Platform\AdRankingAndDistributionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdCampaignLifecycleAndRankingTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet1;
    protected Outlet $outlet2;
    protected User $owner1;
    protected User $superAdmin;
    protected AdCapacityAndScheduleService $scheduleService;
    protected AdRankingAndDistributionService $rankingService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nayara.tenant_isolation_enabled' => true]);

        $this->outlet1 = Outlet::create([
            'outlet_name' => 'Nayara Hijab Yogya',
            'outlet_code' => 'OUT-YOGYA-01',
            'status' => 'ACTIVE',
        ]);

        $this->outlet2 = Outlet::create([
            'outlet_name' => 'Nayara Hijab Malang',
            'outlet_code' => 'OUT-MALANG-01',
            'status' => 'ACTIVE',
        ]);

        $this->owner1 = User::factory()->create([
            'name' => 'Owner Yogya',
            'email' => 'owner.yogya@nayara.test',
            'role' => UserRole::OWNER,
            'outlet_id' => $this->outlet1->id,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin.ads@nayara.test',
            'role' => UserRole::SUPER_ADMIN,
            'outlet_id' => null,
        ]);

        $this->scheduleService = app(AdCapacityAndScheduleService::class);
        $this->rankingService = app(AdRankingAndDistributionService::class);
    }

    public function test_cannot_have_more_than_five_concurrent_shop_ads(): void
    {
        $startsAt = now()->addDays(2);
        $endsAt = $startsAt->copy()->addDays(3);

        // Fill up all 5 slots with distinct outlets
        for ($i = 1; $i <= 5; $i++) {
            $out = Outlet::create(['outlet_name' => "Outlet {$i}", 'outlet_code' => "OUT-{$i}", 'status' => 'ACTIVE']);
            AdCampaign::create([
                'outlet_id' => $out->id,
                'ad_type' => AdCampaign::TYPE_SHOP_AD,
                'title' => "Campaign {$i}",
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AdCampaign::STATUS_SCHEDULED,
            ]);
        }

        $this->expectException(ValidationException::class);
        $this->scheduleService->checkCapacity(
            adType: AdCampaign::TYPE_SHOP_AD,
            startsAt: $startsAt->copy()->addHours(1),
            endsAt: $endsAt->copy()->subHours(1),
            outletId: $this->outlet1->id
        );
    }

    public function test_tenant_cannot_have_overlapping_shop_ads(): void
    {
        $startsAt = now()->addDays(1);
        $endsAt = $startsAt->copy()->addDays(3);

        AdCampaign::create([
            'outlet_id' => $this->outlet1->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'First Shop Ad',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => AdCampaign::STATUS_SCHEDULED,
        ]);

        $this->expectException(ValidationException::class);
        // Overlapping interval attempt for the same tenant
        $this->scheduleService->checkCapacity(
            adType: AdCampaign::TYPE_SHOP_AD,
            startsAt: $startsAt->copy()->addDay(),
            endsAt: $endsAt->copy()->addDay(),
            outletId: $this->outlet1->id
        );
    }

    public function test_ad_invoice_has_strictly_two_hour_expiration(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id' => $this->outlet1->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Promo Lebaran',
            'status' => AdCampaign::STATUS_APPROVED,
        ]);

        $startsAt = now()->addDays(3);
        $invoice = $this->scheduleService->reserveAndCreateInvoice(
            campaign: $campaign,
            totalBudget: 300000,
            durationDays: 3,
            startsAt: $startsAt,
            user: $this->owner1
        );

        $this->assertEquals(PlatformInvoice::STATUS_UNPAID, $invoice->status);
        $this->assertEquals(AdCampaign::STATUS_PAYMENT_PENDING, $campaign->fresh()->status);
        // Expiration exactly 2 hours from now
        $this->assertEquals(2, round(now()->diffInMinutes($invoice->expires_at) / 60));
    }

    public function test_expired_reservations_are_released_automatically(): void
    {
        $campaign = AdCampaign::create([
            'outlet_id' => $this->outlet1->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Expired Reservation',
            'status' => AdCampaign::STATUS_APPROVED,
        ]);

        $invoice = $this->scheduleService->reserveAndCreateInvoice(
            campaign: $campaign,
            totalBudget: 150000,
            durationDays: 1,
            startsAt: now()->addDays(2),
            user: $this->owner1
        );

        // Fast forward invoice past 2 hours
        $invoice->expires_at = now()->subMinute();
        $invoice->save();

        $released = $this->scheduleService->releaseExpiredReservations();
        $this->assertEquals(1, $released);
        $this->assertEquals(AdCampaign::STATUS_APPROVED, $campaign->fresh()->status); // Released back to approved
        $this->assertEquals(PlatformInvoice::STATUS_EXPIRED, $invoice->fresh()->status);
    }

    public function test_effective_bid_ranking_ranks_by_daily_bid_not_total_budget(): void
    {
        $now = Carbon::parse('2026-10-25 10:00:00');

        // Campaign A: Budget 500,000 for 10 days = 50,000 / day
        $campA = AdCampaign::create([
            'outlet_id' => $this->outlet1->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Shop A',
            'total_budget' => 500000,
            'duration_days' => 10,
            'effective_bid_per_day' => 50000,
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addDays(9),
            'status' => AdCampaign::STATUS_ACTIVE,
            'verified_payment_at' => $now->copy()->subHours(2),
        ]);

        // Campaign B: Budget 300,000 for 3 days = 100,000 / day (HIGHER daily bid despite lower total budget!)
        $campB = AdCampaign::create([
            'outlet_id' => $this->outlet2->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Shop B',
            'total_budget' => 300000,
            'duration_days' => 3,
            'effective_bid_per_day' => 100000,
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addDays(2),
            'status' => AdCampaign::STATUS_ACTIVE,
            'verified_payment_at' => $now->copy()->subHour(),
        ]);

        $ranked = $this->rankingService->getRankedActiveShopAds($now);

        $this->assertCount(2, $ranked);
        // Section O: Rank by effective bid per day descending -> B must come first!
        $this->assertEquals($campB->id, $ranked->first()->id);
        $this->assertEquals($campA->id, $ranked->last()->id);
    }

    public function test_center_highlight_allocates_all_twenty_turns_to_single_sponsor(): void
    {
        $now = Carbon::parse('2026-10-25 10:00:00');

        $camp = AdCampaign::create([
            'outlet_id' => $this->outlet1->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Sole Sponsor',
            'total_budget' => 100000,
            'duration_days' => 1,
            'effective_bid_per_day' => 100000,
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addDay(),
            'status' => AdCampaign::STATUS_ACTIVE,
            'verified_payment_at' => $now->copy()->subHour(),
        ]);

        $distribution = $this->rankingService->generateCenterHighlightSchedule($now);

        $this->assertEquals(20, $distribution['turns'][$camp->id]);
        $this->assertCount(20, $distribution['schedule']);
    }

    public function test_center_highlight_multi_sponsor_largest_remainder_guarantees_exactly_twenty_turns(): void
    {
        $now = Carbon::parse('2026-10-25 10:00:00');

        // 3 active sponsors with bids: 100k, 60k, 40k
        $c1 = AdCampaign::create([
            'outlet_id' => $this->outlet1->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Sponsor 1',
            'effective_bid_per_day' => 100000,
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addDay(),
            'status' => AdCampaign::STATUS_ACTIVE,
            'verified_payment_at' => $now->copy()->subHours(3),
        ]);

        $c2 = AdCampaign::create([
            'outlet_id' => $this->outlet2->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Sponsor 2',
            'effective_bid_per_day' => 60000,
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addDay(),
            'status' => AdCampaign::STATUS_ACTIVE,
            'verified_payment_at' => $now->copy()->subHours(2),
        ]);

        $out3 = Outlet::create(['outlet_name' => 'Outlet 3', 'outlet_code' => 'OUT-03', 'status' => 'ACTIVE']);
        $c3 = AdCampaign::create([
            'outlet_id' => $out3->id,
            'ad_type' => AdCampaign::TYPE_SHOP_AD,
            'title' => 'Sponsor 3',
            'effective_bid_per_day' => 40000,
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addDay(),
            'status' => AdCampaign::STATUS_ACTIVE,
            'verified_payment_at' => $now->copy()->subHours(1),
        ]);

        $distribution = $this->rankingService->generateCenterHighlightSchedule($now);

        // Every active campaign receives at least 1 turn
        $this->assertGreaterThanOrEqual(1, $distribution['turns'][$c1->id]);
        $this->assertGreaterThanOrEqual(1, $distribution['turns'][$c2->id]);
        $this->assertGreaterThanOrEqual(1, $distribution['turns'][$c3->id]);

        // Total turns must equal exactly 20 (Section P)
        $totalTurns = array_sum($distribution['turns']);
        $this->assertEquals(20, $totalTurns);
        $this->assertCount(20, $distribution['schedule']);
    }

    public function test_featured_product_ads_only_shows_published_products_up_to_six(): void
    {
        $now = Carbon::parse('2026-10-25 10:00:00');

        $published = Product::create([
            'outlet_id' => $this->outlet1->id,
            'name' => 'Published Hijab',
            'product_code' => 'PUB-01',
            'is_published' => true,
        ]);

        $unpublished = Product::create([
            'outlet_id' => $this->outlet1->id,
            'name' => 'Unpublished Hijab',
            'product_code' => 'UNPUB-01',
            'is_published' => false,
        ]);

        AdCampaign::create([
            'outlet_id' => $this->outlet1->id,
            'ad_type' => AdCampaign::TYPE_PRODUCT_AD,
            'product_id' => $published->id,
            'title' => 'Ad Published',
            'effective_bid_per_day' => 50000,
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addDay(),
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        AdCampaign::create([
            'outlet_id' => $this->outlet1->id,
            'ad_type' => AdCampaign::TYPE_PRODUCT_AD,
            'product_id' => $unpublished->id,
            'title' => 'Ad Unpublished',
            'effective_bid_per_day' => 80000,
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addDay(),
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $featured = $this->rankingService->getFeaturedProductAds($now, 6);

        $this->assertCount(1, $featured);
        $this->assertEquals($published->id, $featured->first()->product_id);
    }
}
