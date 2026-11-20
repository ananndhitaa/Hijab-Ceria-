<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Master\Product;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\AdCampaignRevision;
use App\Services\Platform\AdCapacityAndScheduleService;
use App\Services\Platform\AdRankingAndDistributionService;
use App\Services\Platform\AdRefundService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OwnerAdCampaignController extends Controller
{
    public function __construct(
        protected AdCapacityAndScheduleService $scheduleService,
        protected AdRankingAndDistributionService $rankingService,
        protected AdRefundService $refundService
    ) {}

    /**
     * List all ad campaigns for the current outlet.
     */
    public function index(Request $request): View
    {
        $outletId = auth()->user()->outlet_id;

        $typeFilter = $request->input('type');
        $statusFilter = $request->input('status');

        $campaigns = AdCampaign::with(['product', 'invoice'])
            ->where('outlet_id', $outletId)
            ->when($typeFilter, fn($q) => $q->where('ad_type', $typeFilter))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $pageTitle = 'Iklan & Promosi';
        $breadcrumb = 'Executive Monitoring > Iklan & Promosi';

        // Genuine metrics for current tenant
        $totalCount = AdCampaign::where('outlet_id', $outletId)->count();
        $shopAdsCount = AdCampaign::where('outlet_id', $outletId)->where('ad_type', AdCampaign::TYPE_SHOP_AD)->count();
        $productAdsCount = AdCampaign::where('outlet_id', $outletId)->where('ad_type', AdCampaign::TYPE_PRODUCT_AD)->count();
        $activeCount = AdCampaign::where('outlet_id', $outletId)->where('status', AdCampaign::STATUS_ACTIVE)->count();
        $pendingCount = AdCampaign::where('outlet_id', $outletId)->whereIn('status', [
            AdCampaign::STATUS_PENDING_REVIEW,
            AdCampaign::STATUS_APPROVED,
            AdCampaign::STATUS_PAYMENT_PENDING,
            AdCampaign::STATUS_SCHEDULED,
        ])->count();
        $totalInvested = (float) AdCampaign::where('outlet_id', $outletId)
            ->whereIn('status', [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_COMPLETED])
            ->sum('total_budget');

        $publishedProducts = Product::where('outlet_id', $outletId)
            ->where('is_published', true)
            ->get();

        return view('owner.ads.index', compact(
            'campaigns',
            'typeFilter',
            'statusFilter',
            'pageTitle',
            'breadcrumb',
            'totalCount',
            'shopAdsCount',
            'productAdsCount',
            'activeCount',
            'pendingCount',
            'totalInvested',
            'publishedProducts'
        ));
    }

    /**
     * Form to create a new ad material.
     */
    public function create(): View
    {
        $outletId = auth()->user()->outlet_id;

        $publishedProducts = Product::where('outlet_id', $outletId)
            ->where('is_published', true)
            ->get();

        $pageTitle = 'Buat Iklan Baru';
        $breadcrumb = 'Executive Monitoring > Iklan & Promosi > Buat Iklan';

        return view('owner.ads.create', compact('publishedProducts', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Store new ad campaign material.
     */
    public function store(Request $request): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        $validated = $request->validate([
            'ad_type' => ['required', 'in:SHOP_AD,PRODUCT_AD'],
            'product_id' => ['nullable', 'required_if:ad_type,PRODUCT_AD', 'integer', 'exists:products,id'],
            'title' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'banner_file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($validated['ad_type'] === AdCampaign::TYPE_PRODUCT_AD) {
            $product = Product::where('id', $validated['product_id'])
                ->where('outlet_id', $outletId)
                ->where('is_published', true)
                ->firstOrFail();
        }

        $bannerPath = $request->file('banner_file')->store('ad_banners', 'public');

        $campaign = AdCampaign::create([
            'outlet_id' => $outletId,
            'created_by' => auth()->id(),
            'ad_type' => $validated['ad_type'],
            'product_id' => $validated['product_id'] ?? null,
            'title' => $validated['title'],
            'tagline' => $validated['tagline'] ?? null,
            'banner_path' => $bannerPath,
            'status' => AdCampaign::STATUS_PENDING_REVIEW, // Automatically submitted for Super Admin review
        ]);

        return redirect()->route('owner.ads.show', $campaign->id)
            ->with('success', 'Materi iklan berhasil disimpan dan diajukan untuk peninjauan Super Admin.');
    }

    /**
     * View campaign details, review status, booking, or schedule preview.
     */
    public function show(AdCampaign $campaign): View
    {
        $outletId = auth()->user()->outlet_id;

        if ($campaign->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $campaign->load(['product', 'invoice', 'revisions.reviewer']);

        $competitorPreview = [];
        if ($campaign->starts_at && $campaign->ends_at) {
            $competitorPreview = $this->rankingService->getCompetitorPreview(
                adType: $campaign->ad_type,
                startsAt: $campaign->starts_at,
                endsAt: $campaign->ends_at,
                ownOutletId: $outletId
            );
        }

        $pageTitle = 'Detail Iklan';
        $breadcrumb = 'Executive Monitoring > Iklan & Promosi > Detail';

        return view('owner.ads.show', compact('campaign', 'competitorPreview', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Book schedule and issue 2-hour invoice for an approved campaign.
     */
    public function bookSchedule(Request $request, AdCampaign $campaign): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        if ($campaign->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'total_budget' => ['required', 'numeric', 'min:10000'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date', 'after:now'],
        ]);

        $startsAt = Carbon::parse($validated['starts_at'], 'Asia/Jakarta');

        try {
            $invoice = $this->scheduleService->reserveAndCreateInvoice(
                campaign: $campaign,
                totalBudget: (float) $validated['total_budget'],
                durationDays: (int) $validated['duration_days'],
                startsAt: $startsAt,
                user: auth()->user()
            );

            return redirect()->route('owner.subscriptions.invoice', $invoice->id)
                ->with('success', 'Kapasitas slot iklan berhasil direservasi. Silakan selesaikan pembayaran dalam 2 jam.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reschedule campaign before first airing (max 2 successful changes).
     */
    public function reschedule(Request $request, AdCampaign $campaign): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        if ($campaign->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'new_starts_at' => ['required', 'date', 'after:now'],
        ]);

        $newStartsAt = Carbon::parse($validated['new_starts_at'], 'Asia/Jakarta');

        try {
            $this->scheduleService->reschedule($campaign, $newStartsAt);

            return back()->with('success', 'Jadwal tayang iklan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Submit creative revision (max 2 successful approved revisions).
     */
    public function submitRevision(Request $request, AdCampaign $campaign): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        if ($campaign->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($campaign->creative_revision_count >= 2) {
            return back()->with('error', 'Batas maksimal 2 kali revisi materi kreatif telah tercapai.');
        }

        $validated = $request->validate([
            'revision_type' => ['required', 'in:CREATIVE,FACTUAL'],
            'title' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'banner_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $bannerPath = $campaign->banner_path;
        if ($request->hasFile('banner_file')) {
            $bannerPath = $request->file('banner_file')->store('ad_banners', 'public');
        }

        AdCampaignRevision::create([
            'ad_campaign_id' => $campaign->id,
            'title' => $validated['title'],
            'tagline' => $validated['tagline'] ?? null,
            'banner_path' => $bannerPath,
            'revision_type' => $validated['revision_type'],
            'status' => AdCampaignRevision::STATUS_PENDING,
        ]);

        $campaign->status = AdCampaign::STATUS_PENDING_REVIEW;
        $campaign->save();

        return back()->with('success', 'Revisi materi iklan berhasil diajukan untuk peninjauan.');
    }

    /**
     * Cancel campaign and process refund qualification based on Section U rules.
     */
    public function cancel(Request $request, AdCampaign $campaign): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        if ($campaign->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'bank_name' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:100'],
        ]);

        try {
            $result = $this->refundService->requestCancellation(
                campaign: $campaign,
                reason: $validated['reason'],
                bankDetails: [
                    'bank_name' => $validated['bank_name'],
                    'account_number' => $validated['account_number'],
                    'account_name' => $validated['account_name'],
                ],
                user: auth()->user()
            );

            if ($result['eligible_for_refund']) {
                return redirect()->route('owner.ads.show', $campaign->id)
                    ->with('success', 'Kampanye berhasil dibatalkan sebelum tayang. Pengajuan refund 100% sebesar Rp ' . number_format($result['refund_amount'], 0, ',', '.') . ' telah didaftarkan.');
            }

            return redirect()->route('owner.ads.show', $campaign->id)
                ->with('warning', 'Kampanye telah dibatalkan. Sesuai kebijakan Section U, pembatalan setelah tayang tidak berhak mendapatkan refund.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
