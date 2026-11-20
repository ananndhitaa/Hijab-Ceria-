<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Master\Product;
use App\Models\Platform\AdCampaign;
use App\Models\Platform\OutletListingSlot;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformRefund;
use App\Models\Platform\TenantRegistration;
use App\Models\Platform\TenantSubscription;
use App\Models\Sales\Order;
use App\Models\System\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformAnalyticsController extends Controller
{
    /**
     * Display global platform analytics for Super Admin.
     */
    public function index(Request $request): View
    {
        $period = $request->input('period', '30d');
        // Tenant statistics
        $totalShops = Outlet::count();
        $activeShops = Outlet::where('status', 'ACTIVE')->count();
        $inactiveShops = Outlet::where('status', '!=', 'ACTIVE')->count();

        // Registration pipeline
        $pendingRegistrations = TenantRegistration::where('status', TenantRegistration::STATUS_PENDING)->count();
        $approvedRegistrations = TenantRegistration::where('status', TenantRegistration::STATUS_APPROVED)->count();
        $rejectedRegistrations = TenantRegistration::where('status', TenantRegistration::STATUS_REJECTED)->count();

        // Monetization metrics
        $activePremiumSubs = TenantSubscription::where('type', TenantSubscription::TYPE_PREMIUM)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>=', now())
            ->count();

        $activeAddonSubs = TenantSubscription::where('type', TenantSubscription::TYPE_ANALYTICS_ADDON)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>=', now())
            ->count();

        $permanentSlotsSold = (int) OutletListingSlot::sum('purchased_slots');

        // Advertising metrics
        $activeShopAds = AdCampaign::where('ad_type', AdCampaign::TYPE_SHOP_AD)
            ->where('status', AdCampaign::STATUS_ACTIVE)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->count();

        $activeProductAds = AdCampaign::where('ad_type', AdCampaign::TYPE_PRODUCT_AD)
            ->where('status', AdCampaign::STATUS_ACTIVE)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->count();

        // Verified platform revenue
        $totalVerifiedRevenue = (float) PlatformInvoice::where('status', PlatformInvoice::STATUS_VERIFIED)->sum('amount');
        $revenueByType = PlatformInvoice::where('status', PlatformInvoice::STATUS_VERIFIED)
            ->select('service_type', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as total_count'))
            ->groupBy('service_type')
            ->get();

        // Refunds
        $pendingRefundsCount = PlatformRefund::whereIn('status', [PlatformRefund::STATUS_REQUESTED, PlatformRefund::STATUS_APPROVED])->count();
        $completedRefundsTotal = (float) PlatformRefund::where('status', PlatformRefund::STATUS_COMPLETED)->sum('amount');

        // Organic Top Shops (Section Y: Strictly by completed orders / revenue, NOT paid ad placements)
        $topOrganicShops = Outlet::select('outlets.id', 'outlets.outlet_name', 'outlets.outlet_name as name', DB::raw('COUNT(DISTINCT orders.id) as completed_orders_count'), DB::raw('COALESCE(SUM(order_items.qty_ordered * order_items.unit_selling_price), 0) as total_revenue'))
            ->leftJoin('orders', function ($join) {
                $join->on('orders.outlet_id', '=', 'outlets.id')
                     ->whereIn('orders.status', ['SELESAI', 'COMPLETED']);
            })
            ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
            ->groupBy('outlets.id', 'outlets.outlet_name')
            ->orderByDesc('completed_orders_count')
            ->limit(5)
            ->get();

        // Organic Top Products
        $topOrganicProducts = Product::select('products.id', 'products.product_name', 'products.product_name as name', 'products.outlet_id', DB::raw('COALESCE(SUM(order_items.qty_ordered), 0) as total_units_sold'))
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->join('order_items', 'order_items.variant_id', '=', 'product_variants.id')
            ->join('orders', function ($join) {
                $join->on('orders.id', '=', 'order_items.order_id')
                     ->whereIn('orders.status', ['SELESAI', 'COMPLETED']);
            })
            ->groupBy('products.id', 'products.product_name', 'products.outlet_id')
            ->orderByDesc('total_units_sold')
            ->limit(5)
            ->get();

        return view('platform.analytics.index', compact(
            'totalShops',
            'activeShops',
            'inactiveShops',
            'pendingRegistrations',
            'approvedRegistrations',
            'rejectedRegistrations',
            'activePremiumSubs',
            'activeAddonSubs',
            'permanentSlotsSold',
            'activeShopAds',
            'activeProductAds',
            'totalVerifiedRevenue',
            'revenueByType',
            'pendingRefundsCount',
            'completedRefundsTotal',
            'topOrganicShops',
            'topOrganicProducts',
            'period'
        ));
    }
}
