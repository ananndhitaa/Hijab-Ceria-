<?php

namespace App\Services\Platform;

use App\Models\Platform\AdCampaign;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AdRankingAndDistributionService
{
    public const CYCLE_TURNS = 20;
    public const TURN_SECONDS = 7;
    public const CYCLE_SECONDS = 140;

    /**
     * Get active Shop Ads ranked by effective bid per day descending,
     * with tie-breaks: verified_payment_at ASC, id ASC.
     *
     * @return Collection<int, AdCampaign>
     */
    public function getRankedActiveShopAds(?Carbon $now = null): Collection
    {
        $time = $now ?? now();

        return AdCampaign::with('outlet')
            ->where('ad_type', AdCampaign::TYPE_SHOP_AD)
            ->where('status', AdCampaign::STATUS_ACTIVE)
            ->where('starts_at', '<=', $time)
            ->where('ends_at', '>', $time)
            ->orderByDesc('effective_bid_per_day')
            ->orderBy('verified_payment_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Calculate Center Highlight turns allocation and interleaved rotation schedule for 20 slots.
     * Guaranteed: exactly 20 slots, each eligible sponsor gets >= 1 turn,
     * remaining turns allocated via Largest Remainder Method, interleaved without clumping.
     *
     * @return array{turns: array<int, int>, schedule: array<int, array>}
     */
    public function generateCenterHighlightSchedule(?Carbon $now = null): array
    {
        $campaigns = $this->getRankedActiveShopAds($now);
        $count = $campaigns->count();

        if ($count === 0) {
            return [
                'turns' => [],
                'schedule' => [],
            ];
        }

        // Case: single sponsor gets all 20 turns
        if ($count === 1) {
            $campaign = $campaigns->first();
            $schedule = [];
            for ($turn = 0; $turn < self::CYCLE_TURNS; $turn++) {
                $schedule[] = [
                    'turn_index' => $turn,
                    'start_second' => $turn * self::TURN_SECONDS,
                    'duration_second' => self::TURN_SECONDS,
                    'campaign_id' => $campaign->id,
                    'shop_name' => $campaign->outlet?->name,
                    'title' => $campaign->title,
                    'tagline' => $campaign->tagline,
                    'banner_path' => $campaign->banner_path,
                ];
            }

            return [
                'turns' => [$campaign->id => self::CYCLE_TURNS],
                'schedule' => $schedule,
            ];
        }

        // Multi-sponsor: 2 <= N <= 5
        // Step 1: Every eligible campaign receives at least 1 turn
        $allocatedTurns = [];
        foreach ($campaigns as $camp) {
            $allocatedTurns[$camp->id] = 1;
        }

        // Step 2: Remaining turns to distribute
        $remainingTurns = self::CYCLE_TURNS - $count;
        $totalBid = (float) $campaigns->sum('effective_bid_per_day');

        // Step 3: Largest Remainder Method (Hamilton-Hare)
        $fractions = [];
        $integerSum = 0;

        foreach ($campaigns as $camp) {
            $bid = (float) $camp->effective_bid_per_day;
            $quota = $totalBid > 0 ? ($remainingTurns * ($bid / $totalBid)) : ($remainingTurns / $count);
            $floorQuota = (int) floor($quota);
            $remainder = $quota - $floorQuota;

            $allocatedTurns[$camp->id] += $floorQuota;
            $integerSum += $floorQuota;

            $fractions[] = [
                'id' => $camp->id,
                'remainder' => $remainder,
                'bid' => $bid,
                'verified_at' => $camp->verified_payment_at,
            ];
        }

        $leftover = $remainingTurns - $integerSum;

        // Step 4: Sort by remainder descending, tie-break bid DESC, verified_at ASC
        usort($fractions, function ($a, $b) {
            if ($b['remainder'] != $a['remainder']) {
                return $b['remainder'] <=> $a['remainder'];
            }
            if ($b['bid'] != $a['bid']) {
                return $b['bid'] <=> $a['bid'];
            }
            return $a['id'] <=> $b['id'];
        });

        for ($i = 0; $i < $leftover && $i < count($fractions); $i++) {
            $allocatedTurns[$fractions[$i]['id']]++;
        }

        // Step 5: Interleave the 20 turns evenly (Bresenham-like fair distribution)
        // Avoid grouping all turns from one sponsor together.
        $campaignMap = $campaigns->keyBy('id');
        $countsNeeded = $allocatedTurns;
        $countsAssigned = array_fill_keys(array_keys($allocatedTurns), 0);
        $interleavedCampaignIds = [];

        for ($slot = 1; $slot <= self::CYCLE_TURNS; $slot++) {
            // Find campaign with greatest deficit: (needed / 20 * slot) - assigned
            $bestId = null;
            $maxDeficit = -999999.0;

            foreach ($countsNeeded as $id => $needed) {
                if ($countsAssigned[$id] >= $needed) {
                    continue;
                }
                $ideal = ($needed / (float) self::CYCLE_TURNS) * $slot;
                $deficit = $ideal - $countsAssigned[$id];

                if ($deficit > $maxDeficit) {
                    $maxDeficit = $deficit;
                    $bestId = $id;
                }
            }

            if ($bestId !== null) {
                $interleavedCampaignIds[] = $bestId;
                $countsAssigned[$bestId]++;
            }
        }

        // Build schedule array
        $schedule = [];
        foreach ($interleavedCampaignIds as $turn => $campId) {
            $campaign = $campaignMap[$campId];
            $schedule[] = [
                'turn_index' => $turn,
                'start_second' => $turn * self::TURN_SECONDS,
                'duration_second' => self::TURN_SECONDS,
                'campaign_id' => $campaign->id,
                'shop_name' => $campaign->outlet?->name,
                'title' => $campaign->title,
                'tagline' => $campaign->tagline,
                'banner_path' => $campaign->banner_path,
            ];
        }

        return [
            'turns' => $allocatedTurns,
            'schedule' => $schedule,
        ];
    }

    /**
     * Get up to 6 active eligible Featured Product Ads.
     * Ordered by effective_bid_per_day DESC, verified_payment_at ASC, id ASC.
     * Only displays ads whose underlying product is currently published.
     *
     * @return Collection<int, AdCampaign>
     */
    public function getFeaturedProductAds(?Carbon $now = null, int $limit = 6): Collection
    {
        $time = $now ?? now();

        return AdCampaign::with(['product', 'outlet'])
            ->where('ad_type', AdCampaign::TYPE_PRODUCT_AD)
            ->where('status', AdCampaign::STATUS_ACTIVE)
            ->where('starts_at', '<=', $time)
            ->where('ends_at', '>', $time)
            ->whereHas('product', function ($p) {
                $p->where('is_published', true);
            })
            ->orderByDesc('effective_bid_per_day')
            ->orderBy('verified_payment_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Preview top 3 competitors for Owner without leaking private financial records.
     */
    public function getCompetitorPreview(string $adType, Carbon $startsAt, Carbon $endsAt, int $ownOutletId): array
    {
        $overlapping = AdCampaign::where('ad_type', $adType)
            ->where('outlet_id', '!=', $ownOutletId)
            ->whereIn('status', [AdCampaign::STATUS_SCHEDULED, AdCampaign::STATUS_ACTIVE])
            ->where(function ($interval) use ($startsAt, $endsAt) {
                $interval->where('starts_at', '<', $endsAt)
                         ->where('ends_at', '>', $startsAt);
            })
            ->orderByDesc('effective_bid_per_day')
            ->limit(3)
            ->get();

        return $overlapping->map(function ($camp, $index) {
            return [
                'rank' => $index + 1,
                'effective_bid_per_day' => (float) $camp->effective_bid_per_day,
                'starts_at' => $camp->starts_at->toIso8601String(),
                'ends_at' => $camp->ends_at->toIso8601String(),
                'duration_days' => $camp->duration_days,
            ];
        })->toArray();
    }
}
