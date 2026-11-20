<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerNotificationController extends Controller
{
    /**
     * List all platform notifications for the outlet.
     */
    public function index(Request $request): View
    {
        $outletId = auth()->user()->outlet_id;

        $pageTitle = 'Pusat Notifikasi';
        $breadcrumb = 'Executive Monitoring > Pusat Notifikasi';

        // Compute actual counts for this outlet
        $totalCount = PlatformNotification::where('outlet_id', $outletId)->count();
        $unreadCount = PlatformNotification::where('outlet_id', $outletId)->where('is_read', false)->count();
        $readCount = PlatformNotification::where('outlet_id', $outletId)->where('is_read', true)->count();

        $status = $request->query('status', 'all');
        $category = $request->query('category', 'all');

        $query = PlatformNotification::where('outlet_id', $outletId);

        // Filter by read status
        if ($status === 'unread') {
            $query->where('is_read', false);
        } elseif ($status === 'read') {
            $query->where('is_read', true);
        }

        // Filter by category
        if ($category === 'ads') {
            $query->where('type', 'LIKE', 'AD_%');
        } elseif ($category === 'billing') {
            $query->where(function ($q) {
                $q->where('type', 'LIKE', 'BILLING_%')
                  ->orWhere('type', 'LIKE', 'INVOICE_%')
                  ->orWhere('type', 'LIKE', 'SUBSCRIPTION_%')
                  ->orWhere('type', 'LIKE', 'REFUND_%');
            });
        } elseif ($category === 'showcase') {
            $query->where(function ($q) {
                $q->where('type', 'LIKE', 'PRODUCT_%')
                  ->orWhere('type', 'LIKE', 'CATALOG_%')
                  ->orWhere('type', 'LIKE', 'SHOWCASE_%')
                  ->orWhere('type', 'LIKE', 'PUBLICATION_%');
            });
        } elseif ($category === 'service') {
            $query->where('type', 'LIKE', 'SERVICE_%');
        } elseif ($category === 'system') {
            $query->where(function ($q) {
                $q->whereIn('type', ['ANNOUNCEMENT', 'MAINTENANCE', 'POLICY_UPDATE', 'GENERAL', 'SYSTEM'])
                  ->orWhere('type', 'LIKE', 'SYSTEM_%');
            });
        }

        $notifications = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('owner.notifications.index', compact(
            'notifications',
            'totalCount',
            'unreadCount',
            'readCount',
            'status',
            'category',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Mark a notification as read.
     */
    public function markRead(PlatformNotification $notification): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        if ($notification->outlet_id !== $outletId) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $notification->update(['is_read' => true]);

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        return back()->with('success', 'Notifikasi telah ditandai dibaca.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(): RedirectResponse
    {
        $outletId = auth()->user()->outlet_id;

        $updated = PlatformNotification::where('outlet_id', $outletId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', $updated > 0
            ? 'Semua notifikasi berhasil ditandai sebagai telah dibaca.'
            : 'Tidak ada notifikasi baru yang perlu ditandai.');
    }
}
