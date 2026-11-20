<?php

namespace App\Http\Controllers\Gudang;

use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StorePurchaseRequest;
use App\Http\Requests\Gudang\UpdatePurchaseRequest;
use App\Models\Inventory\Purchase;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Models\Production\ProductionBatch;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\PurchaseApprovalService;
use App\Services\Inventory\PurchaseOrderService;
use App\Services\Inventory\PurchaseReceivingService;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    /**
     * Display a read-only purchase receiving history (Riwayat Pembelian Bahan Baku).
     *
     * Shows only RECEIVED purchase items with their corresponding
     * StockMovement IN records, ordered by newest receiving first.
     */
    public function history(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $search = $request->input('search');
        $supplierFilter = $request->input('supplier_id');
        $materialFilter = $request->input('material_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // 1. Determine canonical historical supplier-material relationships from received items
        $historicalPairs = \App\Models\Inventory\PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->when($outletId !== null, fn ($q) => $q->where('purchases.outlet_id', $outletId))
            ->where('purchases.status', PurchaseStatus::RECEIVED)
            ->where('purchase_items.qty_received', '>', 0)
            ->select('purchases.supplier_id', 'purchase_items.material_id')
            ->distinct()
            ->get();

        $supplierMaterialMap = [];
        $allHistoricalMaterialIds = [];
        foreach ($historicalPairs as $pair) {
            if ($pair->supplier_id) {
                $sId = (string) $pair->supplier_id;
                $mId = (int) $pair->material_id;
                $supplierMaterialMap[$sId][] = $mId;
            }
            if ($pair->material_id) {
                $allHistoricalMaterialIds[(int) $pair->material_id] = true;
            }
        }
        $allHistoricalMaterialIds = array_keys($allHistoricalMaterialIds);

        // Server-side safety: normalize invalid supplier-material combinations
        if ($supplierFilter) {
            $validMaterialIdsForSupplier = $supplierMaterialMap[(string) $supplierFilter] ?? [];
            if ($materialFilter && ! in_array((int) $materialFilter, $validMaterialIdsForSupplier, true)) {
                $materialFilter = null;
            }
        } elseif ($materialFilter && ! in_array((int) $materialFilter, $allHistoricalMaterialIds, true)) {
            $materialFilter = null;
        }

        $query = \App\Models\Inventory\PurchaseItem::query()
            ->with([
                'purchase.supplier',
                'material.unit',
            ])
            ->whereHas('purchase', function ($q) use ($outletId) {
                $q->where('status', PurchaseStatus::RECEIVED)
                    ->when($outletId !== null, fn ($pq) => $pq->where('purchases.outlet_id', $outletId));
            })
            ->when($outletId !== null, fn ($q) => $q->whereHas('material', fn ($mq) => $mq->where('materials.outlet_id', $outletId)))
            ->where('qty_received', '>', 0);

        // Search: PO number / supplier name / material name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('purchase', function ($pq) use ($search) {
                    $pq->where('purchase_no', 'like', "%{$search}%")
                       ->orWhereHas('supplier', function ($sq) use ($search) {
                           $sq->where('supplier_name', 'like', "%{$search}%");
                       });
                })->orWhereHas('material', function ($mq) use ($search) {
                    $mq->where('material_name', 'like', "%{$search}%");
                });
            });
        }

        // Filter by supplier
        if ($supplierFilter) {
            $query->whereHas('purchase', function ($q) use ($supplierFilter) {
                $q->where('supplier_id', $supplierFilter);
            });
        }

        // Filter by material
        if ($materialFilter) {
            $query->where('material_id', $materialFilter);
        }

        // Filter by receiving date range (via linked StockMovement)
        if ($dateFrom || $dateTo) {
            $query->whereHas('stockMovementIn', function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) {
                    $q->where('created_at', '>=', $dateFrom . ' 00:00:00');
                }
                if ($dateTo) {
                    $q->where('created_at', '<=', $dateTo . ' 23:59:59');
                }
            });
        }

        // Order by the purchase's receiving date (newest first)
        $query->orderByDesc(
            \App\Models\Inventory\Purchase::select('updated_at')
                ->whereColumn('purchases.id', 'purchase_items.purchase_id')
                ->limit(1)
        )->orderByDesc('id');

        $historyItems = $query->paginate(20)->withQueryString();

        // Eager-load the StockMovement IN for each item to get the exact receiving timestamp
        $itemIds = $historyItems->pluck('id')->all();
        $stockMovementsMap = [];
        if (! empty($itemIds)) {
            $stockMovementsMap = \App\Models\Inventory\StockMovement::with('createdByUser')
                ->where('reference_type', 'purchase_items')
                ->whereIn('reference_id', $itemIds)
                ->where('movement_type', \App\Enums\MovementType::IN)
                ->get()
                ->keyBy('reference_id');
        }

        $suppliersQuery = $outletId !== null
            ? Supplier::forOutlet($outletId)
            : Supplier::query();
        $suppliers = $suppliersQuery->orderBy('supplier_name')->get();

        $allMaterialsQuery = $outletId !== null
            ? Material::where('outlet_id', $outletId)
            : Material::query();
        $allMaterials = $allMaterialsQuery->with('unit')
            ->whereIn('id', $allHistoricalMaterialIds)
            ->orderBy('material_name')
            ->get();

        if ($supplierFilter) {
            $allowedIds = $supplierMaterialMap[(string) $supplierFilter] ?? [];
            $materials = $allMaterials->filter(fn ($m) => in_array($m->id, $allowedIds))->values();
        } else {
            $materials = $allMaterials;
        }

        $materialsJson = $allMaterials->map(function ($m) {
            return [
                'id'   => $m->id,
                'name' => $m->material_name,
                'unit' => $m->unit?->symbol ?? '-',
            ];
        })->values();

        return view('gudang.purchases.history', compact(
            'historyItems',
            'stockMovementsMap',
            'suppliers',
            'materials',
            'materialsJson',
            'supplierMaterialMap',
            'search',
            'supplierFilter',
            'materialFilter',
            'dateFrom',
            'dateTo'
        ));
    }


    public function index(Request $request, PurchaseOrderService $service): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $search = $request->input('search');
        $supplierFilter = $request->input('supplier_id');
        $statusFilter = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $purchasesQuery = $outletId !== null
            ? Purchase::forOutlet($outletId)
            : Purchase::query();

        $purchases = $purchasesQuery
            ->with(['supplier', 'items.material.unit', 'payments'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('purchase_no', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($sq) use ($search) {
                          $sq->where('supplier_name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($supplierFilter, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->when($statusFilter, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($dateFrom, function ($query, $from) {
                $query->whereDate('purchase_date', '>=', $from);
            })
            ->when($dateTo, function ($query, $to) {
                $query->whereDate('purchase_date', '<=', $to);
            })
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $suppliersQuery = $outletId !== null
            ? Supplier::forOutlet($outletId)
            : Supplier::query();
        $suppliers = $suppliersQuery->orderBy('supplier_name')->get();
        $statuses = PurchaseStatus::cases();

        return view('gudang.purchases.index', compact(
            'purchases',
            'suppliers',
            'statuses',
            'search',
            'supplierFilter',
            'statusFilter',
            'dateFrom',
            'dateTo',
            'service'
        ));
    }

    public function create(Request $request, BatchMaterialPlanningService $mrpService): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $suppliersQuery = $outletId !== null
            ? Supplier::forOutlet($outletId)
            : Supplier::query();
        $suppliers = $suppliersQuery->with(['materials.unit'])->where('active', true)->orderBy('supplier_name')->get();

        $materialsQuery = $outletId !== null
            ? Material::where('outlet_id', $outletId)
            : Material::query();
        $materials = $materialsQuery->with('unit')->where('active', true)->orderBy('material_name')->get();

        $batch = null;
        $prefillItems = [];

        if ($batchId = $request->query('batch_id')) {
            $batch = ProductionBatch::with(['orderItem.order.customer', 'orderItem.variant.product'])->find($batchId);
            if ($batch) {
                if ($outletId !== null && ! $batch->hasConsistentTenantRelations($outletId)) {
                    abort(404, 'Batch produksi tidak ditemukan atau bukan milik outlet aktif.');
                }

                $mrpData = $mrpService->getMaterialRequirementsData($batch);
                $activeMaterials = Material::whereIn('id', array_column($mrpData['items'], 'material_id'))
                    ->when($outletId !== null, fn ($q) => $q->where('outlet_id', $outletId))
                    ->where('active', true)
                    ->pluck('id')
                    ->all();

                foreach ($mrpData['items'] as $item) {
                    if (bccomp((string) $item['shortage_qty'], '0', 4) > 0 && in_array($item['material_id'], $activeMaterials, true)) {
                        $latestPrice = \App\Models\Inventory\PurchaseItem::where('material_id', $item['material_id'])
                            ->where('unit_price', '>', 0)
                            ->latest('id')
                            ->value('unit_price');

                        $prefillItems[] = [
                            'material_id'     => $item['material_id'],
                            'material_name'   => $item['material_name'],
                            'unit_symbol'     => $item['symbol'] ?? '',
                            'qty'             => $item['shortage_qty'],
                            'unit_price'      => $latestPrice ? (float) $latestPrice : '',
                            'suggested_price' => $latestPrice ? (float) $latestPrice : null,
                        ];
                    }
                }
            }
        }

        $suggestedSupplierId = null;
        if (! empty($prefillItems)) {
            $neededMaterialIds = array_column($prefillItems, 'material_id');
            $matchingSupplier = $suppliers->sortByDesc(function ($s) use ($neededMaterialIds) {
                return $s->materials->pluck('id')->intersect($neededMaterialIds)->count();
            })->first();

            if ($matchingSupplier && $matchingSupplier->materials->pluck('id')->intersect($neededMaterialIds)->isNotEmpty()) {
                $suggestedSupplierId = $matchingSupplier->id;
            }
        }

        return view('gudang.purchases.create', compact('suppliers', 'materials', 'batch', 'prefillItems', 'suggestedSupplierId'));
    }

    public function store(StorePurchaseRequest $request, PurchaseOrderService $service): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        $data = $request->validated();
        if ($outletId !== null) {
            $data['outlet_id'] = $outletId;
        }

        try {
            $purchase = $service->createPurchase($data);

            return redirect()->route('gudang.purchases.show', $purchase)
                ->with('success', "Purchase Order {$purchase->purchase_no} berhasil dibuat sebagai DRAFT.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (UniqueConstraintViolationException $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Nomor dokumen mengalami konflik saat dibuat. Silakan coba kembali.');
        }
    }

    public function show(Purchase $purchase, PurchaseOrderService $service): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        $purchase->load([
            'supplier',
            'items.material.unit',
            'payments.createdByUser',
            'submittedByUser',
            'approvedByUser',
            'approvalHistories.user',
            'batch',
        ]);

        $totalAmount = $service->calculateTotal($purchase);
        $canEdit = $service->canEdit($purchase);
        $canSubmitApproval = $service->canSubmitApproval($purchase);
        $canConfirm = $service->canConfirm($purchase);
        $canCancel = $service->canCancel($purchase);
        $canReceive = ($purchase->status === PurchaseStatus::ORDERED && $purchase->isFullyPaid());

        return view('gudang.purchases.show', compact(
            'purchase',
            'totalAmount',
            'canEdit',
            'canSubmitApproval',
            'canConfirm',
            'canCancel',
            'canReceive',
            'service'
        ));
    }

    public function edit(Purchase $purchase, PurchaseOrderService $service): View|RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        if (! $service->canEdit($purchase)) {
            return redirect()->route('gudang.purchases.show', $purchase)
                ->with('error', "Purchase Order dengan status {$purchase->status->label()} tidak dapat diedit.");
        }

        $purchase->load(['supplier', 'items.material.unit']);

        $suppliersQuery = $outletId !== null
            ? Supplier::forOutlet($outletId)
            : Supplier::query();

        $suppliers = $suppliersQuery->with(['materials.unit'])
            ->where(function ($q) use ($purchase) {
                $q->where('active', true)
                  ->orWhere('id', $purchase->supplier_id);
            })
            ->orderBy('supplier_name')
            ->get();

        $existingMaterialIds = $purchase->items->pluck('material_id')->all();
        $materialsQuery = $outletId !== null
            ? Material::where('outlet_id', $outletId)
            : Material::query();

        $materials = $materialsQuery->with('unit')
            ->where(function ($q) use ($existingMaterialIds) {
                $q->where('active', true)
                  ->orWhereIn('id', $existingMaterialIds);
            })
            ->orderBy('material_name')
            ->get();

        return view('gudang.purchases.edit', compact('purchase', 'suppliers', 'materials'));
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase, PurchaseOrderService $service): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        if (! $service->canEdit($purchase)) {
            return redirect()->route('gudang.purchases.show', $purchase)
                ->with('error', 'Purchase Order tidak dapat diedit pada status saat ini.');
        }

        try {
            $updated = $service->updatePurchase($purchase, $request->validated());

            return redirect()->route('gudang.purchases.show', $updated)
                ->with('success', "Perubahan Purchase Order {$updated->purchase_no} berhasil disimpan.");
        } catch (DomainException $e) {
            return redirect()->route('gudang.purchases.show', $purchase)
                ->with('error', $e->getMessage());
        }
    }

    public function submitApproval(Purchase $purchase, PurchaseApprovalService $approvalService): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        try {
            $submitted = $approvalService->submitForApproval($purchase, auth()->user());

            return redirect()->route('gudang.purchases.show', $submitted)
                ->with('success', "Purchase Order {$submitted->purchase_no} berhasil diajukan untuk persetujuan Admin.");
        } catch (DomainException $e) {
            return redirect()->route('gudang.purchases.show', $purchase)
                ->with('error', $e->getMessage());
        }
    }

    public function receive(Purchase $purchase, PurchaseReceivingService $receivingService): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        try {
            $received = $receivingService->receivePurchase($purchase, auth()->id());

            return redirect()->route('gudang.purchases.show', $received)
                ->with('success', 'Barang berhasil diterima. Stok dan harga rata-rata material telah diperbarui.');
        } catch (DomainException $e) {
            return redirect()->route('gudang.purchases.show', $purchase)
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(Purchase $purchase, PurchaseOrderService $service): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        try {
            $cancelled = $service->cancelPurchase($purchase);

            return redirect()->route('gudang.purchases.show', $cancelled)
                ->with('success', "Purchase Order {$cancelled->purchase_no} berhasil dibatalkan.");
        } catch (DomainException $e) {
            return redirect()->route('gudang.purchases.show', $purchase)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Resolve active tenant outlet ID when tenant isolation is enabled.
     * Fails closed (aborts 403) when enabled but user/tenant is invalid.
     */
    protected function getActiveTenantOutletId(): ?int
    {
        if (! config('nayara.tenant_isolation_enabled', false)) {
            return null;
        }

        try {
            return app(TenantContext::class)->currentOutletId();
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak memiliki akses tenant yang sah.');
        }
    }
}
