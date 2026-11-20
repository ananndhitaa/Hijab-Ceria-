<?php

namespace App\Services\Platform;

use App\Models\Platform\OutletListingSlot;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\TenantSubscription;
use App\Models\System\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformBillingService
{
    public const PREMIUM_PRICES = [
        1 => 59000,
        3 => 169000,
        6 => 319000,
        12 => 569000,
    ];

    public const ANALYTICS_ADDON_PRICES = [
        1 => 45000,
        3 => 129000,
        6 => 249000,
        12 => 489000,
    ];

    public const LISTING_SLOT_PACKAGES = [
        5 => 49000,
        10 => 89000,
    ];

    public const UPGRADE_PRICE_DIFFS = [
        1 => 14000,
        3 => 40000,
        6 => 70000,
        12 => 80000,
    ];

    /**
     * Create an invoice for Premium subscription.
     */
    public function createPremiumInvoice(int $outletId, int $months, ?User $user = null): PlatformInvoice
    {
        if (! isset(self::PREMIUM_PRICES[$months])) {
            throw ValidationException::withMessages([
                'duration' => ['Durasi paket Premium tidak valid.'],
            ]);
        }

        $amount = self::PREMIUM_PRICES[$months];

        return $this->createInvoice(
            outletId: $outletId,
            serviceType: PlatformInvoice::SERVICE_PREMIUM,
            amount: $amount,
            durationMonths: $months,
            expiresAt: now()->addHours(24),
            user: $user,
            metadata: ['months' => $months]
        );
    }

    /**
     * Create an invoice for Financial Analytics Add-On.
     */
    public function createAnalyticsAddonInvoice(int $outletId, int $months, ?User $user = null): PlatformInvoice
    {
        if (! isset(self::ANALYTICS_ADDON_PRICES[$months])) {
            throw ValidationException::withMessages([
                'duration' => ['Durasi paket Financial Analytics Add-on tidak valid.'],
            ]);
        }

        // Check if outlet already has active Premium (which includes analytics)
        $hasPremium = TenantSubscription::where('outlet_id', $outletId)
            ->where('type', TenantSubscription::TYPE_PREMIUM)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>=', now())
            ->exists();

        if ($hasPremium) {
            throw ValidationException::withMessages([
                'subscription' => ['Outlet sudah memiliki paket Premium aktif yang mencakup fitur Financial Analytics.'],
            ]);
        }

        $amount = self::ANALYTICS_ADDON_PRICES[$months];

        return $this->createInvoice(
            outletId: $outletId,
            serviceType: PlatformInvoice::SERVICE_ANALYTICS_ADDON,
            amount: $amount,
            durationMonths: $months,
            expiresAt: now()->addHours(24),
            user: $user,
            metadata: ['months' => $months]
        );
    }

    /**
     * Create an invoice for permanent listing slots (+5 or +10).
     */
    public function createListingSlotsInvoice(int $outletId, int $slots, ?User $user = null): PlatformInvoice
    {
        if (! isset(self::LISTING_SLOT_PACKAGES[$slots])) {
            throw ValidationException::withMessages([
                'slots' => ['Paket slot listing tidak valid. Pilih +5 atau +10 slot.'],
            ]);
        }

        $amount = self::LISTING_SLOT_PACKAGES[$slots];

        return $this->createInvoice(
            outletId: $outletId,
            serviceType: PlatformInvoice::SERVICE_LISTING_SLOTS,
            amount: $amount,
            durationMonths: null,
            expiresAt: now()->addHours(24),
            user: $user,
            metadata: ['slots' => $slots]
        );
    }

    /**
     * Create an invoice for Setup & Training Services.
     */
    public function createServiceInvoice(\App\Models\Platform\PlatformServiceRequest $serviceRequest, float $amount, ?User $user = null): PlatformInvoice
    {
        return $this->createInvoice(
            outletId: $serviceRequest->outlet_id,
            serviceType: PlatformInvoice::SERVICE_SETUP_TRAINING,
            amount: $amount,
            durationMonths: null,
            expiresAt: now()->addHours(72),
            user: $user,
            metadata: [
                'service_request_id' => $serviceRequest->id,
                'service_type' => $serviceRequest->service_type,
            ]
        );
    }

    /**
     * Calculate prorated upgrade quote from active Add-on to Premium.
     */
    public function calculateUpgradeQuote(int $outletId): array
    {
        $activeAddon = TenantSubscription::where('outlet_id', $outletId)
            ->where('type', TenantSubscription::TYPE_ANALYTICS_ADDON)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->first();

        if (! $activeAddon) {
            throw ValidationException::withMessages([
                'upgrade' => ['Tidak ada paket Financial Analytics Add-on aktif yang dapat di-upgrade.'],
            ]);
        }

        $remainingHours = now()->diffInHours($activeAddon->expires_at, false);
        if ($remainingHours < 24) {
            throw ValidationException::withMessages([
                'upgrade' => ['Sisa masa aktif Add-on kurang dari 24 jam. Upgrade tidak dapat diproses.'],
            ]);
        }

        // Determine base package duration from original invoice or fallback to days
        $durationMonths = 1;
        if ($activeAddon->invoice && $activeAddon->invoice->package_duration_months) {
            $durationMonths = $activeAddon->invoice->package_duration_months;
        } else {
            $totalDays = $activeAddon->starts_at->diffInDays($activeAddon->expires_at);
            if ($totalDays >= 300) {
                $durationMonths = 12;
            } elseif ($totalDays >= 150) {
                $durationMonths = 6;
            } elseif ($totalDays >= 75) {
                $durationMonths = 3;
            }
        }

        $priceDiff = self::UPGRADE_PRICE_DIFFS[$durationMonths] ?? self::UPGRADE_PRICE_DIFFS[1];
        $totalSeconds = max(1, $activeAddon->starts_at->diffInSeconds($activeAddon->expires_at));
        $remainingSeconds = max(0, now()->diffInSeconds($activeAddon->expires_at, false));

        $proratedAmount = round(($priceDiff * $remainingSeconds) / $totalSeconds);

        return [
            'subscription_id' => $activeAddon->id,
            'duration_months' => $durationMonths,
            'price_diff' => $priceDiff,
            'remaining_hours' => $remainingHours,
            'prorated_amount' => (float) max(0, $proratedAmount),
            'expires_at' => $activeAddon->expires_at,
        ];
    }

    /**
     * Create upgrade invoice from Analytics Add-on to Premium.
     */
    public function createUpgradeInvoice(int $outletId, ?User $user = null): PlatformInvoice
    {
        // Ensure no active pending upgrade invoice exists
        $pendingInvoice = PlatformInvoice::where('outlet_id', $outletId)
            ->where('service_type', PlatformInvoice::SERVICE_UPGRADE_PREMIUM)
            ->whereIn('status', [PlatformInvoice::STATUS_UNPAID, PlatformInvoice::STATUS_PAYMENT_SUBMITTED])
            ->where('expires_at', '>', now())
            ->first();

        if ($pendingInvoice) {
            return $pendingInvoice;
        }

        $quote = $this->calculateUpgradeQuote($outletId);

        return $this->createInvoice(
            outletId: $outletId,
            serviceType: PlatformInvoice::SERVICE_UPGRADE_PREMIUM,
            amount: $quote['prorated_amount'],
            durationMonths: $quote['duration_months'],
            expiresAt: now()->addHours(12), // Section K: Upgrade invoice expires after 12 hours
            user: $user,
            metadata: [
                'target_subscription_id' => $quote['subscription_id'],
                'target_expires_at' => $quote['expires_at']->toIso8601String(),
                'duration_months' => $quote['duration_months'],
            ]
        );
    }

    /**
     * Submit payment proof for an invoice.
     */
    public function submitPaymentProof(PlatformInvoice $invoice, string $proofPath, ?string $paymentMethod = null): PlatformInvoice
    {
        if ($invoice->isPaid() || $invoice->isVerified()) {
            throw ValidationException::withMessages([
                'invoice' => ['Invoice ini sudah diverifikasi.'],
            ]);
        }

        if (now()->greaterThan($invoice->expires_at)) {
            $invoice->status = PlatformInvoice::STATUS_EXPIRED;
            $invoice->save();

            throw ValidationException::withMessages([
                'invoice' => ['Invoice ini telah kedaluwarsa. Silakan ajukan pesanan baru.'],
            ]);
        }

        $invoice->update([
            'status' => PlatformInvoice::STATUS_PAYMENT_SUBMITTED,
            'payment_proof_path' => $proofPath,
            'payment_method' => $paymentMethod ?? 'BANK_TRANSFER',
            'payment_submitted_at' => now(),
        ]);

        return $invoice;
    }

    /**
     * Idempotently verify payment and activate the corresponding entitlement.
     */
    public function verifyPayment(PlatformInvoice $invoice, User $verifier): PlatformInvoice
    {
        return DB::transaction(function () use ($invoice, $verifier) {
            $lockedInvoice = PlatformInvoice::where('id', $invoice->id)->lockForUpdate()->first();

            // Idempotency: if already verified, do not re-apply
            if ($lockedInvoice->isVerified()) {
                return $lockedInvoice;
            }

            if (! in_array($lockedInvoice->status, [PlatformInvoice::STATUS_PAYMENT_SUBMITTED, PlatformInvoice::STATUS_UNPAID])) {
                throw ValidationException::withMessages([
                    'invoice' => ["Status invoice ({$lockedInvoice->status}) tidak dapat diverifikasi."],
                ]);
            }

            $lockedInvoice->status = PlatformInvoice::STATUS_VERIFIED;
            $lockedInvoice->verified_at = now();
            $lockedInvoice->verified_by = $verifier->id;
            $lockedInvoice->save();

            // Activate entitlement based on service type
            $this->activateEntitlement($lockedInvoice);

            return $lockedInvoice;
        });
    }

    /**
     * Internal handler to apply entitlement benefits upon payment verification.
     */
    protected function activateEntitlement(PlatformInvoice $invoice): void
    {
        $outletId = $invoice->outlet_id;

        switch ($invoice->service_type) {
            case PlatformInvoice::SERVICE_PREMIUM:
                $months = $invoice->package_duration_months ?? 1;
                $activeSub = TenantSubscription::where('outlet_id', $outletId)
                    ->where('type', TenantSubscription::TYPE_PREMIUM)
                    ->where('status', TenantSubscription::STATUS_ACTIVE)
                    ->where('expires_at', '>=', now())
                    ->latest('expires_at')
                    ->first();

                $startDate = now();
                $expiresDate = $activeSub ? $activeSub->expires_at->copy()->addMonths($months) : now()->addMonths($months);

                if ($activeSub) {
                    $activeSub->expires_at = $expiresDate;
                    $activeSub->save();
                } else {
                    TenantSubscription::create([
                        'outlet_id' => $outletId,
                        'type' => TenantSubscription::TYPE_PREMIUM,
                        'starts_at' => $startDate,
                        'expires_at' => $expiresDate,
                        'status' => TenantSubscription::STATUS_ACTIVE,
                        'platform_invoice_id' => $invoice->id,
                    ]);
                }
                break;

            case PlatformInvoice::SERVICE_ANALYTICS_ADDON:
                $months = $invoice->package_duration_months ?? 1;
                $activeSub = TenantSubscription::where('outlet_id', $outletId)
                    ->where('type', TenantSubscription::TYPE_ANALYTICS_ADDON)
                    ->where('status', TenantSubscription::STATUS_ACTIVE)
                    ->where('expires_at', '>=', now())
                    ->latest('expires_at')
                    ->first();

                $startDate = now();
                $expiresDate = $activeSub ? $activeSub->expires_at->copy()->addMonths($months) : now()->addMonths($months);

                if ($activeSub) {
                    $activeSub->expires_at = $expiresDate;
                    $activeSub->save();
                } else {
                    TenantSubscription::create([
                        'outlet_id' => $outletId,
                        'type' => TenantSubscription::TYPE_ANALYTICS_ADDON,
                        'starts_at' => $startDate,
                        'expires_at' => $expiresDate,
                        'status' => TenantSubscription::STATUS_ACTIVE,
                        'platform_invoice_id' => $invoice->id,
                    ]);
                }
                break;

            case PlatformInvoice::SERVICE_LISTING_SLOTS:
                $slotsToAdd = (int) ($invoice->metadata['slots'] ?? 5);
                $slotRecord = OutletListingSlot::firstOrCreate(
                    ['outlet_id' => $outletId],
                    ['purchased_slots' => 0]
                );
                $slotRecord->increment('purchased_slots', $slotsToAdd);
                break;

            case PlatformInvoice::SERVICE_UPGRADE_PREMIUM:
                $targetSubId = $invoice->metadata['target_subscription_id'] ?? null;
                $targetExpiresAt = isset($invoice->metadata['target_expires_at'])
                    ? Carbon::parse($invoice->metadata['target_expires_at'])
                    : now()->addMonth();

                if ($targetSubId) {
                    $oldSub = TenantSubscription::find($targetSubId);
                    if ($oldSub) {
                        $oldSub->status = TenantSubscription::STATUS_UPGRADED;
                        $oldSub->save();
                        $targetExpiresAt = $oldSub->expires_at;
                    }
                }

                TenantSubscription::create([
                    'outlet_id' => $outletId,
                    'type' => TenantSubscription::TYPE_PREMIUM,
                    'starts_at' => now(),
                    'expires_at' => $targetExpiresAt,
                    'status' => TenantSubscription::STATUS_ACTIVE,
                    'platform_invoice_id' => $invoice->id,
                ]);
                break;

            case PlatformInvoice::SERVICE_SETUP_TRAINING:
                $serviceReqId = $invoice->metadata['service_request_id'] ?? null;
                if ($serviceReqId) {
                    $serviceReq = \App\Models\Platform\PlatformServiceRequest::find($serviceReqId);
                    if ($serviceReq && in_array($serviceReq->status, [
                        \App\Models\Platform\PlatformServiceRequest::STATUS_SCOPED,
                        \App\Models\Platform\PlatformServiceRequest::STATUS_SUBMITTED,
                        \App\Models\Platform\PlatformServiceRequest::STATUS_IN_REVIEW,
                    ])) {
                        $serviceReq->status = \App\Models\Platform\PlatformServiceRequest::STATUS_SCHEDULED;
                        $serviceReq->save();
                    }
                }
                break;

            default:
                // AD campaigns handle their own activation via AdCapacityAndScheduleService
                break;
        }
    }

    /**
     * Create raw invoice record.
     */
    protected function createInvoice(
        int $outletId,
        string $serviceType,
        float $amount,
        ?int $durationMonths,
        Carbon $expiresAt,
        ?User $user = null,
        ?array $metadata = null
    ): PlatformInvoice {
        $invoiceNo = 'INV-PLT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        return PlatformInvoice::create([
            'invoice_no' => $invoiceNo,
            'outlet_id' => $outletId,
            'created_by' => $user?->id,
            'service_type' => $serviceType,
            'package_duration_months' => $durationMonths,
            'amount' => $amount,
            'status' => PlatformInvoice::STATUS_UNPAID,
            'expires_at' => $expiresAt,
            'metadata' => $metadata,
        ]);
    }
}
