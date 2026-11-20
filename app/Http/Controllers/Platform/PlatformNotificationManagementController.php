<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\OwnerComplaint;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformNotification;
use App\Models\Platform\PlatformRefund;
use App\Models\Platform\TenantRegistration;
use App\Models\Platform\TenantRegistrationHistory;
use App\Models\System\Outlet;
use App\Services\Platform\PlatformNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformNotificationManagementController extends Controller
{
    public function __construct(
        protected PlatformNotificationService $notificationService
    ) {}

    /**
     * Display broadcast & notification management view.
     */
    public function index(): View
    {
        $notifications = PlatformNotification::with('outlet')
            ->latest('id')
            ->paginate(15);

        $outlets = Outlet::where('status', 'ACTIVE')->orderBy('outlet_name')->get();

        $stats = [
            'total'     => PlatformNotification::count(),
            'unread'    => PlatformNotification::where('is_read', false)->count(),
            'broadcast' => PlatformNotification::whereNull('outlet_id')->count(),
            'targeted'  => PlatformNotification::whereNotNull('outlet_id')->count(),
        ];

        // Super Admin Inbound Actionable Tasks
        $pendingRegistrations = TenantRegistration::where('status', TenantRegistration::STATUS_PENDING)
            ->latest('id')
            ->get();

        $needsRevisionRegistrations = TenantRegistration::where('status', TenantRegistration::STATUS_NEEDS_REVISION)
            ->latest('id')
            ->get();

        $pendingInvoices = PlatformInvoice::with('outlet')
            ->where('status', PlatformInvoice::STATUS_PAYMENT_SUBMITTED)
            ->latest('id')
            ->get();

        $pendingAds = AdCampaign::with('outlet')
            ->where('status', AdCampaign::STATUS_PENDING_REVIEW)
            ->latest('id')
            ->get();

        $pendingRefunds = PlatformRefund::with(['outlet', 'invoice'])
            ->whereIn('status', [PlatformRefund::STATUS_REQUESTED, PlatformRefund::STATUS_APPROVED])
            ->latest('id')
            ->get();

        $pendingComplaints = OwnerComplaint::with(['outlet', 'user'])
            ->whereIn('status', [OwnerComplaint::STATUS_NEW, OwnerComplaint::STATUS_IN_PROGRESS])
            ->latest('id')
            ->get();

        // Audit Trail / Activity Logs
        $activityLogs = TenantRegistrationHistory::with(['registration', 'actor'])
            ->latest('id')
            ->take(20)
            ->get();

        $tasksCount = $pendingRegistrations->count() 
            + $needsRevisionRegistrations->count() 
            + $pendingInvoices->count() 
            + $pendingAds->count() 
            + $pendingRefunds->count()
            + $pendingComplaints->count();

        return view('platform.notifications.index', compact(
            'notifications',
            'outlets',
            'stats',
            'pendingRegistrations',
            'needsRevisionRegistrations',
            'pendingInvoices',
            'pendingAds',
            'pendingRefunds',
            'pendingComplaints',
            'activityLogs',
            'tasksCount'
        ));
    }

    /**
     * Dispatch a broadcast or targeted platform notification with deduplication.
     */
    public function broadcast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'message'    => ['required', 'string', 'max:2000'],
            'type'       => ['required', 'string', 'in:ANNOUNCEMENT,MAINTENANCE,BILLING_ALERT,POLICY_UPDATE,GENERAL'],
            'target'     => ['required', 'string', 'in:all,specific'],
            'outlet_id'  => ['nullable', 'required_if:target,specific', 'exists:outlets,id'],
            'action_url' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['target'] === 'all') {
            // Broadcast to all active outlets
            $activeOutlets = Outlet::where('status', 'ACTIVE')->get();
            $sentCount = 0;

            foreach ($activeOutlets as $outlet) {
                // Deduplication guard against rapid double-submissions/retries within 5 minutes
                $isDuplicate = PlatformNotification::where('outlet_id', $outlet->id)
                    ->where('type', $validated['type'])
                    ->where('title', $validated['title'])
                    ->where('message', $validated['message'])
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->exists();

                if (! $isDuplicate) {
                    $this->notificationService->notify(
                        $outlet->id,
                        null,
                        $validated['type'],
                        $validated['title'],
                        $validated['message'],
                        $validated['action_url'] ?? null
                    );
                    $sentCount++;
                }
            }

            return redirect()->route('platform.notifications.index')
                ->with('success', $sentCount > 0 
                    ? "Pemberitahuan broadcast berhasil dikirim ke {$sentCount} tenant aktif."
                    : "Pemberitahuan broadcast identik baru saja dikirim sebelumnya (duplikasi dicegah).");
        }

        // Targeted notification to specific outlet
        $outlet = Outlet::findOrFail($validated['outlet_id']);

        $isDuplicate = PlatformNotification::where('outlet_id', $outlet->id)
            ->where('type', $validated['type'])
            ->where('title', $validated['title'])
            ->where('message', $validated['message'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($isDuplicate) {
            return redirect()->route('platform.notifications.index')
                ->with('info', "Pemberitahuan identik baru saja dikirim ke tenant '{$outlet->outlet_name}' dalam 5 menit terakhir (duplikasi dicegah).");
        }

        $this->notificationService->notify(
            $outlet->id,
            null,
            $validated['type'],
            $validated['title'],
            $validated['message'],
            $validated['action_url'] ?? null
        );

        return redirect()->route('platform.notifications.index')
            ->with('success', "Pemberitahuan berhasil dikirim ke tenant '{$outlet->outlet_name}'.");
    }
}
