<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformRefund;
use App\Models\Platform\TenantRegistration;
use App\Models\Platform\TenantRegistrationHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformAuditController extends Controller
{
    /**
     * Display registration and platform decision audit trail.
     */
    public function index(Request $request): View|StreamedResponse
    {
        // Handle CSV Export if requested
        if ($request->input('export') === 'csv') {
            $allHistories = TenantRegistrationHistory::with(['registration', 'actor'])->latest()->get();
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="nayara_platform_audit_log_' . now()->format('Ymd_His') . '.csv"',
            ];
            $callback = function () use ($allHistories) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'Waktu', 'Perusahaan', 'Pemohon', 'Aksi', 'Status Awal', 'Status Akhir', 'Aktor', 'Catatan']);
                foreach ($allHistories as $row) {
                    fputcsv($file, [
                        $row->id,
                        $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
                        $row->registration?->company_name ?? '-',
                        $row->registration?->owner_name ?? '-',
                        $row->action,
                        $row->from_status ?? '-',
                        $row->to_status,
                        $row->actor?->name ?? 'Sistem / Publik',
                        $row->notes ?? '-',
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }

        $query = TenantRegistrationHistory::with(['registration', 'actor'])->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('to_status', 'like', "%{$search}%")
                  ->orWhereHas('registration', function ($rq) use ($search) {
                      $rq->where('company_name', 'like', "%{$search}%")
                         ->orWhere('owner_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('actor', function ($aq) use ($search) {
                      $aq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            if ($status !== 'ALL') {
                $query->where('to_status', $status);
            }
        }

        $histories = $query->paginate(20)->withQueryString();

        // Calculate truthful stats directly from existing records
        $totalHistories = TenantRegistrationHistory::count();
        $pendingReviews = TenantRegistration::where('status', TenantRegistration::STATUS_PENDING)->count();
        $approvedCount = TenantRegistration::where('status', TenantRegistration::STATUS_APPROVED)->count();
        $rejectedOrRevision = TenantRegistration::whereIn('status', [
            TenantRegistration::STATUS_REJECTED,
            TenantRegistration::STATUS_NEEDS_REVISION,
        ])->count();

        $stats = [
            'total' => $totalHistories,
            'today_total' => $totalHistories,
            'pending' => $pendingReviews,
            'approved' => $approvedCount,
            'rejected_or_revision' => $rejectedOrRevision,
        ];

        return view('platform.audit.index', compact('histories', 'stats'));
    }
}
