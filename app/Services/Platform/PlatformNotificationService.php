<?php

namespace App\Services\Platform;

use App\Models\Platform\AdCampaign;
use App\Models\Platform\OwnerComplaint;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformNotification;
use App\Models\Platform\PlatformRefund;
use App\Models\Platform\TenantRegistration;
use Illuminate\Database\Eloquent\Collection;

class PlatformNotificationService
{
    /**
     * Get real-time actionable Super Admin platform tasks and notification counters.
     * Synchronized across all platform pages (Dashboard, Registrations, Tenants, Billing, etc.).
     *
     * @return array{
     *     total: int,
     *     pending_registrations: int,
     *     needs_revision: int,
     *     pending_invoices: int,
     *     pending_ads: int,
     *     pending_refunds: int,
     *     pending_complaints: int
     * }
     */
    public static function getActionableTasks(): array
    {
        $pendingRegistrations = rescue(
            fn () => TenantRegistration::where('status', TenantRegistration::STATUS_PENDING)->count(),
            0,
            false
        );

        $needsRevision = rescue(
            fn () => TenantRegistration::where('status', TenantRegistration::STATUS_NEEDS_REVISION)->count(),
            0,
            false
        );

        $pendingInvoices = rescue(
            fn () => PlatformInvoice::where('status', PlatformInvoice::STATUS_PAYMENT_SUBMITTED)->count(),
            0,
            false
        );

        $pendingAds = rescue(
            fn () => AdCampaign::where('status', AdCampaign::STATUS_PENDING_REVIEW)->count(),
            0,
            false
        );

        $pendingRefunds = rescue(
            fn () => PlatformRefund::whereIn('status', [
                PlatformRefund::STATUS_REQUESTED,
                PlatformRefund::STATUS_APPROVED,
            ])->count(),
            0,
            false
        );

        $pendingComplaints = rescue(
            fn () => OwnerComplaint::whereIn('status', [
                OwnerComplaint::STATUS_NEW,
                OwnerComplaint::STATUS_IN_PROGRESS,
            ])->count(),
            0,
            false
        );

        $total = $pendingRegistrations
            + $needsRevision
            + $pendingInvoices
            + $pendingAds
            + $pendingRefunds
            + $pendingComplaints;

        return [
            'total'                 => (int) $total,
            'pending_registrations' => (int) $pendingRegistrations,
            'needs_revision'        => (int) $needsRevision,
            'pending_invoices'      => (int) $pendingInvoices,
            'pending_ads'           => (int) $pendingAds,
            'pending_refunds'       => (int) $pendingRefunds,
            'pending_complaints'    => (int) $pendingComplaints,
        ];
    }
    /**
     * Dispatch an in-app platform notification.
     */
    public function notify(
        ?int $outletId,
        ?int $userId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null
    ): PlatformNotification {
        return PlatformNotification::create([
            'outlet_id' => $outletId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'is_read' => false,
        ]);
    }

    /**
     * Get unread notifications for a tenant outlet.
     *
     * @return Collection<int, PlatformNotification>
     */
    public function getUnreadForOutlet(int $outletId): Collection
    {
        return PlatformNotification::where('outlet_id', $outletId)
            ->where('is_read', false)
            ->latest('id')
            ->get();
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $notificationId): bool
    {
        return (bool) PlatformNotification::where('id', $notificationId)->update(['is_read' => true]);
    }

    /**
     * Check active campaigns needing revision and send reminder notifications if within SLA window (Section V).
     */
    public function sendRevisionReminders(): int
    {
        $now = now();
        $campaignsNeedingRevision = AdCampaign::where('status', AdCampaign::STATUS_NEEDS_REVISION)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', $now)
            ->get();

        $sentCount = 0;
        foreach ($campaignsNeedingRevision as $camp) {
            $remainingHours = $now->diffInHours($camp->ends_at, false);
            $durationDays = $camp->duration_days;

            // 1-day campaign: 12h before end; 2+ days: 24h before end
            $shouldRemind = ($durationDays == 1 && $remainingHours <= 12)
                || ($durationDays >= 2 && $remainingHours <= 24);

            if ($shouldRemind) {
                // Check if reminder was already sent in last 12 hours
                $alreadySent = PlatformNotification::where('outlet_id', $camp->outlet_id)
                    ->where('type', 'AD_REVISION_REMINDER')
                    ->where('created_at', '>=', now()->subHours(12))
                    ->exists();

                if (! $alreadySent) {
                    $this->notify(
                        outletId: $camp->outlet_id,
                        userId: $camp->created_by,
                        type: 'AD_REVISION_REMINDER',
                        title: 'Pengingat Revisi Materi Iklan',
                        message: "Kampanye '{$camp->title}' membutuhkan revisi materi. Sisa waktu tayang: {$remainingHours} jam sebelum masa aktif berakhir.",
                        actionUrl: route('owner.ads.show', $camp->id, false)
                    );
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }
}
