<?php

namespace App\Http\Controllers\Platform;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformRefund;
use App\Models\Platform\TenantRegistration;
use App\Models\Platform\TenantRegistrationHistory;
use App\Models\Platform\TenantSubscription;
use App\Models\System\Outlet;
use App\Models\System\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    /**
     * Display a platform-scoped overview for Super Admin.
     *
     * The dashboard intentionally uses platform entities and safe tenant
     * aggregates only. It must not read tenant Sales Orders, payments,
     * inventory, HPP, or other operational transaction details.
     */
    public function index(): View
    {
        $now = now();

        $metrics = [
            'total_tenants' => Outlet::count(),
            'active_tenants' => Outlet::where('status', 'ACTIVE')->count(),
            'inactive_tenants' => Outlet::where('status', '!=', 'ACTIVE')->count(),

            'total_registrations' => TenantRegistration::count(),
            'pending_registrations' => TenantRegistration::where(
                'status',
                TenantRegistration::STATUS_PENDING
            )->count(),
            'needs_revision' => TenantRegistration::where(
                'status',
                TenantRegistration::STATUS_NEEDS_REVISION
            )->count(),
            'approved_registrations' => TenantRegistration::where(
                'status',
                TenantRegistration::STATUS_APPROVED
            )->count(),
            'rejected_registrations' => TenantRegistration::where(
                'status',
                TenantRegistration::STATUS_REJECTED
            )->count(),

            'pending_platform_invoices' => PlatformInvoice::where(
                'status',
                PlatformInvoice::STATUS_PAYMENT_SUBMITTED
            )->count(),
            'verified_platform_revenue' => PlatformInvoice::where(
                'status',
                PlatformInvoice::STATUS_VERIFIED
            )->sum('amount'),

            'active_premium_subscriptions' => TenantSubscription::where('type', TenantSubscription::TYPE_PREMIUM)
                ->where('status', TenantSubscription::STATUS_ACTIVE)
                ->where('expires_at', '>=', $now)
                ->count(),
            'active_analytics_addons' => TenantSubscription::where('type', TenantSubscription::TYPE_ANALYTICS_ADDON)
                ->where('status', TenantSubscription::STATUS_ACTIVE)
                ->where('expires_at', '>=', $now)
                ->count(),

            'active_campaigns' => AdCampaign::where('status', AdCampaign::STATUS_ACTIVE)
                ->where('starts_at', '<=', $now)
                ->where('ends_at', '>', $now)
                ->count(),
            'pending_ad_reviews' => AdCampaign::where('status', AdCampaign::STATUS_PENDING_REVIEW)->count(),
            'pending_refunds' => PlatformRefund::whereIn('status', [
                PlatformRefund::STATUS_REQUESTED,
                PlatformRefund::STATUS_APPROVED,
            ])->count(),

            // Akun tenant terdaftar hanya mencakup akun Owner konveksi terverifikasi/aktif.
            // Tidak mencakup akun karyawan tenant (Admin, Gudang, Produksi, Customer)
            // dan tidak mencakup pengajuan pendaftaran yang masih tahap review.
            'total_tenant_users' => User::where('role', UserRole::OWNER->value)->count(),
        ];

        $recentRegistrations = TenantRegistration::with('reviewer')
            ->latest('id')
            ->limit(6)
            ->get();

        $recentActivities = TenantRegistrationHistory::with([
            'registration:id,company_name',
            'actor:id,name',
        ])
            ->latest('created_at')
            ->limit(6)
            ->get();

        // Actual registration volume for the last seven calendar days.
        $today = $now->copy()->startOfDay();
        $trendStart = $today->copy()->subDays(6);
        $registrationRows = TenantRegistration::where('created_at', '>=', $trendStart)
            ->get(['created_at']);

        $registrationCounts = $registrationRows
            ->groupBy(fn (TenantRegistration $registration) => $registration->created_at?->toDateString())
            ->map(fn ($items) => $items->count());

        $dayLabels = [
            1 => 'Sen',
            2 => 'Sel',
            3 => 'Rab',
            4 => 'Kam',
            5 => 'Jum',
            6 => 'Sab',
            7 => 'Min',
        ];

        $registrationTrend = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($today, $registrationCounts, $dayLabels) {
                $date = $today->copy()->subDays($daysAgo);
                $dateKey = $date->toDateString();

                return [
                    'label' => $dayLabels[$date->dayOfWeekIso],
                    'date_label' => $date->format('d/m'),
                    'count' => (int) $registrationCounts->get($dateKey, 0),
                ];
            });

        $maxRegistrationCount = max(1, (int) $registrationTrend->max('count'));
        $registrationTrend = $registrationTrend->map(function (array $item) use ($maxRegistrationCount) {
            $item['percentage'] = $item['count'] > 0
                ? (int) round(($item['count'] / $maxRegistrationCount) * 100)
                : 0;

            return $item;
        })->values();

        $attentionCount = $metrics['pending_registrations']
            + $metrics['needs_revision']
            + $metrics['pending_platform_invoices']
            + $metrics['pending_refunds']
            + $metrics['pending_ad_reviews'];

        // 5 Official Revenue Streams of NAYARA Group Platform
        $verifiedInvoices = PlatformInvoice::where('status', PlatformInvoice::STATUS_VERIFIED)->get();

        $stream1 = (float) $verifiedInvoices->whereIn('service_type', [
            PlatformInvoice::SERVICE_PREMIUM,
            PlatformInvoice::SERVICE_UPGRADE_PREMIUM,
        ])->sum('amount');

        $stream2 = (float) $verifiedInvoices->where('service_type', PlatformInvoice::SERVICE_LISTING_SLOTS)->sum('amount');

        $stream3 = (float) $verifiedInvoices->whereIn('service_type', [
            PlatformInvoice::SERVICE_SHOP_AD,
            PlatformInvoice::SERVICE_PRODUCT_AD,
        ])->sum('amount');

        $stream4 = (float) $verifiedInvoices->where('service_type', PlatformInvoice::SERVICE_ANALYTICS_ADDON)->sum('amount');

        $stream5 = (float) $verifiedInvoices->where('service_type', PlatformInvoice::SERVICE_SETUP_TRAINING)->sum('amount');

        $totalVerifiedRevenue = (float) $verifiedInvoices->sum('amount');

        $revenueStreams = [
            [
                'stream_no' => 1,
                'name' => 'Langganan SaaS Premium',
                'description' => 'Paket bulanan/tahunan fitur operasional lanjutan',
                'amount' => $stream1,
                'percentage' => $totalVerifiedRevenue > 0 ? round(($stream1 / $totalVerifiedRevenue) * 100, 1) : 0,
                'icon' => 'building',
            ],
            [
                'stream_no' => 2,
                'name' => 'Slot Produk Tambahan',
                'description' => 'Ekspansi kuota publikasi katalog etalase (+5 / +10)',
                'amount' => $stream2,
                'percentage' => $totalVerifiedRevenue > 0 ? round(($stream2 / $totalVerifiedRevenue) * 100, 1) : 0,
                'icon' => 'layout',
            ],
            [
                'stream_no' => 3,
                'name' => 'Ekosistem Iklan Berbayar',
                'description' => 'Iklan Toko (Highlight) & Iklan Produk (Bersponsor)',
                'amount' => $stream3,
                'percentage' => $totalVerifiedRevenue > 0 ? round(($stream3 / $totalVerifiedRevenue) * 100, 1) : 0,
                'icon' => 'megaphone',
            ],
            [
                'stream_no' => 4,
                'name' => 'Add-on Analitik Keuangan',
                'description' => 'Akses metrik finansial, HPP breakdown, & profitabilitas',
                'amount' => $stream4,
                'percentage' => $totalVerifiedRevenue > 0 ? round(($stream4 / $totalVerifiedRevenue) * 100, 1) : 0,
                'icon' => 'chart',
            ],
            [
                'stream_no' => 5,
                'name' => 'Layanan Setup & Pelatihan',
                'description' => 'Pendampingan implementasi ERP & pelatihan konveksi',
                'amount' => $stream5,
                'percentage' => $totalVerifiedRevenue > 0 ? round(($stream5 / $totalVerifiedRevenue) * 100, 1) : 0,
                'icon' => 'graduation',
            ],
        ];

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver' => DB::connection()->getDriverName(),
            'db_version' => $this->getDbVersion(),
            'tenant_isolation_status' => config('nayara.tenant_isolation_enabled', false)
                ? 'Aktif'
                : 'Belum diaktifkan',
            'app_timezone' => config('app.timezone'),
        ];

        // Retain the existing dashboard data contract for other consumers.
        $nayaraDashboard = [
            'overview' => [
                'pending_registrations' => $metrics['pending_registrations'],
                'active_outlets' => $metrics['active_tenants'],
                'needs_revision' => $metrics['needs_revision'],
                'inactive_outlets' => $metrics['inactive_tenants'],
                'total_registrations' => $metrics['total_registrations'],
                'approved_registrations' => $metrics['approved_registrations'],
                'rejected_registrations' => $metrics['rejected_registrations'],
                'tenant_users' => $metrics['total_tenant_users'],
            ],
            'recent_registrations' => $recentRegistrations,
            'platform' => [
                'invoices_pending_verification' => $metrics['pending_platform_invoices'],
                'pending_refunds' => $metrics['pending_refunds'],
                'active_campaigns' => $metrics['active_campaigns'],
                'verified_revenue' => $metrics['verified_platform_revenue'],
                'active_premium_subscriptions' => $metrics['active_premium_subscriptions'],
                'revenue_streams' => $revenueStreams,
            ],
        ];

        // Platform Monetization Summary (strictly from PlatformInvoice verified payments)
        $verifiedInvoicesCount = $verifiedInvoices->count();
        $averageVerifiedInvoice = $verifiedInvoicesCount > 0 ? (float) round($totalVerifiedRevenue / $verifiedInvoicesCount) : 0.0;

        // Dynamic 7-day, 30-day, and 3-month SaaS monetization trend
        $invoices7d = PlatformInvoice::where('status', PlatformInvoice::STATUS_VERIFIED)
            ->where('verified_at', '>=', $today->copy()->subDays(6))
            ->get(['verified_at', 'amount']);

        $monetizationTrend7d = collect(range(6, 0))->map(function (int $daysAgo) use ($today, $dayLabels, $invoices7d) {
            $date = $today->copy()->subDays($daysAgo);
            $dateKey = $date->toDateString();
            $matches = $invoices7d->filter(fn ($inv) => $inv->verified_at?->toDateString() === $dateKey);
            $amount = (float) $matches->sum('amount');
            $count = $matches->count();

            return [
                'label' => $dayLabels[$date->dayOfWeekIso],
                'date_label' => $date->format('d M'),
                'amount' => $amount,
                'count' => $count,
                'formatted_amount' => 'Rp ' . number_format($amount, 0, ',', '.'),
            ];
        })->values();

        // 30d (4 weekly intervals)
        $monetizationTrend30d = collect([
            ['label' => 'Mgg 1', 'date_label' => '11 - 17 Okt', 'start' => $today->copy()->subDays(28), 'end' => $today->copy()->subDays(21)],
            ['label' => 'Mgg 2', 'date_label' => '18 - 24 Okt', 'start' => $today->copy()->subDays(21), 'end' => $today->copy()->subDays(14)],
            ['label' => 'Mgg 3', 'date_label' => '25 - 31 Okt', 'start' => $today->copy()->subDays(14), 'end' => $today->copy()->subDays(7)],
            ['label' => 'Mgg 4', 'date_label' => '01 - 08 Nov', 'start' => $today->copy()->subDays(7), 'end' => $today->copy()->addDay()],
        ])->map(function ($item) {
            $matches = PlatformInvoice::where('status', PlatformInvoice::STATUS_VERIFIED)
                ->where('verified_at', '>=', $item['start'])
                ->where('verified_at', '<', $item['end'])
                ->get(['amount']);
            $amount = (float) $matches->sum('amount');
            $count = $matches->count();

            return [
                'label' => $item['label'],
                'date_label' => $item['date_label'],
                'amount' => $amount,
                'count' => $count,
                'formatted_amount' => 'Rp ' . number_format($amount, 0, ',', '.'),
            ];
        });

        // 3m (3 months)
        $monetizationTrend3m = collect(range(2, 0))->map(function (int $monthsAgo) use ($today) {
            $monthDate = $today->copy()->subMonths($monthsAgo);
            $start = $monthDate->copy()->startOfMonth();
            $end = $monthDate->copy()->endOfMonth();
            $matches = PlatformInvoice::where('status', PlatformInvoice::STATUS_VERIFIED)
                ->where('verified_at', '>=', $start)
                ->where('verified_at', '<=', $end)
                ->get(['amount']);
            $amount = (float) $matches->sum('amount');
            $count = $matches->count();

            return [
                'label' => $monthDate->translatedFormat('M Y') ?: $monthDate->format('M Y'),
                'date_label' => $monthDate->translatedFormat('F Y') ?: $monthDate->format('F Y'),
                'amount' => $amount,
                'count' => $count,
                'formatted_amount' => 'Rp ' . number_format($amount, 0, ',', '.'),
            ];
        });

        // Top period calculation
        $topPeriod = '—';
        $topPeriodAmount = 0.0;
        if ($totalVerifiedRevenue > 0) {
            $topPeriod = 'Bulan Ini';
            $topPeriodAmount = $totalVerifiedRevenue;
        }

        $platformMonetizationSummary = [
            'total_revenue' => $totalVerifiedRevenue,
            'paid_invoices_count' => $verifiedInvoicesCount,
            'average_invoice_value' => $averageVerifiedInvoice,
            'top_period' => $topPeriod,
            'top_period_amount' => $topPeriodAmount,
        ];

        $monetizationTrends = [
            '7d' => $monetizationTrend7d,
            '30d' => $monetizationTrend30d,
            '3m' => $monetizationTrend3m,
        ];

        // Safe Infrastructure Monitoring Status (honest APM status)
        $infrastructureMonitoring = [
            'is_configured' => false,
            'uptime' => 'Belum tersedia',
            'response_time' => 'Belum tersedia',
            'database_load' => 'Belum tersedia',
            'status_note' => 'Monitoring belum tersedia',
            'db_status' => 'Aktif & Terhubung',
        ];

        // Tenant Composition
        $tenantComposition = [
            'total_tenants' => $metrics['total_tenants'],
            'active_tenants' => $metrics['active_tenants'],
            'pending_registrations' => $metrics['pending_registrations'],
            'inactive_tenants' => $metrics['inactive_tenants'] + $metrics['needs_revision'],
            'active_percentage' => $metrics['total_tenants'] > 0
                ? (int) round(($metrics['active_tenants'] / $metrics['total_tenants']) * 100)
                : 0,
        ];

        // Safe Ecosystem Top Rankings (Top Stores, Categories, Customers, Products, Ad Products)
        try {
            $topStores = Outlet::select(
                    'outlets.id',
                    'outlets.outlet_name',
                    DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                    DB::raw('COALESCE(SUM(order_items.qty_ordered * order_items.unit_selling_price), 0) as total_revenue')
                )
                ->join('orders', function ($join) {
                    $join->on('orders.outlet_id', '=', 'outlets.id')
                         ->whereIn('orders.status', ['SELESAI', 'COMPLETED']);
                })
                ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
                ->groupBy('outlets.id', 'outlets.outlet_name')
                ->orderByDesc('orders_count')
                ->limit(5)
                ->get();
        } catch (\Throwable) {
            $topStores = collect();
        }

        try {
            $topCategories = \App\Models\Master\Product::select(
                    'products.public_category as category_name',
                    DB::raw('COALESCE(SUM(order_items.qty_ordered), 0) as total_sold'),
                    DB::raw('COALESCE(SUM(order_items.qty_ordered * order_items.unit_selling_price), 0) as total_revenue')
                )
                ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
                ->join('order_items', 'order_items.variant_id', '=', 'product_variants.id')
                ->join('orders', function ($join) {
                    $join->on('orders.id', '=', 'order_items.order_id')
                         ->whereIn('orders.status', ['SELESAI', 'COMPLETED']);
                })
                ->whereNotNull('products.public_category')
                ->where('products.public_category', '!=', '')
                ->groupBy('products.public_category')
                ->orderByDesc('total_sold')
                ->limit(5)
                ->get();
        } catch (\Throwable) {
            $topCategories = collect();
        }

        try {
            $topCustomers = \App\Models\Master\Customer::select(
                    'customers.id',
                    'customers.customer_name',
                    DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                    DB::raw('COALESCE(SUM(order_items.qty_ordered * order_items.unit_selling_price), 0) as total_spend')
                )
                ->join('orders', function ($join) {
                    $join->on('orders.customer_id', '=', 'customers.id')
                         ->whereIn('orders.status', ['SELESAI', 'COMPLETED']);
                })
                ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
                ->groupBy('customers.id', 'customers.customer_name')
                ->orderByDesc('total_spend')
                ->limit(5)
                ->get();
        } catch (\Throwable) {
            $topCustomers = collect();
        }

        try {
            $topProducts = \App\Models\Master\Product::select(
                    'products.id',
                    'products.product_name',
                    'products.outlet_id',
                    DB::raw('COALESCE(SUM(order_items.qty_ordered), 0) as total_sold')
                )
                ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
                ->join('order_items', 'order_items.variant_id', '=', 'product_variants.id')
                ->join('orders', function ($join) {
                    $join->on('orders.id', '=', 'order_items.order_id')
                         ->whereIn('orders.status', ['SELESAI', 'COMPLETED']);
                })
                ->groupBy('products.id', 'products.product_name', 'products.outlet_id')
                ->orderByDesc('total_sold')
                ->limit(5)
                ->get();
        } catch (\Throwable) {
            $topProducts = collect();
        }

        try {
            $topAdProducts = AdCampaign::where('ad_type', AdCampaign::TYPE_PRODUCT_AD)
                ->whereIn('status', [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_SCHEDULED, AdCampaign::STATUS_APPROVED])
                ->with(['outlet'])
                ->latest('id')
                ->limit(5)
                ->get();
        } catch (\Throwable) {
            $topAdProducts = collect();
        }

        return view('platform.dashboard', compact(
            'nayaraDashboard',
            'metrics',
            'recentRegistrations',
            'recentActivities',
            'registrationTrend',
            'attentionCount',
            'systemInfo',
            'revenueStreams',
            'totalVerifiedRevenue',
            'platformMonetizationSummary',
            'monetizationTrends',
            'infrastructureMonitoring',
            'tenantComposition',
            'topStores',
            'topCategories',
            'topCustomers',
            'topProducts',
            'topAdProducts'
        ));
    }

    protected function getDbVersion(): string
    {
        try {
            $row = DB::select('SELECT VERSION() as v');

            return $row[0]->v ?? 'Unknown';
        } catch (\Throwable) {
            return 'N/A';
        }
    }
}
