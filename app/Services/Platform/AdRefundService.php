<?php

namespace App\Services\Platform;

use App\Models\Platform\AdCampaign;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformRefund;
use App\Models\System\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdRefundService
{
    /**
     * Request campaign cancellation and process refund qualification based on Section U rules.
     *
     * @throws ValidationException
     */
    public function requestCancellation(
        AdCampaign $campaign,
        string $reason,
        array $bankDetails,
        ?User $user = null
    ): array {
        return DB::transaction(function () use ($campaign, $reason, $bankDetails) {
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

            if (in_array($lockedCampaign->status, [AdCampaign::STATUS_CANCELLED, AdCampaign::STATUS_COMPLETED])) {
                throw ValidationException::withMessages([
                    'campaign' => ['Kampanye ini sudah dibatalkan atau selesai.'],
                ]);
            }

            $invoice = $lockedCampaign->invoice;
            $hasPaid = $invoice && $invoice->isVerified();
            $paidAmount = $hasPaid ? (float) $invoice->amount : 0.0;

            $hasAired = $lockedCampaign->hasAired() || $lockedCampaign->status === AdCampaign::STATUS_ACTIVE;

            // Section U: If cancelled AFTER airing begins -> 0% refund
            // If cancelled BEFORE airing begins -> 100% refund
            $refundAmount = 0.0;
            $refundType = PlatformRefund::TYPE_FULL_100_PERCENT;

            if ($hasPaid) {
                if ($hasAired) {
                    $refundAmount = 0.0;
                } else {
                    $refundAmount = $paidAmount;
                }
            }

            $lockedCampaign->status = AdCampaign::STATUS_CANCELLED;
            $lockedCampaign->save();

            $refundRecord = null;
            if ($refundAmount > 0) {
                $refundRecord = PlatformRefund::create([
                    'outlet_id' => $lockedCampaign->outlet_id,
                    'platform_invoice_id' => $invoice->id,
                    'ad_campaign_id' => $lockedCampaign->id,
                    'refund_type' => $refundType,
                    'amount' => $refundAmount,
                    'reason' => $reason,
                    'status' => PlatformRefund::STATUS_REQUESTED,
                    'destination_bank_name' => $bankDetails['bank_name'] ?? 'BCA',
                    'destination_account_number' => $bankDetails['account_number'] ?? '000000',
                    'destination_account_name' => $bankDetails['account_name'] ?? 'Owner Toko',
                ]);
            }

            return [
                'campaign' => $lockedCampaign,
                'refund' => $refundRecord,
                'refund_amount' => $refundAmount,
                'eligible_for_refund' => $refundAmount > 0,
            ];
        });
    }

    /**
     * Calculate and issue proportional compensation for NAYARA-caused service failure (Section U).
     */
    public function issueProportionalCompensation(
        AdCampaign $campaign,
        int $lostSeconds,
        string $incidentReason,
        array $bankDetails,
        User $admin
    ): PlatformRefund {
        $invoice = $campaign->invoice;
        if (! $invoice || ! $invoice->isVerified()) {
            throw ValidationException::withMessages([
                'compensation' => ['Hanya kampanye dengan pembayaran terverifikasi yang dapat menerima kompensasi.'],
            ]);
        }

        $totalPurchasedSeconds = max(1, $campaign->duration_days * 86400);
        $verifiedPayment = (float) $invoice->amount;

        // Formula: verified_payment * actual_unfulfilled_duration / purchased_duration
        $unfulfilledRatio = min(1.0, max(0.0, $lostSeconds / $totalPurchasedSeconds));
        $refundAmount = round($verifiedPayment * $unfulfilledRatio);

        // Prevent over-refund across cumulative downtime claims
        $priorRefunds = PlatformRefund::where('platform_invoice_id', $invoice->id)
            ->whereIn('status', [PlatformRefund::STATUS_REQUESTED, PlatformRefund::STATUS_APPROVED, PlatformRefund::STATUS_COMPLETED])
            ->sum('amount');

        $maxRefundable = max(0.0, $verifiedPayment - $priorRefunds);
        if ($maxRefundable <= 0) {
            throw ValidationException::withMessages([
                'compensation' => ['Total dana pembayaran invoice ini sudah sepenuhnya direfund atau dalam proses refund.'],
            ]);
        }

        $refundAmount = min((float) $refundAmount, (float) $maxRefundable);

        if ($refundAmount <= 0) {
            throw ValidationException::withMessages([
                'compensation' => ['Nilai kompensasi terhitung 0 rupiah.'],
            ]);
        }

        return PlatformRefund::create([
            'outlet_id' => $campaign->outlet_id,
            'platform_invoice_id' => $invoice->id,
            'ad_campaign_id' => $campaign->id,
            'refund_type' => PlatformRefund::TYPE_PROPORTIONAL,
            'amount' => $refundAmount,
            'reason' => "Kompensasi gangguan layanan NAYARA ({$incidentReason}) - Hilang {$lostSeconds} detik.",
            'status' => PlatformRefund::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'destination_bank_name' => $bankDetails['bank_name'],
            'destination_account_number' => $bankDetails['account_number'],
            'destination_account_name' => $bankDetails['account_name'],
        ]);
    }

    /**
     * Super Admin approves refund request.
     */
    public function approveRefund(PlatformRefund $refund, User $admin): PlatformRefund
    {
        if ($refund->status !== PlatformRefund::STATUS_REQUESTED) {
            throw ValidationException::withMessages([
                'refund' => ["Status refund ({$refund->status}) tidak dapat disetujui."],
            ]);
        }

        $refund->status = PlatformRefund::STATUS_APPROVED;
        $refund->approved_by = $admin->id;
        $refund->approved_at = now();
        $refund->save();

        return $refund;
    }

    /**
     * Super Admin records settlement evidence and marks refund as completed.
     * Idempotent: cannot settle twice.
     */
    public function completeRefund(PlatformRefund $refund, string $transferProofPath, User $admin): PlatformRefund
    {
        return DB::transaction(function () use ($refund, $transferProofPath, $admin) {
            $lockedRefund = PlatformRefund::where('id', $refund->id)->lockForUpdate()->first();

            if ($lockedRefund->status === PlatformRefund::STATUS_COMPLETED) {
                return $lockedRefund;
            }

            if (! in_array($lockedRefund->status, [PlatformRefund::STATUS_REQUESTED, PlatformRefund::STATUS_APPROVED])) {
                throw ValidationException::withMessages([
                    'refund' => ["Status refund ({$lockedRefund->status}) tidak dapat diselesaikan."],
                ]);
            }

            $lockedRefund->status = PlatformRefund::STATUS_COMPLETED;
            $lockedRefund->transfer_proof_path = $transferProofPath;
            $lockedRefund->completed_by = $admin->id;
            $lockedRefund->completed_at = now();
            $lockedRefund->save();

            // Mark invoice as REFUNDED if fully refunded
            if ($lockedRefund->invoice && $lockedRefund->refund_type === PlatformRefund::TYPE_FULL_100_PERCENT) {
                $lockedRefund->invoice->status = PlatformInvoice::STATUS_REFUNDED;
                $lockedRefund->invoice->save();
            }

            return $lockedRefund;
        });
    }
}
