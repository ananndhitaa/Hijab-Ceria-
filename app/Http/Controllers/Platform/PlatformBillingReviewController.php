<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformInvoice;
use App\Services\Platform\AdCapacityAndScheduleService;
use App\Services\Platform\PlatformBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformBillingReviewController extends Controller
{
    public function __construct(
        protected PlatformBillingService $billingService,
        protected AdCapacityAndScheduleService $adScheduleService
    ) {}

    /**
     * List all platform invoices for Super Admin review.
     */
    public function index(Request $request): View
    {
        $status      = $request->input('status');
        $serviceType = $request->input('service_type');
        $search      = trim((string) $request->input('search'));
        $sort        = $request->input('sort', 'latest');

        $query = PlatformInvoice::with(['outlet', 'creator']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhereHas('outlet', function ($oq) use ($search) {
                      $oq->where('outlet_name', 'like', "%{$search}%")
                         ->orWhere('outlet_code', 'like', "%{$search}%")
                         ->orWhere('slug', 'like', "%{$search}%");
                  });

                $cleanNum = str_replace(['.', ',', ' '], '', $search);
                if (is_numeric($cleanNum)) {
                    $q->orWhere('amount', (float) $cleanNum);
                }
            });
        }

        if (!empty($status)) {
            if ($status === 'REJECTED') {
                $query->where(function ($q) {
                    $q->where('status', 'REJECTED')
                      ->orWhereNotNull('metadata->rejection_notes');
                });
            } else {
                $query->where('status', $status);
            }
        }

        if (!empty($serviceType)) {
            $query->where('service_type', $serviceType);
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('id', 'asc');
                break;
            case 'amount_desc':
                $query->orderByDesc('amount')->orderByDesc('id');
                break;
            case 'amount_asc':
                $query->orderBy('amount', 'asc')->orderByDesc('id');
                break;
            case 'latest':
            default:
                $query->orderByDesc('id');
                break;
        }

        $invoices = $query->paginate(15)->withQueryString();

        $counts = [
            'total'                 => PlatformInvoice::count(),
            'pending'               => PlatformInvoice::where('status', PlatformInvoice::STATUS_PAYMENT_SUBMITTED)->count(),
            'verified'              => PlatformInvoice::where('status', PlatformInvoice::STATUS_VERIFIED)->count(),
            'unpaid'                => PlatformInvoice::where('status', PlatformInvoice::STATUS_UNPAID)->count(),
            'rejected'              => PlatformInvoice::where(function ($q) {
                $q->where('status', 'REJECTED')
                  ->orWhereNotNull('metadata->rejection_notes');
            })->count(),
            'total_verified_amount' => (float) PlatformInvoice::where('status', PlatformInvoice::STATUS_VERIFIED)->sum('amount'),
        ];

        return view('platform.billing.index', compact('invoices', 'counts', 'status', 'serviceType', 'search', 'sort'));
    }

    /**
     * Show single invoice verification screen.
     */
    public function show(PlatformInvoice $invoice): View
    {
        $invoice->load(['outlet', 'creator', 'verifier']);

        return view('platform.billing.show', compact('invoice'));
    }

    /**
     * Idempotently verify payment and activate service entitlement.
     */
    public function verify(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        try {
            $this->billingService->verifyPayment($invoice, auth()->user());

            // If it's an ad invoice, schedule or activate the campaign
            if (in_array($invoice->service_type, [PlatformInvoice::SERVICE_SHOP_AD, PlatformInvoice::SERVICE_PRODUCT_AD])) {
                $this->adScheduleService->verifyAndScheduleCampaign($invoice, auth()->user());
            }

            return redirect()->route('platform.billing.show', $invoice->id)
                ->with('success', "Pembayaran invoice {$invoice->invoice_no} berhasil diverifikasi. Hak layanan telah diaktifkan.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject submitted payment proof.
     */
    public function reject(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        if ($invoice->isVerified()) {
            return back()->with('error', 'Invoice yang sudah diverifikasi tidak dapat ditolak.');
        }

        $invoice->update([
            'status' => PlatformInvoice::STATUS_UNPAID, // Return to UNPAID so tenant can re-submit
            'metadata' => array_merge($invoice->metadata ?? [], ['rejection_notes' => $validated['notes']]),
        ]);

        return back()->with('warning', "Bukti transfer ditolak. Catatan: {$validated['notes']}");
    }
}
