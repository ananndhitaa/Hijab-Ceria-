<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\AdCampaignRevision;
use App\Services\Platform\PlatformNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAdReviewController extends Controller
{
    public function __construct(
        protected PlatformNotificationService $notificationService
    ) {}

    /**
     * List campaigns requiring Super Admin review or moderation.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status', AdCampaign::STATUS_PENDING_REVIEW);
        $adType = $request->input('ad_type');
        $search = $request->input('search');
        $outletId = $request->input('outlet_id');

        $campaigns = AdCampaign::with(['outlet', 'product', 'creator'])
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->when($adType, fn($q) => $q->where('ad_type', $adType))
            ->when($outletId, fn($q) => $q->where('outlet_id', $outletId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('tagline', 'like', "%{$search}%")
                        ->orWhereHas('outlet', fn($out) => $out->where('outlet_name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $pendingCount = AdCampaign::where('status', AdCampaign::STATUS_PENDING_REVIEW)->count();
        $pendingRevisionsCount = AdCampaignRevision::where('status', AdCampaignRevision::STATUS_PENDING)->count();

        $counts = [
            'pending'   => $pendingCount,
            'approved'  => AdCampaign::where('status', AdCampaign::STATUS_APPROVED)->count(),
            'active'    => AdCampaign::where('status', AdCampaign::STATUS_ACTIVE)->count(),
            'rejected'  => AdCampaign::where('status', AdCampaign::STATUS_REJECTED)->count(),
            'all'       => AdCampaign::count(),
        ];

        // Synchronized moderation priorities (Sum always equals pendingCount)
        $highPriorityCount = AdCampaign::where('status', AdCampaign::STATUS_PENDING_REVIEW)
            ->where(function ($q) {
                $q->where('total_budget', '>=', 500000)
                  ->orWhere('effective_bid_per_day', '>', 0);
            })->count();

        $mediumPriorityCount = AdCampaign::where('status', AdCampaign::STATUS_PENDING_REVIEW)
            ->where('ad_type', AdCampaign::TYPE_SHOP_AD)
            ->where(function ($q) {
                $q->where('total_budget', '<', 500000)
                  ->orWhereNull('total_budget');
            })->count();

        $normalPriorityCount = max(0, $pendingCount - $highPriorityCount - $mediumPriorityCount);

        $priorities = [
            'high'   => $highPriorityCount,
            'medium' => $mediumPriorityCount,
            'normal' => $normalPriorityCount,
        ];

        // Recent moderation activity logs
        $recentActivities = AdCampaign::with('outlet')
            ->whereNotNull('reviewed_at')
            ->latest('reviewed_at')
            ->take(4)
            ->get();

        if ($recentActivities->isEmpty()) {
            $recentActivities = AdCampaign::with('outlet')
                ->latest('updated_at')
                ->take(4)
                ->get();
        }

        $outlets = \App\Models\System\Outlet::where('status', 'ACTIVE')->orderBy('outlet_name')->get(['id', 'outlet_name']);

        return view('platform.ads.index', compact(
            'campaigns',
            'status',
            'adType',
            'pendingCount',
            'pendingRevisionsCount',
            'counts',
            'outlets',
            'search',
            'outletId',
            'priorities',
            'recentActivities'
        ));
    }

    /**
     * Show campaign review screen.
     */
    public function show(AdCampaign $campaign): View
    {
        $campaign->load(['outlet', 'product', 'creator', 'revisions.reviewer', 'invoice']);

        return view('platform.ads.show', compact('campaign'));
    }

    /**
     * Super Admin review decision on ad material.
     */
    public function review(Request $request, AdCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:APPROVE,NEEDS_REVISION,REJECT'],
            'notes' => ['nullable', 'required_if:decision,NEEDS_REVISION,REJECT', 'string', 'max:1000'],
        ]);

        $statusMap = [
            'APPROVE' => AdCampaign::STATUS_APPROVED,
            'NEEDS_REVISION' => AdCampaign::STATUS_NEEDS_REVISION,
            'REJECT' => AdCampaign::STATUS_REJECTED,
        ];

        $campaign->update([
            'status' => $statusMap[$validated['decision']],
            'review_notes' => $validated['notes'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Send in-app notification to tenant
        $this->notificationService->notify(
            outletId: $campaign->outlet_id,
            userId: $campaign->created_by,
            type: 'AD_REVIEW_DECISION',
            title: "Keputusan Peninjauan Iklan: {$campaign->title}",
            message: "Status materi iklan Anda diubah menjadi {$campaign->status}." . ($validated['notes'] ? " Catatan: {$validated['notes']}" : ''),
            actionUrl: route('owner.ads.show', $campaign->id, false)
        );

        return redirect()->route('platform.ads.show', $campaign->id)
            ->with('success', "Keputusan peninjauan ({$validated['decision']}) berhasil disimpan.");
    }

    /**
     * Super Admin review decision on creative revision.
     */
    public function reviewRevision(Request $request, AdCampaignRevision $revision): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:APPROVE,REJECT'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $campaign = $revision->campaign;

        if ($validated['decision'] === 'APPROVE') {
            $revision->status = AdCampaignRevision::STATUS_APPROVED;
            $revision->review_notes = $validated['notes'] ?? null;
            $revision->reviewed_by = auth()->id();
            $revision->reviewed_at = now();
            $revision->save();

            // Apply approved revision content to live campaign
            $campaign->title = $revision->title;
            $campaign->tagline = $revision->tagline;
            $campaign->banner_path = $revision->banner_path;

            // Only creative revisions consume revision quota (Section T)
            if ($revision->revision_type === AdCampaignRevision::TYPE_CREATIVE) {
                $campaign->creative_revision_count++;
            }

            // Restore campaign status to ACTIVE or SCHEDULED
            $now = now();
            $campaign->status = ($campaign->starts_at && $now->greaterThanOrEqualTo($campaign->starts_at) && $now->lessThan($campaign->ends_at))
                ? AdCampaign::STATUS_ACTIVE
                : AdCampaign::STATUS_SCHEDULED;

            $campaign->save();
        } else {
            $revision->status = AdCampaignRevision::STATUS_REJECTED;
            $revision->review_notes = $validated['notes'] ?? null;
            $revision->reviewed_by = auth()->id();
            $revision->reviewed_at = now();
            $revision->save();

            // Restore campaign status
            $campaign->status = AdCampaign::STATUS_ACTIVE;
            $campaign->save();
        }

        $this->notificationService->notify(
            outletId: $campaign->outlet_id,
            userId: $campaign->created_by,
            type: 'AD_REVISION_DECISION',
            title: "Keputusan Revisi Iklan: {$campaign->title}",
            message: "Pengajuan revisi materi iklan {$validated['decision']}." . ($validated['notes'] ? " Catatan: {$validated['notes']}" : ''),
            actionUrl: route('owner.ads.show', $campaign->id, false)
        );

        return redirect()->route('platform.ads.show', $campaign->id)
            ->with('success', "Keputusan revisi materi ({$validated['decision']}) berhasil disimpan.");
    }

    /**
     * Suspend a running ad campaign due to misleading content or violation.
     */
    public function suspend(Request $request, AdCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $campaign->update([
            'status' => AdCampaign::STATUS_SUSPENDED,
            'review_notes' => "DITANGGUHKAN: {$validated['reason']}",
        ]);

        $this->notificationService->notify(
            outletId: $campaign->outlet_id,
            userId: $campaign->created_by,
            type: 'AD_SUSPENDED',
            title: "Penayangan Iklan Ditangguhkan: {$campaign->title}",
            message: "Iklan Anda ditangguhkan dari penayangan publik karena: {$validated['reason']}",
            actionUrl: route('owner.ads.show', $campaign->id, false)
        );

        return back()->with('warning', "Penayangan iklan berhasil ditangguhkan.");
    }
}
