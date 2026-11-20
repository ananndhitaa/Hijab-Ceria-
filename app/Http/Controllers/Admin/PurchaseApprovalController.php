<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\Inventory\Purchase;
use App\Services\Finance\FinancialRecordService;
use App\Services\Inventory\BatchMaterialPlanningService;
use App\Services\Inventory\PurchaseApprovalService;
use App\Services\System\TenantContext;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseApprovalController extends Controller
{
    /**
     * Display a queue of Purchase Orders pending Admin approval.
     */
    public function index(Request $request): View
    {
        $outletId = $this->getActiveTenantOutletId();

        $statusFilter = $request->input('status', PurchaseApprovalStatus::PENDING_APPROVAL->value);
        $search = $request->input('search');

        $baseQuery = $outletId !== null
            ? Purchase::forOutlet($outletId)
            : Purchase::query();

        $query = (clone $baseQuery)->with(['supplier', 'items.material.unit', 'submittedByUser'])
            ->when($statusFilter && $statusFilter !== 'ALL', function ($q) use ($statusFilter) {
                $q->where('approval_status', $statusFilter);
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('purchase_no', 'like', "%{$search}%")
                       ->orWhereHas('supplier', function ($supQuery) use ($search) {
                           $supQuery->where('supplier_name', 'like', "%{$search}%");
                       });
                });
            })
            ->latest('submitted_at')
            ->latest('id');

        $purchases = $query->paginate(15)->withQueryString();

        $pendingCount = (clone $baseQuery)->where('approval_status', PurchaseApprovalStatus::PENDING_APPROVAL)->count();
        $approvedCount = (clone $baseQuery)->where('approval_status', PurchaseApprovalStatus::APPROVED)->count();
        $revisionCount = (clone $baseQuery)->where('approval_status', PurchaseApprovalStatus::REVISION_REQUIRED)->count();
        $rejectedCount = (clone $baseQuery)->where('approval_status', PurchaseApprovalStatus::REJECTED)->count();

        return view('admin.purchases.approvals.index', compact(
            'purchases',
            'statusFilter',
            'search',
            'pendingCount',
            'approvedCount',
            'revisionCount',
            'rejectedCount'
        ));
    }

    /**
     * Display operational & financial review context for a Purchase Order.
     */
    public function show(
        Purchase $purchase,
        FinancialRecordService $financeService,
        BatchMaterialPlanningService $mrpService
    ): View {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        $purchase->load([
            'supplier',
            'items.material.unit',
            'items.material.stock',
            'batch.orderItem.variant.product',
            'batch.orderItem.order.customer',
            'submittedByUser',
            'approvedByUser',
            'approvalHistories.user',
            'payments.createdByUser',
        ]);

        // Operational Context (MRP / Manual)
        $mrpMap = [];
        $isManual = ($purchase->batch === null);

        if ($purchase->batch) {
            $mrpData = $mrpService->getMaterialRequirementsData($purchase->batch);
            foreach ($mrpData['items'] as $item) {
                $mrpMap[$item['material_id']] = $item;
            }
        }

        // Financial Context (Read-Only)
        $evalOutletId = config('nayara.tenant_isolation_enabled', false) ? $outletId : null;
        $finance = $financeService->calculateAvailableToCommit($purchase->id, $evalOutletId);
        $currentCash = $finance['current_cash'];
        $committedUnpaid = $finance['committed_unpaid'];
        $availableToCommit = $finance['available_to_commit'];
        $poTotal = $purchase->total_amount_string;
        $proyeksiSisa = bcsub($availableToCommit, $poTotal, 2);
        $isSufficient = ($finance['has_opening'] ?? false) && bccomp($availableToCommit, $poTotal, 2) >= 0;

        return view('admin.purchases.approvals.show', compact(
            'purchase',
            'isManual',
            'mrpMap',
            'currentCash',
            'committedUnpaid',
            'availableToCommit',
            'poTotal',
            'proyeksiSisa',
            'isSufficient',
            'finance'
        ));
    }

    /**
     * Admin approves a Purchase Order.
     */
    public function approve(
        Request $request,
        Purchase $purchase,
        PurchaseApprovalService $approvalService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $approved = $approvalService->approvePurchase(
                $purchase,
                $request->user(),
                $request->input('review_note')
            );

            return redirect()->route('admin.purchases.approvals.show', $approved)
                ->with('success', "Purchase Order {$approved->purchase_no} berhasil disetujui (APPROVED). Admin dapat memproses pembayaran supplier.");
        } catch (DomainException $e) {
            return redirect()->route('admin.purchases.approvals.show', $purchase)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Admin requests revision to Gudang.
     */
    public function requestRevision(
        Request $request,
        Purchase $purchase,
        PurchaseApprovalService $approvalService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        $request->validate([
            'review_note' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'review_note.required' => 'Catatan revisi wajib diisi agar Gudang mengetahui poin perbaikan.',
            'review_note.min'      => 'Catatan revisi minimal 3 karakter.',
        ]);

        try {
            $revised = $approvalService->requestRevision(
                $purchase,
                $request->user(),
                $request->input('review_note')
            );

            return redirect()->route('admin.purchases.approvals.index')
                ->with('success', "Permintaan revisi untuk PO {$revised->purchase_no} berhasil dikirim ke Gudang.");
        } catch (DomainException $e) {
            return redirect()->route('admin.purchases.approvals.show', $purchase)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Admin rejects a Purchase Order.
     */
    public function reject(
        Request $request,
        Purchase $purchase,
        PurchaseApprovalService $approvalService
    ): RedirectResponse {
        $outletId = $this->getActiveTenantOutletId();

        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reason.required' => 'Alasan penolakan PO wajib diisi.',
            'reason.min'      => 'Alasan penolakan minimal 3 karakter.',
        ]);

        try {
            $rejected = $approvalService->rejectPurchase(
                $purchase,
                $request->user(),
                $request->input('reason')
            );

            return redirect()->route('admin.purchases.approvals.index')
                ->with('success', "Purchase Order {$rejected->purchase_no} telah ditolak.");
        } catch (DomainException $e) {
            return redirect()->route('admin.purchases.approvals.show', $purchase)
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
