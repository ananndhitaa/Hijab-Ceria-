<?php

namespace App\Http\Controllers\Owner;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\System\OutletActivityLog;
use App\Models\System\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerActivityLogController extends Controller
{
    /**
     * Resolve legitimate outlet ID for authenticated OWNER.
     * Fails closed (aborts 403) if user is not OWNER or outlet_id is empty.
     */
    protected function resolveOwnerOutletId(): int
    {
        $owner = auth()->user();
        if (! $owner || $owner->role !== UserRole::OWNER || empty($owner->outlet_id)) {
            abort(403, 'Akses ditolak: Akun Owner tidak terikat ke outlet konveksi yang sah.');
        }

        return (int) $owner->outlet_id;
    }

    /**
     * Display the tenant activity logs (Read-Only).
     */
    public function index(Request $request): View
    {
        $outletId = $this->resolveOwnerOutletId();

        // Ensure backend historical transactions are synchronized into audit logs
        if (OutletActivityLog::forOutlet($outletId)->count() <= 1) {
            \App\Services\System\TenantActivitySyncService::syncOutletLogs($outletId);
        }

        $query = OutletActivityLog::forOutlet($outletId)->with('user');

        // Filter: Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('action_type', 'like', "%{$search}%");
            });
        }

        // Filter: Module
        if ($module = $request->input('module')) {
            if ($module !== 'all') {
                $query->where('module', $module);
            }
        }

        // Filter: Action Type
        if ($actionType = $request->input('action_type')) {
            if ($actionType !== 'all') {
                $query->where('action_type', $actionType);
            }
        }

        // Filter: User
        if ($userId = $request->input('user_id')) {
            if ($userId !== 'all') {
                $query->where('user_id', $userId);
            }
        }

        // Filter: Date Range
        $preset = $request->input('preset', 'all');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');
        $now = Carbon::now('Asia/Jakarta');

        if ($preset === 'today') {
            $query->whereBetween('created_at', [
                $now->copy()->startOfDay()->utc(),
                $now->copy()->endOfDay()->utc(),
            ]);
        } elseif ($preset === 'this_month') {
            $query->whereBetween('created_at', [
                $now->copy()->startOfMonth()->startOfDay()->utc(),
                $now->copy()->endOfMonth()->endOfDay()->utc(),
            ]);
        } elseif ($preset === 'last_month') {
            $query->whereBetween('created_at', [
                $now->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay()->utc(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay()->utc(),
            ]);
        } elseif ($preset === 'custom' && $customStart && $customEnd) {
            $query->whereBetween('created_at', [
                Carbon::parse($customStart, 'Asia/Jakarta')->startOfDay()->utc(),
                Carbon::parse($customEnd, 'Asia/Jakarta')->endOfDay()->utc(),
            ]);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Statistics
        $totalLogsCount = OutletActivityLog::forOutlet($outletId)->count();
        $todayLogsCount = OutletActivityLog::forOutlet($outletId)
            ->whereBetween('created_at', [
                $now->copy()->startOfDay()->utc(),
                $now->copy()->endOfDay()->utc(),
            ])
            ->count();

        $productionLogsCount = OutletActivityLog::forOutlet($outletId)
            ->whereIn('module', ['BATCH_PRODUKSI', 'PRODUKSI', 'QC'])
            ->count();

        $salesLogsCount = OutletActivityLog::forOutlet($outletId)
            ->whereIn('module', ['SALES_ORDER', 'KEUANGAN'])
            ->count();

        // Users in this outlet for filter dropdown
        $users = User::where('outlet_id', $outletId)->orderBy('name')->get();

        // Distinct modules and action types
        $availableModules = OutletActivityLog::forOutlet($outletId)
            ->select('module')
            ->distinct()
            ->pluck('module');

        return view('owner.activity-logs.index', compact(
            'logs',
            'totalLogsCount',
            'todayLogsCount',
            'productionLogsCount',
            'salesLogsCount',
            'users',
            'availableModules',
            'search',
            'module',
            'actionType',
            'userId',
            'preset',
            'customStart',
            'customEnd'
        ));
    }
}

