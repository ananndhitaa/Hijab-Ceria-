<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformRefund;
use App\Services\Platform\AdRefundService;
use App\Services\Platform\PlatformNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformRefundReviewController extends Controller
{
    public function __construct(
        protected AdRefundService $refundService,
        protected PlatformNotificationService $notificationService
    ) {}

    /**
     * List all platform refund requests.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $search = $request->input('search');
        $period = $request->input('period');

        $query = PlatformRefund::with(['outlet', 'invoice', 'campaign']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('destination_account_name', 'like', "%{$search}%")
                  ->orWhere('destination_account_number', 'like', "%{$search}%")
                  ->orWhere('destination_bank_name', 'like', "%{$search}%")
                  ->orWhereHas('outlet', fn($out) => $out->where('outlet_name', 'like', "%{$search}%"));
                if (is_numeric($search) || str_starts_with(strtoupper($search), 'REF-')) {
                    $cleanId = (int) str_ireplace('REF-', '', $search);
                    if ($cleanId > 0) {
                        $q->orWhere('id', $cleanId);
                    }
                }
            });
        }

        if ($period === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($period === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($period === '90d') {
            $query->where('created_at', '>=', now()->subDays(90));
        }

        $refunds = $query->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'all' => PlatformRefund::count(),
            'requested' => PlatformRefund::where('status', PlatformRefund::STATUS_REQUESTED)->count(),
            'approved' => PlatformRefund::where('status', PlatformRefund::STATUS_APPROVED)->count(),
            'completed' => PlatformRefund::where('status', PlatformRefund::STATUS_COMPLETED)->count(),
            'rejected' => PlatformRefund::where('status', PlatformRefund::STATUS_REJECTED)->count(),
            'total_refunded' => (float) PlatformRefund::where('status', PlatformRefund::STATUS_COMPLETED)->sum('amount'),
        ];

        return view('platform.refunds.index', compact('refunds', 'stats', 'status', 'search', 'period'));
    }

    /**
     * Show single refund details.
     */
    public function show(PlatformRefund $refund): View
    {
        $refund->load(['outlet', 'invoice', 'campaign', 'approver', 'completer']);

        return view('platform.refunds.show', compact('refund'));
    }

    /**
     * Approve a refund request.
     */
    public function approve(PlatformRefund $refund): RedirectResponse
    {
        try {
            $this->refundService->approveRefund($refund, auth()->user());

            $this->notificationService->notify(
                outletId: $refund->outlet_id,
                userId: null,
                type: 'REFUND_APPROVED',
                title: 'Pengajuan Refund Disetujui',
                message: "Pengajuan refund sebesar Rp " . number_format($refund->amount, 0, ',', '.') . " telah disetujui dan dalam proses transfer pencairan.",
                actionUrl: null
            );

            return back()->with('success', 'Pengajuan refund berhasil disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record settlement transfer proof and complete refund.
     */
    public function complete(Request $request, PlatformRefund $refund): RedirectResponse
    {
        $request->validate([
            'transfer_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:3072'],
        ]);

        $proofPath = $request->file('transfer_proof')->store('refund_proofs', 'public');

        try {
            $this->refundService->completeRefund($refund, $proofPath, auth()->user());

            $this->notificationService->notify(
                outletId: $refund->outlet_id,
                userId: null,
                type: 'REFUND_COMPLETED',
                title: 'Pencairan Refund Selesai',
                message: "Dana refund sebesar Rp " . number_format($refund->amount, 0, ',', '.') . " telah ditransfer ke rekening {$refund->destination_bank_name} - {$refund->destination_account_number}.",
                actionUrl: null
            );

            return back()->with('success', 'Refund berhasil diselesaikan dengan bukti transfer yang tercatat.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
