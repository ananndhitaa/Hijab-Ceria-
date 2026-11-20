<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformServiceRequest;
use App\Services\Platform\PlatformBillingService;
use App\Services\Platform\PlatformNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformServiceManagementController extends Controller
{
    public function __construct(
        protected PlatformNotificationService $notificationService,
        protected PlatformBillingService $billingService
    ) {}

    /**
     * List all setup and training requests.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $serviceType = $request->input('service_type');
        $search = $request->input('search');

        $requests = PlatformServiceRequest::with(['outlet', 'creator'])
            ->when($status, function ($q) use ($status) {
                if ($status === 'NEW' || $status === 'SUBMITTED') {
                    $q->whereIn('status', [PlatformServiceRequest::STATUS_SUBMITTED, PlatformServiceRequest::STATUS_IN_REVIEW]);
                } elseif ($status === 'SCHEDULED') {
                    $q->whereIn('status', [PlatformServiceRequest::STATUS_SCOPED, PlatformServiceRequest::STATUS_SCHEDULED]);
                } elseif ($status === 'IN_PROGRESS') {
                    $q->where('status', PlatformServiceRequest::STATUS_IN_PROGRESS);
                } elseif ($status === 'COMPLETED') {
                    $q->where('status', PlatformServiceRequest::STATUS_COMPLETED);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($serviceType, fn($q) => $q->where('service_type', $serviceType))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('service_type', 'like', "%{$search}%")
                        ->orWhere('scope_details', 'like', "%{$search}%")
                        ->orWhereHas('outlet', fn($o) => $o->where('outlet_name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $countNew = PlatformServiceRequest::whereIn('status', [PlatformServiceRequest::STATUS_SUBMITTED, PlatformServiceRequest::STATUS_IN_REVIEW])->count();
        $countScheduled = PlatformServiceRequest::whereIn('status', [PlatformServiceRequest::STATUS_SCOPED, PlatformServiceRequest::STATUS_SCHEDULED])->count();
        $countInProgress = PlatformServiceRequest::where('status', PlatformServiceRequest::STATUS_IN_PROGRESS)->count();
        $countCompleted = PlatformServiceRequest::where('status', PlatformServiceRequest::STATUS_COMPLETED)->count();
        $totalRequests = PlatformServiceRequest::count();

        $metrics = [
            'new' => $countNew,
            'scheduled' => $countScheduled,
            'in_progress' => $countInProgress,
            'completed' => $countCompleted,
            'total' => $totalRequests,
            'new_pct' => $totalRequests > 0 ? (int) round(($countNew / $totalRequests) * 100) : 0,
            'scheduled_pct' => $totalRequests > 0 ? (int) round(($countScheduled / $totalRequests) * 100) : 0,
            'in_progress_pct' => $totalRequests > 0 ? (int) round(($countInProgress / $totalRequests) * 100) : 0,
            'completed_pct' => $totalRequests > 0 ? (int) round(($countCompleted / $totalRequests) * 100) : 0,
        ];

        $recentActivities = PlatformServiceRequest::with(['outlet'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('platform.services.index', compact(
            'requests',
            'status',
            'serviceType',
            'search',
            'metrics',
            'recentActivities'
        ));
    }

    /**
     * Show single service request details.
     */
    public function show(PlatformServiceRequest $serviceRequest): View
    {
        $serviceRequest->load(['outlet', 'creator']);

        return view('platform.services.show', compact('serviceRequest'));
    }

    /**
     * Update request status and custom quotation.
     */
    public function update(Request $request, PlatformServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:SUBMITTED,IN_REVIEW,SCOPED,SCHEDULED,IN_PROGRESS,COMPLETED,CANCELLED'],
            'custom_quote_amount' => ['nullable', 'numeric', 'min:0'],
            'super_admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $serviceRequest->update([
            'status' => $validated['status'],
            'custom_quote_amount' => $validated['custom_quote_amount'] ?? null,
            'super_admin_notes' => $validated['super_admin_notes'] ?? null,
        ]);

        if (!empty($validated['custom_quote_amount']) && (float) $validated['custom_quote_amount'] > 0 && in_array($validated['status'], ['SCOPED', 'SCHEDULED'])) {
            $existingInvoice = PlatformInvoice::where('service_type', PlatformInvoice::SERVICE_SETUP_TRAINING)
                ->where('metadata->service_request_id', $serviceRequest->id)
                ->first();

            if (! $existingInvoice) {
                $this->billingService->createServiceInvoice($serviceRequest, (float) $validated['custom_quote_amount'], auth()->user());
            }
        }

        $this->notificationService->notify(
            outletId: $serviceRequest->outlet_id,
            userId: $serviceRequest->created_by,
            type: 'SERVICE_REQUEST_UPDATE',
            title: 'Pembaruan Permintaan Layanan Implementasi',
            message: "Status permintaan layanan '{$serviceRequest->service_type}' Anda diperbarui menjadi {$serviceRequest->status}." . ($serviceRequest->custom_quote_amount ? " Estimasi penawaran: Rp " . number_format($serviceRequest->custom_quote_amount, 0, ',', '.') : ''),
            actionUrl: route('owner.services.index', [], false)
        );

        return back()->with('success', 'Status permintaan layanan berhasil diperbarui.');
    }
}
