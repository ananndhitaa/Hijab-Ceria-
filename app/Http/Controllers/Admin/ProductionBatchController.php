<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductionBatchRequest;
use App\Models\Production\ProductionBatch;
use App\Models\Sales\Order;
use App\Services\Production\BatchService;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionBatchController extends Controller
{
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $search       = $request->input('search');
        $statusFilter = $request->input('status');

        $query = ProductionBatch::with([
            'orderItem.order.customer',
            'orderItem.variant.product',
        ]);

        if ($outletId !== null) {
            $query->forOutlet($outletId);
        }

        $batches = $query
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('batch_no', 'like', "%{$search}%")
                        ->orWhereHas('orderItem.order', function ($oq) use ($search) {
                            $oq->where('order_no', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($cq) use ($search) {
                                    $cq->where('customer_name', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('orderItem.variant', function ($vq) use ($search) {
                            $vq->where('sku', 'like', "%{$search}%")
                                ->orWhere('variant_name', 'like', "%{$search}%")
                                ->orWhereHas('product', function ($pq) use ($search) {
                                    $pq->where('product_name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($statusFilter, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $statuses = BatchStatus::cases();

        return view('admin.batches.index', compact('batches', 'search', 'statusFilter', 'statuses'));
    }

    public function create(Order $order, BatchService $batchService): View|RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($order->outlet_id !== null && (int) $order->outlet_id === (int) $outletId, 404);

            if (request()->has('order_item_id')) {
                $requestedItemId = request()->query('order_item_id');
                $itemExistsOnOrder = $order->items()->where('id', $requestedItemId)->exists();
                abort_unless($itemExistsOnOrder, 404);
            }

            if ($order->customer && $order->customer->outlet_id !== null && (int) $order->customer->outlet_id !== (int) $outletId) {
                abort(404);
            }

            foreach ($order->items()->with(['variant.product'])->get() as $item) {
                if ($item->variant && $item->variant->outlet_id !== null && (int) $item->variant->outlet_id !== (int) $outletId) {
                    abort(404);
                }
                if ($item->variant?->product && $item->variant->product->outlet_id !== null && (int) $item->variant->product->outlet_id !== (int) $outletId) {
                    abort(404);
                }
            }
        }

        $order->load(['customer', 'items.variant.product', 'items.productionBatches', 'invoice.payments']);

        if (! $batchService->canCreateForOrder($order)) {
            $errorMessage = ! $order->hasConfirmedDownPayment()
                ? 'Batch produksi belum dapat dibuat karena pembayaran DP belum diterima.'
                : 'Pesanan tidak memenuhi syarat untuk pembuatan batch produksi (status harus DEAL atau BAHAN, dan memiliki kuantitas yang belum dijadwalkan).';

            return redirect()->route('admin.orders.show', $order)
                ->with('error', $errorMessage);
        }

        $productionUsersQuery = \App\Models\System\User::where('role', \App\Enums\UserRole::PRODUKSI)
            ->where('status', \App\Enums\UserStatus::ACTIVE);

        if ($outletId !== null) {
            $productionUsersQuery->where('outlet_id', $outletId);
        }

        $productionUsers = $productionUsersQuery->orderBy('name')->get();
        $employeeWorkloads = app(\App\Services\Owner\ProductionPerformanceService::class)->getProductionEmployeesWorkload($outletId);

        return view('admin.batches.create', compact('order', 'batchService', 'productionUsers', 'employeeWorkloads'));
    }

    public function store(
        StoreProductionBatchRequest $request,
        Order $order,
        BatchService $batchService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($order->outlet_id !== null && (int) $order->outlet_id === (int) $outletId, 404);

            if ($order->customer && $order->customer->outlet_id !== null && (int) $order->customer->outlet_id !== (int) $outletId) {
                abort(404);
            }
        }

        try {
            $result = $batchService->createBatch($order, $request->validated(), $request->user());
            $batch = $result['batch'];
            $wasDeal = $result['was_deal'];

            $message = $wasDeal
                ? "Batch produksi {$batch->batch_no} berhasil dibuat dan status pesanan berpindah ke BAHAN. Kebutuhan bahan telah dihitung otomatis berdasarkan BOM varian."
                : "Batch produksi {$batch->batch_no} berhasil dibuat. Kebutuhan bahan telah dihitung otomatis berdasarkan BOM varian.";

            return redirect()->route('admin.batches.show', $batch)
                ->with('success', $message);
        } catch (DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (UniqueConstraintViolationException $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Nomor batch mengalami konflik saat dibuat. Silakan coba kembali.');
        }
    }

    public function show(ProductionBatch $batch): View
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        $batch->load([
            'orderItem.order.customer',
            'orderItem.variant.product',
            'batchMaterials.material',
            'productionEmployee',
            'picHistories.fromEmployee',
            'picHistories.toEmployee',
            'picHistories.assigner',
            'productionResult',
            'qcResult',
            'productionResults.qcResult',
            'closedByUser',
        ]);

        $employeeWorkloads = app(\App\Services\Owner\ProductionPerformanceService::class)->getProductionEmployeesWorkload($outletId);

        return view('admin.batches.show', compact('batch', 'employeeWorkloads'));
    }

    public function reassignPic(Request $request, ProductionBatch $batch, BatchService $batchService): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        $validated = $request->validate([
            'production_employee_id' => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($query) use ($outletId) {
                    $query->where('role', \App\Enums\UserRole::PRODUKSI->value);
                    if ($outletId !== null) {
                        $query->where('outlet_id', $outletId);
                    }
                }),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'production_employee_id.exists' => 'Karyawan yang dipilih bukan role Produksi yang sah pada outlet ini.',
        ]);

        $newId = ! empty($validated['production_employee_id']) ? (int) $validated['production_employee_id'] : null;
        $batchService->reassignPic($batch, $newId, $request->user(), $validated['notes'] ?? null);

        return redirect()->back()->with('success', "PIC Batch {$batch->batch_no} berhasil diperbarui.");
    }

    /**
     * Resume production on the same batch with the same PIC to fulfill shortfall.
     */
    public function resumeProduction(
        Request $request,
        ProductionBatch $batch,
        \App\Services\Production\BatchWorkflowService $workflowService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        try {
            $workflowService->resumeProduction($batch, $request->user(), $request->input('notes'));

            return redirect()->route('admin.batches.show', $batch)
                ->with('success', "Batch {$batch->batch_no} berhasil dibuka kembali ke tahap Produksi untuk memenuhi sisa target.");
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Close a batch partially with required reason and approval.
     */
    public function partialClose(
        Request $request,
        ProductionBatch $batch,
        \App\Services\Production\BatchWorkflowService $workflowService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($batch->hasConsistentTenantRelations($outletId), 404);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reason.required' => 'Alasan kekurangan / penutupan parsial wajib diisi.',
            'reason.min'      => 'Alasan penutupan parsial minimal 3 karakter.',
            'reason.max'      => 'Alasan penutupan parsial maksimal 1000 karakter.',
        ]);

        try {
            $workflowService->closeBatchPartially($batch, $validated['reason'], $request->user());

            return redirect()->route('admin.batches.show', $batch)
                ->with('success', "Batch {$batch->batch_no} berhasil diselesaikan secara parsial (SELESAI_PARSIAL).");
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
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
