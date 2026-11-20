<?php

namespace App\Services\Platform;

use App\Models\Master\Product;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\PlatformInvoice;
use App\Models\System\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdCapacityAndScheduleService
{
    public const MAX_SHOP_ADS = 5;
    public const MAX_PRODUCT_ADS = 6;
    public const MAX_RESCHEDULES = 2;

    /**
     * Check if a proposed schedule interval has capacity and does not violate uniqueness.
     * Uses half-open interval [startsAt, endsAt).
     *
     * @throws ValidationException
     */
    public function checkCapacity(
        string $adType,
        Carbon $startsAt,
        Carbon $endsAt,
        int $outletId,
        ?int $productId = null,
        ?int $excludeCampaignId = null
    ): void {
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw ValidationException::withMessages([
                'schedule' => ['Waktu selesai harus lebih besar dari waktu mulai.'],
            ]);
        }

        $now = now();
        $maxCapacity = $adType === AdCampaign::TYPE_SHOP_AD ? self::MAX_SHOP_ADS : self::MAX_PRODUCT_ADS;

        // Query overlapping active/scheduled campaigns and active reservations
        $overlappingQuery = AdCampaign::where('ad_type', $adType)
            ->where(function ($q) {
                $q->whereIn('status', [AdCampaign::STATUS_SCHEDULED, AdCampaign::STATUS_ACTIVE])
                  ->orWhere(function ($sub) {
                      $sub->where('status', AdCampaign::STATUS_PAYMENT_PENDING)
                          ->whereHas('invoice', function ($inv) {
                              $inv->where(function ($hold) {
                                  // Section R: 2h validity, or +24h hold if payment submitted
                                  $hold->where('status', PlatformInvoice::STATUS_UNPAID)
                                       ->where('expires_at', '>', now());
                              })->orWhere(function ($hold) {
                                  $hold->where('status', PlatformInvoice::STATUS_PAYMENT_SUBMITTED)
                                       ->where('expires_at', '>', now()->subHours(24));
                              });
                          });
                  });
            })
            ->where(function ($interval) use ($startsAt, $endsAt) {
                // Half-open overlap: starts_at < endsAt AND ends_at > startsAt
                $interval->where('starts_at', '<', $endsAt)
                         ->where('ends_at', '>', $startsAt);
            });

        if ($excludeCampaignId) {
            $overlappingQuery->where('id', '!=', $excludeCampaignId);
        }

        $overlapping = $overlappingQuery->get();

        // Check global capacity
        if ($overlapping->count() >= $maxCapacity) {
            throw ValidationException::withMessages([
                'capacity' => [
                    "Kapasitas iklan {$adType} penuh pada jadwal tersebut (maksimal {$maxCapacity} kampanye bersamaan). Silakan pilih jadwal lain.",
                ],
            ]);
        }

        // Check per-tenant uniqueness for Shop Ad (at most 1 overlapping Shop Ad per tenant)
        if ($adType === AdCampaign::TYPE_SHOP_AD) {
            $hasOwnOverlap = $overlapping->firstWhere('outlet_id', $outletId);
            if ($hasOwnOverlap) {
                throw ValidationException::withMessages([
                    'shop_ad' => ['Toko Anda sudah memiliki Iklan Toko aktif atau terjadwal pada rentang waktu yang sama.'],
                ]);
            }
        }

        // Check per-product uniqueness for Product Ad (same product cannot have overlapping campaigns)
        if ($adType === AdCampaign::TYPE_PRODUCT_AD && $productId) {
            $hasProductOverlap = $overlapping->firstWhere('product_id', $productId);
            if ($hasProductOverlap) {
                throw ValidationException::withMessages([
                    'product_ad' => ['Produk ini sudah memiliki Iklan Produk aktif atau terjadwal pada rentang waktu yang sama.'],
                ]);
            }
        }
    }

    /**
     * Atomically reserve schedule capacity and issue an ad invoice.
     * Ad invoices have exactly 2 hours validity.
     *
     * @throws ValidationException
     */
    public function reserveAndCreateInvoice(
        AdCampaign $campaign,
        float $totalBudget,
        int $durationDays,
        Carbon $startsAt,
        ?User $user = null
    ): PlatformInvoice {
        if (! in_array($campaign->status, [AdCampaign::STATUS_APPROVED, AdCampaign::STATUS_DRAFT])) {
            throw ValidationException::withMessages([
                'campaign' => ['Materi iklan harus berstatus DISETUJUI sebelum membuat reservasi jadwal.'],
            ]);
        }

        if ($totalBudget <= 0) {
            throw ValidationException::withMessages([
                'total_budget' => ['Total anggaran iklan harus lebih dari 0 rupiah.'],
            ]);
        }

        if ($durationDays < 1) {
            throw ValidationException::withMessages([
                'duration_days' => ['Durasi iklan minimal 1 hari (24 jam).'],
            ]);
        }

        try {
            $calculatedEndsAt = $startsAt->copy()->addDays($durationDays);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'duration_days' => ['Durasi iklan melebihi batas teknis penanggalan sistem.'],
            ]);
        }

        if ($calculatedEndsAt->year > 2099) {
            throw ValidationException::withMessages([
                'duration_days' => ['Durasi iklan melebihi batas teknis penanggalan sistem.'],
            ]);
        }

        if ($campaign->isProductAd()) {
            if (! $campaign->product_id) {
                throw ValidationException::withMessages([
                    'product_id' => ['Iklan produk harus memilih produk publik yang valid.'],
                ]);
            }
            $product = Product::where('id', $campaign->product_id)
                ->where('outlet_id', $campaign->outlet_id)
                ->where('is_published', true)
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk yang diiklankan harus berstatus aktif di etalase publik.'],
                ]);
            }
        }

        return DB::transaction(function () use ($campaign, $totalBudget, $durationDays, $startsAt, $user) {
            // Lock campaign
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

            // 1 ad day = exactly 24 hours
            $endsAt = $startsAt->copy()->addDays($durationDays);

            // Verify capacity atomically
            $this->checkCapacity(
                adType: $lockedCampaign->ad_type,
                startsAt: $startsAt,
                endsAt: $endsAt,
                outletId: $lockedCampaign->outlet_id,
                productId: $lockedCampaign->product_id,
                excludeCampaignId: $lockedCampaign->id
            );

            $effectiveBidPerDay = round($totalBudget / $durationDays, 2);

            // Create Platform Invoice with exactly 2-hour expiration (Section R)
            $serviceType = $lockedCampaign->isShopAd() ? PlatformInvoice::SERVICE_SHOP_AD : PlatformInvoice::SERVICE_PRODUCT_AD;
            $invoiceNo = 'INV-AD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

            $invoice = PlatformInvoice::create([
                'invoice_no' => $invoiceNo,
                'outlet_id' => $lockedCampaign->outlet_id,
                'created_by' => $user?->id,
                'service_type' => $serviceType,
                'package_duration_months' => null,
                'amount' => $totalBudget,
                'status' => PlatformInvoice::STATUS_UNPAID,
                'expires_at' => now()->addHours(2), // Exactly 2 hours
                'metadata' => [
                    'ad_campaign_id' => $lockedCampaign->id,
                    'duration_days' => $durationDays,
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $endsAt->toIso8601String(),
                ],
            ]);

            $lockedCampaign->update([
                'total_budget' => $totalBudget,
                'duration_days' => $durationDays,
                'effective_bid_per_day' => $effectiveBidPerDay,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AdCampaign::STATUS_PAYMENT_PENDING,
                'platform_invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });
    }

    /**
     * Verify payment and transition campaign to SCHEDULED or ACTIVE.
     */
    public function verifyAndScheduleCampaign(PlatformInvoice $invoice, User $verifier): AdCampaign
    {
        return DB::transaction(function () use ($invoice, $verifier) {
            $campaign = AdCampaign::where('platform_invoice_id', $invoice->id)->lockForUpdate()->first();

            if (! $campaign) {
                throw ValidationException::withMessages([
                    'campaign' => ['Kampanye iklan untuk invoice ini tidak ditemukan.'],
                ]);
            }

            $now = now();
            $status = ($now->greaterThanOrEqualTo($campaign->starts_at) && $now->lessThan($campaign->ends_at))
                ? AdCampaign::STATUS_ACTIVE
                : AdCampaign::STATUS_SCHEDULED;

            $campaign->update([
                'status' => $status,
                'verified_payment_at' => $now,
            ]);

            return $campaign;
        });
    }

    /**
     * Reschedule a campaign before first airing (max 2 successful changes).
     *
     * @throws ValidationException
     */
    public function reschedule(AdCampaign $campaign, Carbon $newStartsAt): AdCampaign
    {
        if ($campaign->hasAired() || $campaign->status === AdCampaign::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'reschedule' => ['Kampanye yang telah tayang tidak dapat diubah jadwalnya.'],
            ]);
        }

        if ($campaign->schedule_change_count >= self::MAX_RESCHEDULES) {
            throw ValidationException::withMessages([
                'reschedule' => ['Batas maksimal 2 kali perubahan jadwal telah tercapai.'],
            ]);
        }

        return DB::transaction(function () use ($campaign, $newStartsAt) {
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

            $newEndsAt = $newStartsAt->copy()->addDays($lockedCampaign->duration_days);

            // Atomically check capacity for new interval
            $this->checkCapacity(
                adType: $lockedCampaign->ad_type,
                startsAt: $newStartsAt,
                endsAt: $newEndsAt,
                outletId: $lockedCampaign->outlet_id,
                productId: $lockedCampaign->product_id,
                excludeCampaignId: $lockedCampaign->id
            );

            $lockedCampaign->starts_at = $newStartsAt;
            $lockedCampaign->ends_at = $newEndsAt;
            $lockedCampaign->schedule_change_count++;
            $lockedCampaign->save();

            return $lockedCampaign;
        });
    }

    /**
     * Release expired campaign reservations where invoice payment was not submitted on time.
     */
    public function releaseExpiredReservations(): int
    {
        $expiredCampaigns = AdCampaign::where('status', AdCampaign::STATUS_PAYMENT_PENDING)
            ->whereHas('invoice', function ($inv) {
                // Section R: 2h expiration if UNPAID, or +24h hold if PAYMENT_SUBMITTED
                $inv->where(function ($sub) {
                    $sub->where('status', PlatformInvoice::STATUS_UNPAID)
                        ->where('expires_at', '<=', now());
                })->orWhere(function ($sub) {
                    $sub->where('status', PlatformInvoice::STATUS_PAYMENT_SUBMITTED)
                        ->where('expires_at', '<=', now()->subHours(24));
                });
            })
            ->get();

        $releasedCount = 0;
        foreach ($expiredCampaigns as $camp) {
            DB::transaction(function () use ($camp, &$releasedCount) {
                $invoice = $camp->invoice;

                $camp->status = AdCampaign::STATUS_APPROVED; // Return to approved so tenant can re-book
                $camp->platform_invoice_id = null;
                $camp->save();

                if ($invoice) {
                    $invoice->status = PlatformInvoice::STATUS_EXPIRED;
                    $invoice->save();
                }

                $releasedCount++;
            });
        }

        return $releasedCount;
    }

    /**
     * Synchronize campaign statuses based on current time.
     */
    public function syncCampaignStatuses(): array
    {
        $now = now();

        // SCHEDULED -> ACTIVE
        $activated = AdCampaign::where('status', AdCampaign::STATUS_SCHEDULED)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->update(['status' => AdCampaign::STATUS_ACTIVE]);

        // ACTIVE -> COMPLETED
        $completed = AdCampaign::where('status', AdCampaign::STATUS_ACTIVE)
            ->where('ends_at', '<=', $now)
            ->update(['status' => AdCampaign::STATUS_COMPLETED]);

        return [
            'activated' => $activated,
            'completed' => $completed,
        ];
    }
}
