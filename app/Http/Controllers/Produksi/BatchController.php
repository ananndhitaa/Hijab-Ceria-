<?php

namespace App\Http\Controllers\Produksi;

use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Production\ProductionBatch;
use App\Services\Production\BatchWorkflowService;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();
        $employeeId = auth()->id();

        // Strict employee isolation: only batches assigned to this employee or unassigned
        $query = ProductionBatch::with([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'batchMaterials',
            'productionResult',
            'qcResult',
        ])->where(function ($q) use ($employeeId) {
            $q->where('production_employee_id', $employeeId)
                ->orWhereNull('production_employee_id');
        });

        if ($outletId !== null) {
            $query->forOutlet($outletId);
        }

        // Filter: Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_no', 'like', "%{$search}%")
                    ->orWhereHas('orderItem.order', function ($oq) use ($search) {
                        $oq->where('order_no', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('customer_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('orderItem.variant', function ($vq) use ($search) {
                        $vq->where('variant_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($pq) use ($search) {
                                $pq->where('product_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Filter: Status
        if ($status = $request->input('status')) {
            if ($statusEnum = BatchStatus::tryFrom($status)) {
                $query->where('status', $statusEnum->value);
            }
        }

        // Filter: Route
        if ($route = $request->input('route')) {
            if ($route === 'printing') {
                $query->where(function ($q) {
                    $q->where('requires_printing', true)
                        ->orWhere(function ($sq) {
                            $sq->whereNull('requires_printing')
                                ->whereHas('orderItem.variant', fn ($vq) => $vq->where('requires_printing', true));
                        });
                });
            } elseif ($route === 'plain') {
                $query->where(function ($q) {
                    $q->where('requires_printing', false)
                        ->orWhere(function ($sq) {
                            $sq->whereNull('requires_printing')
                                ->whereHas('orderItem.variant', fn ($vq) => $vq->where('requires_printing', false));
                        });
                });
            }
        }

        $batches = $query->latest('id')->paginate(15)->withQueryString();

        return view('produksi.batches.index', compact('batches'));
    }

    public function show(ProductionBatch $batch): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        // Strict employee isolation: cannot access batches assigned to other employees
        if ($batch->production_employee_id !== null && (int) $batch->production_employee_id !== (int) auth()->id()) {
            abort(403, 'Akses ditolak: Batch ini ditugaskan kepada karyawan lain.');
        }

        $batch->load([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'batchMaterials.material.unit',
            'productionResult',
            'qcResult',
            'picHistories.assigner',
            'picHistories.fromEmployee',
            'picHistories.toEmployee',
        ]);

        return view('produksi.batches.show', compact('batch'));
    }

    public function start(Request $request, ProductionBatch $batch, BatchWorkflowService $workflowService): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        // Strict employee isolation: cannot access batches assigned to other employees
        if ($batch->production_employee_id !== null && (int) $batch->production_employee_id !== (int) auth()->id()) {
            abort(403, 'Akses ditolak: Batch ini ditugaskan kepada karyawan lain.');
        }

        try {
            $updatedBatch = $workflowService->startBatch($batch, $request->user());

            return redirect()->route('produksi.batches.show', $updatedBatch)
                ->with('success', "Batch {$updatedBatch->batch_no} berhasil dimulai ke tahap {$updatedBatch->status->label()}.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function completePrinting(Request $request, ProductionBatch $batch, BatchWorkflowService $workflowService): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        // Strict employee isolation: cannot access batches assigned to other employees
        if ($batch->production_employee_id !== null && (int) $batch->production_employee_id !== (int) auth()->id()) {
            abort(403, 'Akses ditolak: Batch ini ditugaskan kepada karyawan lain.');
        }

        try {
            $updatedBatch = $workflowService->completePrinting($batch, $request->user());

            return redirect()->route('produksi.batches.show', $updatedBatch)
                ->with('success', "Batch {$updatedBatch->batch_no} selesai tahap Printing dan lanjut ke tahap Produksi.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function completePacking(Request $request, ProductionBatch $batch, BatchWorkflowService $workflowService): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        // Strict employee isolation: cannot access batches assigned to other employees
        if ($batch->production_employee_id !== null && (int) $batch->production_employee_id !== (int) auth()->id()) {
            abort(403, 'Akses ditolak: Batch ini ditugaskan kepada karyawan lain.');
        }

        try {
            $updatedBatch = $workflowService->completePacking($batch, $request->user());

            return redirect()->route('produksi.batches.show', $updatedBatch)
                ->with('success', "Tahap Packing untuk Batch {$updatedBatch->batch_no} selesai dan status batch menjadi SELESAI.");
        } catch (DomainException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display completed production history strictly isolated to the logged-in employee (Bagian 6).
     */
    public function history(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();
        $employeeId = auth()->id();

        $query = ProductionBatch::with([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'productionResult.recordedByUser',
            'qcResult.qcByUser',
            'picHistories' => fn ($q) => $q->where('to_employee_id', $employeeId)->latest('assigned_at'),
        ])
            ->where('production_employee_id', $employeeId)
            ->where(function ($q) {
                $q->where('status', BatchStatus::SELESAI->value)
                    ->orWhereHas('productionResult');
            });

        if ($outletId !== null) {
            $query->forOutlet($outletId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_no', 'like', "%{$search}%")
                    ->orWhereHas('orderItem.order', function ($oq) use ($search) {
                        $oq->where('order_no', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('customer_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('orderItem.variant', function ($vq) use ($search) {
                        $vq->where('variant_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($pq) use ($search) {
                                $pq->where('product_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $batches = $query->latest('id')->paginate(15)->withQueryString();

        // Personal history statistics
        $totalCompleted = ProductionBatch::where('production_employee_id', $employeeId)
            ->where('status', BatchStatus::SELESAI->value)
            ->count();

        $totalProducedQty = ProductionBatch::where('production_employee_id', $employeeId)
            ->whereHas('productionResult')
            ->with('productionResult')
            ->get()
            ->sum(fn ($b) => (float) ($b->productionResult?->actual_qty ?? 0));

        return view('produksi.batches.history', compact('batches', 'totalCompleted', 'totalProducedQty'));
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
