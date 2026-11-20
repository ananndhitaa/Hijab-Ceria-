<?php

namespace App\Services\Platform;

use App\Models\Master\Product;
use App\Models\Platform\OutletListingSlot;
use App\Models\Platform\TenantSubscription;

class TenantEntitlementService
{
    public const BASE_FREE_SLOTS = 5;
    public const PREMIUM_SLOT_BONUS = 10;

    /**
     * Get total public listing capacity for an outlet.
     */
    public function getListingCapacity(int $outletId): int
    {
        $purchased = (int) (OutletListingSlot::where('outlet_id', $outletId)->value('purchased_slots') ?? 0);
        $premiumBonus = $this->hasActivePremium($outletId) ? self::PREMIUM_SLOT_BONUS : 0;

        return self::BASE_FREE_SLOTS + $purchased + $premiumBonus;
    }

    /**
     * Get number of currently active published public listings.
     */
    public function getPublishedListingCount(int $outletId): int
    {
        return Product::where('outlet_id', $outletId)
            ->where('is_published', true)
            ->count();
    }

    /**
     * Get remaining available slots.
     */
    public function getRemainingListingSlots(int $outletId): int
    {
        $capacity = $this->getListingCapacity($outletId);
        $published = $this->getPublishedListingCount($outletId);

        return max(0, $capacity - $published);
    }

    /**
     * Check if tenant is exceeding their allowed quota.
     * (e.g. after Premium subscription expired).
     */
    public function isOverListingQuota(int $outletId): bool
    {
        return $this->getPublishedListingCount($outletId) > $this->getListingCapacity($outletId);
    }

    /**
     * Check if tenant has active Premium subscription.
     */
    public function hasActivePremium(int $outletId): bool
    {
        return TenantSubscription::where('outlet_id', $outletId)
            ->where('type', TenantSubscription::TYPE_PREMIUM)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>=', now())
            ->exists();
    }

    /**
     * Check if tenant has active Financial Analytics Add-On.
     */
    public function hasActiveAnalyticsAddOn(int $outletId): bool
    {
        return TenantSubscription::where('outlet_id', $outletId)
            ->where('type', TenantSubscription::TYPE_ANALYTICS_ADDON)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>=', now())
            ->exists();
    }

    /**
     * Check if tenant has financial analytics access (Premium OR Add-on).
     */
    public function hasFinancialAnalyticsEntitlement(int $outletId): bool
    {
        return $this->hasActivePremium($outletId) || $this->hasActiveAnalyticsAddOn($outletId);
    }

    /**
     * Check if tenant is entitled to export PDF/Excel reports.
     */
    public function canExportFinancialReports(int $outletId): bool
    {
        return $this->hasFinancialAnalyticsEntitlement($outletId);
    }

    /**
     * Get active subscription for tenant if any.
     */
    public function getActiveSubscription(int $outletId, ?string $type = null): ?TenantSubscription
    {
        $query = TenantSubscription::where('outlet_id', $outletId)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>=', now())
            ->latest('expires_at');

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->first();
    }
}
