<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\TenantRegistration;
use App\Models\Platform\TenantSubscription;
use App\Models\System\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformTenantController extends Controller
{
    /**
     * Display tenant directory.
     * Hardened: Does NOT load or count internal tenant users/staff.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $sort   = $request->input('sort', 'latest');

        $query = Outlet::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('outlet_name', 'like', "%{$search}%")
                  ->orWhere('outlet_code', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('phone_wa', 'like', "%{$search}%");
            });
        }

        if ($status && in_array($status, ['ACTIVE', 'INACTIVE'])) {
            $query->where('status', $status);
        }

        match ($sort) {
            'oldest'    => $query->orderBy('created_at', 'asc'),
            'name_asc'  => $query->orderBy('outlet_name', 'asc'),
            'name_desc' => $query->orderBy('outlet_name', 'desc'),
            default     => $query->orderBy('created_at', 'desc'),
        };

        $outlets = $query->paginate(15)->withQueryString();

        $stats = [
            'total'         => Outlet::count(),
            'active'        => Outlet::where('status', 'ACTIVE')->count(),
            'action_needed' => TenantRegistration::whereIn('status', [
                TenantRegistration::STATUS_PENDING,
                TenantRegistration::STATUS_NEEDS_REVISION,
            ])->count(),
            'inactive'      => Outlet::where('status', 'INACTIVE')->count(),
        ];

        return view('platform.tenants.index', compact('outlets', 'search', 'status', 'sort', 'stats'));
    }

    /**
     * Display tenant administrative detail view.
     * Hardened: Strictly does NOT load or expose internal tenant personnel or staff accounts.
     */
    public function show(Outlet $outlet): View
    {
        $subscription = TenantSubscription::where('outlet_id', $outlet->id)->latest()->first();
        $invoicesCount = PlatformInvoice::where('outlet_id', $outlet->id)->count();
        $paidInvoicesSum = (float) PlatformInvoice::where('outlet_id', $outlet->id)
            ->where('status', PlatformInvoice::STATUS_VERIFIED)
            ->sum('amount');
        $registration = TenantRegistration::where('outlet_id', $outlet->id)->first();

        return view('platform.tenants.show', compact('outlet', 'subscription', 'invoicesCount', 'paidInvoicesSum', 'registration'));
    }

    /**
     * Toggle tenant status between ACTIVE and INACTIVE (Suspend / Reactivate).
     * Preserves all operational and financial records.
     */
    public function toggleStatus(Request $request, Outlet $outlet): RedirectResponse
    {
        $newStatus = $outlet->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        $outlet->update(['status' => $newStatus]);

        $actionText = $newStatus === 'ACTIVE' ? 'diaktifkan kembali' : 'dinonaktifkan (disuspend)';
        $reason = $request->input('reason');
        $msg = "Status outlet {$outlet->outlet_name} berhasil {$actionText}.";
        if ($reason) {
            $msg .= " Catatan audit: \"{$reason}\".";
        }
        $msg .= " Seluruh data operasional dan keuangan tersimpan aman.";

        return redirect()->route('platform.tenants.show', $outlet)
            ->with('success', $msg);
    }
}

