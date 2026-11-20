<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Platform\OutletListingSlot;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\TenantSubscription;
use App\Services\Platform\PlatformBillingService;
use App\Services\Platform\TenantEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerSubscriptionController extends Controller
{
    public function __construct(
        protected PlatformBillingService $billingService,
        protected TenantEntitlementService $entitlementService
    ) {}

    /**
     * Display current subscriptions, slot capacity, and invoice history.
     */
    public function index(): View
    {
        $outletId = auth()->user()->outlet_id;

        $activePremium = $this->entitlementService->getActiveSubscription($outletId, TenantSubscription::TYPE_PREMIUM);
        $activeAddon = $this->entitlementService->getActiveSubscription($outletId, TenantSubscription::TYPE_ANALYTICS_ADDON);
        $purchasedSlots = (int) (OutletListingSlot::where('outlet_id', $outletId)->value('purchased_slots') ?? 0);
        $totalCapacity = $this->entitlementService->getListingCapacity($outletId);

        $invoices = PlatformInvoice::where('outlet_id', $outletId)
            ->latest('id')
            ->paginate(10);

        // Check if upgrade is eligible
        $upgradeQuote = null;
        if ($activeAddon && now()->diffInHours($activeAddon->expires_at, false) >= 24) {
            try {
                $upgradeQuote = $this->billingService->calculateUpgradeQuote($outletId);
            } catch (\Exception $e) {
                // Ignore if not eligible
            }
        }

        $pageTitle = 'Langganan & Slot';
        $breadcrumb = 'Executive Monitoring > Langganan & Slot';

        return view('owner.subscriptions.index', compact(
            'activePremium',
            'activeAddon',
            'purchasedSlots',
            'totalCapacity',
            'invoices',
            'upgradeQuote',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Order a Premium subscription.
     */
    public function storePremium(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'months' => ['required', 'integer', 'in:1,3,6,12'],
        ]);

        $outletId = auth()->user()->outlet_id;

        try {
            $invoice = $this->billingService->createPremiumInvoice(
                outletId: $outletId,
                months: (int) $validated['months'],
                user: auth()->user()
            );

            return redirect()->route('owner.subscriptions.invoice', $invoice->id)
                ->with('success', "Invoice paket Premium {$validated['months']} bulan berhasil dibuat. Silakan lakukan pembayaran.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Order a Financial Analytics Add-On subscription.
     */
    public function storeAnalyticsAddon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'months' => ['required', 'integer', 'in:1,3,6,12'],
        ]);

        $outletId = auth()->user()->outlet_id;

        try {
            $invoice = $this->billingService->createAnalyticsAddonInvoice(
                outletId: $outletId,
                months: (int) $validated['months'],
                user: auth()->user()
            );

            return redirect()->route('owner.subscriptions.invoice', $invoice->id)
                ->with('success', "Invoice Financial Analytics Add-on {$validated['months']} bulan berhasil dibuat. Silakan lakukan pembayaran.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Order permanent listing slots (+5 or +10).
     */
    public function storeListingSlots(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slots' => ['required', 'integer', 'in:5,10'],
        ]);

        $outletId = auth()->user()->outlet_id;

        try {
            $invoice = $this->billingService->createListingSlotsInvoice(
                outletId: $outletId,
                slots: (int) $validated['slots'],
                user: auth()->user()
            );

            return redirect()->route('owner.subscriptions.invoice', $invoice->id)
                ->with('success', "Invoice penambahan +{$validated['slots']} slot permanen berhasil dibuat. Silakan lakukan pembayaran.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Request an upgrade from active Analytics Add-On to Premium.
     */
    public function storeUpgrade(Request $request): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        try {
            $invoice = $this->billingService->createUpgradeInvoice(
                outletId: $outletId,
                user: auth()->user()
            );

            return redirect()->route('owner.subscriptions.invoice', $invoice->id)
                ->with('success', 'Invoice upgrade ke paket Premium berhasil dibuat. Silakan selesaikan pembayaran dalam 12 jam.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * View invoice details and payment upload page.
     */
    public function showInvoice(PlatformInvoice $invoice): View
    {
        $outletId = auth()->user()->outlet_id;

        if ($invoice->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        return view('owner.subscriptions.invoice', compact('invoice'));
    }

    /**
     * Submit proof of payment for an invoice.
     */
    public function submitPaymentProof(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        if ($invoice->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $request->validate([
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:3072'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $path = $request->file('proof_file')->store('platform_payments', 'public');

        try {
            $this->billingService->submitPaymentProof(
                invoice: $invoice,
                proofPath: $path,
                paymentMethod: $request->input('payment_method', 'BANK_TRANSFER')
            );

            return back()->with('success', 'Bukti transfer berhasil dikirim. Tim NAYARA akan memverifikasi pembayaran Anda.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
