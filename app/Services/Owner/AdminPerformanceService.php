<?php

namespace App\Services\Owner;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Sales\Order;
use App\Models\System\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminPerformanceService
{
    /**
     * Resolve start and end dates from a given preset or custom input.
     *
     * @return array{0: string, 1: string, 2: string} [startDate, endDate, preset]
     */
    public function resolveDateRange(?string $preset, ?string $customStart, ?string $customEnd): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $preset = $preset ?: 'this_month';

        [$startDate, $endDate] = match ($preset) {
            'today'       => [$now->toDateString(), $now->toDateString()],
            'this_month'  => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month'  => [
                $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'all'         => [
                '2020-01-01',
                $now->toDateString(),
            ],
            'custom'      => [
                $customStart ?: $now->copy()->startOfMonth()->toDateString(),
                $customEnd ?: $now->copy()->endOfMonth()->toDateString(),
            ],
            default       => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
        };

        return [$startDate, $endDate, $preset];
    }

    /**
     * Calculate comprehensive Admin performance metrics for Owner.
     *
     * @param string $startDate (Y-m-d)
     * @param string $endDate   (Y-m-d)
     * @param string $preset
     * @return array
     */
    public function calculatePerformance(string $startDate, string $endDate, string $preset = 'this_month'): array
    {
        $admins = User::where('role', UserRole::ADMIN)
            ->orderBy('name')
            ->get();

        $adminStats = collect();
        $grandTotalCustomers = 0;
        $grandDealCustomers = 0;
        $grandBelumDealCustomers = 0;
        $grandTotalOrders = 0;

        foreach ($admins as $admin) {
            $customerQuery = Customer::where('handled_by_user_id', $admin->id)
                ->withDealStatus();

            if ($preset !== 'all') {
                $customerQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween(DB::raw('DATE(COALESCE(first_contact_date, created_at))'), [$startDate, $endDate]);
                });
            }

            $customers = $customerQuery->get();
            $totalCustomers = $customers->count();

            // Deal customers: distinct count of customers having at least 1 order with CONFIRMED payment > 0
            $dealCount = $customers->filter(fn(Customer $c) => $c->isDeal())->count();
            $belumDealCount = $totalCustomers - $dealCount;

            // Deal percentage: null if zero denominator, strictly distinct deal customer count / total customer
            $dealPercentage = $totalCustomers > 0
                ? round(($dealCount / $totalCustomers) * 100, 2)
                : null;

            // Total orders created for these customers
            $customerIds = $customers->pluck('id')->all();
            $totalOrders = ! empty($customerIds)
                ? Order::whereIn('customer_id', $customerIds)->count()
                : 0;

            $adminStats->push([
                'admin'                => $admin,
                'total_customers'      => $totalCustomers,
                'deal_customers'       => $dealCount,
                'belum_deal_customers' => $belumDealCount,
                'deal_percentage'      => $dealPercentage,
                'deal_percentage_text' => $dealPercentage !== null ? number_format($dealPercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
                'total_orders'         => $totalOrders,
                'customers'            => $customers,
            ]);

            $grandTotalCustomers += $totalCustomers;
            $grandDealCustomers += $dealCount;
            $grandBelumDealCustomers += $belumDealCount;
            $grandTotalOrders += $totalOrders;
        }

        // Check unassigned customers in cohort
        $unassignedQuery = Customer::whereNull('handled_by_user_id');
        if ($preset !== 'all') {
            $unassignedQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(COALESCE(first_contact_date, created_at))'), [$startDate, $endDate]);
            });
        }
        $unassignedCount = $unassignedQuery->count();

        $overallDealPercentage = $grandTotalCustomers > 0
            ? round(($grandDealCustomers / $grandTotalCustomers) * 100, 2)
            : null;

        return [
            'admin_stats'                => $adminStats,
            'grand_total_customers'      => $grandTotalCustomers,
            'grand_deal_customers'       => $grandDealCustomers,
            'grand_belum_deal_customers' => $grandBelumDealCustomers,
            'grand_total_orders'         => $grandTotalOrders,
            'overall_deal_percentage'    => $overallDealPercentage,
            'overall_deal_text'          => $overallDealPercentage !== null ? number_format($overallDealPercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
            'unassigned_count'           => $unassignedCount,
            'start_date'                 => $startDate,
            'end_date'                   => $endDate,
            'preset'                     => $preset,
        ];
    }

    /**
     * Get detailed breakdown for a single Admin.
     */
    public function getAdminDetail(User $admin, string $startDate, string $endDate, string $preset = 'this_month'): array
    {
        $customerQuery = Customer::where('handled_by_user_id', $admin->id)
            ->with(['orders' => fn ($q) => $q->orderBy('id', 'asc'), 'orders.invoice.payments'])
            ->withDealStatus();

        if ($preset !== 'all') {
            $customerQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(COALESCE(first_contact_date, created_at))'), [$startDate, $endDate]);
            });
        }

        $customers = $customerQuery->latest('first_contact_date')->latest('id')->get();
        $totalCustomers = $customers->count();
        $dealCount = $customers->filter(fn(Customer $c) => $c->isDeal())->count();
        $belumDealCount = $totalCustomers - $dealCount;

        $dealPercentage = $totalCustomers > 0
            ? round(($dealCount / $totalCustomers) * 100, 2)
            : null;

        $customerIds = $customers->pluck('id')->all();
        $totalOrders = ! empty($customerIds)
            ? Order::whereIn('customer_id', $customerIds)->count()
            : 0;

        return [
            'admin'                => $admin,
            'customers'            => $customers,
            'total_customers'      => $totalCustomers,
            'deal_customers'       => $dealCount,
            'belum_deal_customers' => $belumDealCount,
            'deal_percentage'      => $dealPercentage,
            'deal_percentage_text' => $dealPercentage !== null ? number_format($dealPercentage, 2, ',', '.') . '%' : 'Belum Ada Data',
            'total_orders'         => $totalOrders,
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'preset'               => $preset,
        ];
    }
}
