<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Master\Unit;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\System\DashboardActionService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        ?PurchaseOrderService $poService = null,
        ?DashboardActionService $dashboardActionService = null
    ): View {
        $dashboardActionService = $dashboardActionService ?? app(DashboardActionService::class);

        $totalMaterials = Material::where('active', true)->count();
        $totalSuppliers = Supplier::count();
        $totalUnits     = Unit::count();

        // Calculate decimal-safe non-overlapping stock state counts for active materials
        $materials = Material::with('stock', 'unit')->where('active', true)->get();

        $lowStockCount   = 0;
        $outOfStockCount = 0;
        $safeStockCount  = 0;

        foreach ($materials as $m) {
            $stock = (string) ($m->stock?->qty_on_hand ?? '0');
            $minStock = (string) ($m->min_stock ?? '0');

            if (bccomp($stock, '0', 4) <= 0) {
                $outOfStockCount++;
            } elseif (bccomp($stock, $minStock, 4) <= 0) {
                $lowStockCount++;
            } else {
                $safeStockCount++;
            }
        }

        $recentMaterials = Material::with(['unit', 'stock'])
            ->latest('id')
            ->take(5)
            ->get();

        // Combined Action queue for Gudang (Purchases + MRP Batches)
        $actionQueueData = $dashboardActionService->getGudangActions(5);
        $actionablePurchases = $actionQueueData['items'];
        $totalActionablePurchases = $actionQueueData['total'];

        return view('dashboard.gudang', compact(
            'totalMaterials',
            'totalSuppliers',
            'totalUnits',
            'lowStockCount',
            'outOfStockCount',
            'safeStockCount',
            'recentMaterials',
            'actionablePurchases',
            'totalActionablePurchases'
        ));
    }
}
