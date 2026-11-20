<?php

namespace App\Services\Inventory;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseApprovalHistory;
use App\Models\System\User;
use App\Services\Finance\FinancialRecordService;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseApprovalService
{
    public function __construct(
        protected FinancialRecordService $financialRecordService
    ) {}

    /**
     * Submit a DRAFT Purchase Order for Admin approval.
     *
     * @param Purchase $purchase
     * @param User $user
     * @return Purchase
     * @throws DomainException
     */
    public function submitForApproval(Purchase $purchase, User $user): Purchase
    {
        if (! in_array($user->role, [UserRole::GUDANG, UserRole::ADMIN], true)) {
            throw new DomainException('Hanya Gudang atau Admin yang berwenang mengajukan Purchase Order.');
        }

        return DB::transaction(function () use ($purchase, $user) {
            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (config('nayara.tenant_isolation_enabled', false)) {
                $outletId = app(\App\Services\System\TenantContext::class)->outletIdFor($user);
                if (! $lockedPurchase->hasConsistentTenantRelations($outletId)) {
                    abort(404);
                }
            }

            if ($lockedPurchase->status !== PurchaseStatus::DRAFT) {
                throw new DomainException("Hanya PO berstatus DRAFT yang dapat diajukan untuk persetujuan. Status saat ini: {$lockedPurchase->status->label()}.");
            }

            if (! in_array($lockedPurchase->approval_status, [PurchaseApprovalStatus::NOT_SUBMITTED, PurchaseApprovalStatus::REVISION_REQUIRED], true)) {
                throw new DomainException("PO tidak dapat diajukan untuk persetujuan dalam status: {$lockedPurchase->approval_status->label()}.");
            }

            $supplier = $lockedPurchase->supplier;
            if (! $supplier) {
                throw new DomainException('Supplier tidak ditemukan.');
            }
            if (! $supplier->active) {
                throw new DomainException('Supplier tidak aktif. Silakan pilih supplier yang aktif sebelum mengajukan persetujuan.');
            }

            $items = $lockedPurchase->items()->with('material')->get();
            if ($items->isEmpty()) {
                throw new DomainException('Purchase Order harus memiliki minimal 1 item material sebelum diajukan.');
            }

            $totalAmount = '0.00';
            foreach ($items as $item) {
                $material = $item->material;
                $matName = $material?->material_name ?? "ID #{$item->material_id}";

                if (! $material || ! $material->active) {
                    throw new DomainException("Material \"{$matName}\" tidak aktif.");
                }

                if (bccomp((string) $item->qty, '0', 4) <= 0) {
                    throw new DomainException("Qty untuk material \"{$matName}\" harus lebih besar dari 0.");
                }

                if (bccomp((string) $item->unit_price, '0', 2) <= 0) {
                    throw new DomainException("Harga beli untuk material \"{$matName}\" harus lebih besar dari 0.");
                }

                $subtotal = bcmul((string) $item->qty, (string) $item->unit_price, 2);
                $totalAmount = bcadd($totalAmount, $subtotal, 2);
            }

            if (bccomp($totalAmount, '0.00', 2) <= 0) {
                throw new DomainException('Total nilai Purchase Order harus lebih besar dari Rp 0.');
            }

            $fromStatus = $lockedPurchase->approval_status;
            $now = now('Asia/Jakarta');

            $lockedPurchase->update([
                'approval_status' => PurchaseApprovalStatus::PENDING_APPROVAL,
                'submitted_at'    => $now,
                'submitted_by'    => $user->id,
            ]);

            PurchaseApprovalHistory::create([
                'purchase_id' => $lockedPurchase->id,
                'action'      => $fromStatus === PurchaseApprovalStatus::REVISION_REQUIRED ? 'RESUBMITTED' : 'SUBMITTED',
                'from_status' => $fromStatus->value,
                'to_status'   => PurchaseApprovalStatus::PENDING_APPROVAL->value,
                'user_id'     => $user->id,
                'note'        => null,
                'metadata'    => [
                    'total_amount' => $totalAmount,
                    'items_count'  => $items->count(),
                ],
            ]);

            return $lockedPurchase->fresh(['supplier', 'items.material.unit', 'submittedByUser']);
        });
    }

    /**
     * Admin approves a PENDING_APPROVAL Purchase Order.
     * Hard-blocks approval if available-to-commit funds are insufficient.
     *
     * @param Purchase $purchase
     * @param User $user
     * @param string|null $note
     * @return Purchase
     * @throws DomainException
     */
    public function approvePurchase(Purchase $purchase, User $user, ?string $note = null): Purchase
    {
        if ($user->role !== UserRole::ADMIN) {
            throw new DomainException('Hanya Admin yang berwenang menyetujui Purchase Order.');
        }

        return DB::transaction(function () use ($purchase, $user, $note) {
            $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

            if ($isIsolationEnabled) {
                $outletId = app(\App\Services\System\TenantContext::class)->outletIdFor($user);

                // Level 1: Stable Tenant Cash Mutex - Lock authorized tenant Outlet row first
                $lockedOutlet = \App\Models\System\Outlet::whereKey($outletId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedOutlet->isActive()) {
                    abort(403, 'Akses ditolak: Outlet tenant tidak aktif.');
                }
            } else {
                $outletId = null;
            }

            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($isIsolationEnabled) {
                if (! $lockedPurchase->hasConsistentTenantRelations($outletId)) {
                    abort(404);
                }

                // Level 3: Lock tenant-owned OpeningCashBalance record to serialize cash-dependent decisions
                $openingRecord = OpeningCashBalance::forOutlet($outletId)
                    ->lockForUpdate()
                    ->latest('opening_date')
                    ->latest('id')
                    ->first();

                if (! $openingRecord) {
                    throw new DomainException('Saldo awal kas tenant belum ditentukan. Persetujuan pembelian diblokir.');
                }
            } else {
                $outletId = $lockedPurchase->outlet_id;
            }

            if ($lockedPurchase->status !== PurchaseStatus::DRAFT) {
                throw new DomainException("Hanya PO berstatus DRAFT yang dapat disetujui. Status saat ini: {$lockedPurchase->status->label()}.");
            }

            if ($lockedPurchase->approval_status !== PurchaseApprovalStatus::PENDING_APPROVAL) {
                throw new DomainException("PO tidak dapat disetujui karena status persetujuan saat ini adalah: {$lockedPurchase->approval_status->label()}.");
            }

            $lockedPurchase->load('items');
            $poTotal = $lockedPurchase->total_amount_string;

            // Phase 8 & Phase 17: Canonical Multiple Commitment Guard
            $finance = $this->financialRecordService->calculateAvailableToCommit(
                $lockedPurchase->id,
                $outletId
            );

            if (config('nayara.tenant_isolation_enabled', false) && ! ($finance['has_opening'] ?? false)) {
                throw new DomainException('Saldo awal kas tenant belum ditentukan. Persetujuan pembelian diblokir.');
            }

            $availableToCommit = $finance['available_to_commit'];

            if (bccomp($availableToCommit, $poTotal, 2) < 0) {
                throw new DomainException('Dana tersedia belum mencukupi untuk menyetujui pembelian ini.');
            }

            $fromStatus = $lockedPurchase->approval_status;
            $now = now('Asia/Jakarta');

            $lockedPurchase->update([
                'approval_status' => PurchaseApprovalStatus::APPROVED,
                'approved_at'     => $now,
                'approved_by'     => $user->id,
                'review_note'     => $note,
            ]);

            PurchaseApprovalHistory::create([
                'purchase_id' => $lockedPurchase->id,
                'action'      => 'APPROVED',
                'from_status' => $fromStatus->value,
                'to_status'   => PurchaseApprovalStatus::APPROVED->value,
                'user_id'     => $user->id,
                'note'        => $note,
                'metadata'    => [
                    'po_total'            => $poTotal,
                    'available_to_commit' => $availableToCommit,
                    'current_cash'        => $finance['current_cash'],
                ],
            ]);

            return $lockedPurchase->fresh(['supplier', 'items.material.unit', 'approvedByUser']);
        });
    }

    /**
     * Admin requests revision for a PENDING_APPROVAL Purchase Order.
     * Review note is strictly required.
     *
     * @param Purchase $purchase
     * @param User $user
     * @param string $note
     * @return Purchase
     * @throws DomainException
     */
    public function requestRevision(Purchase $purchase, User $user, string $note): Purchase
    {
        if ($user->role !== UserRole::ADMIN) {
            throw new DomainException('Hanya Admin yang berwenang meminta revisi Purchase Order.');
        }

        return DB::transaction(function () use ($purchase, $user, $note) {
            $trimmedNote = trim($note);
            if (empty($trimmedNote)) {
                throw new DomainException('Catatan revisi wajib diisi agar Gudang mengetahui poin perbaikan.');
            }

            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (config('nayara.tenant_isolation_enabled', false)) {
                $outletId = app(\App\Services\System\TenantContext::class)->outletIdFor($user);
                if (! $lockedPurchase->hasConsistentTenantRelations($outletId)) {
                    abort(404);
                }
            }

            if ($lockedPurchase->status !== PurchaseStatus::DRAFT) {
                throw new DomainException("Hanya PO berstatus DRAFT yang dapat diminta revisi. Status saat ini: {$lockedPurchase->status->label()}.");
            }

            if ($lockedPurchase->approval_status !== PurchaseApprovalStatus::PENDING_APPROVAL) {
                throw new DomainException("PO tidak dapat diminta revisi karena status persetujuan saat ini adalah: {$lockedPurchase->approval_status->label()}.");
            }

            $fromStatus = $lockedPurchase->approval_status;

            $lockedPurchase->update([
                'approval_status' => PurchaseApprovalStatus::REVISION_REQUIRED,
                'review_note'     => $trimmedNote,
            ]);

            PurchaseApprovalHistory::create([
                'purchase_id' => $lockedPurchase->id,
                'action'      => 'REVISION_REQUESTED',
                'from_status' => $fromStatus->value,
                'to_status'   => PurchaseApprovalStatus::REVISION_REQUIRED->value,
                'user_id'     => $user->id,
                'note'        => $trimmedNote,
                'metadata'    => null,
            ]);

            return $lockedPurchase->fresh(['supplier', 'items.material.unit']);
        });
    }

    /**
     * Admin rejects a PENDING_APPROVAL Purchase Order.
     * Rejection reason is strictly required.
     *
     * @param Purchase $purchase
     * @param User $user
     * @param string $reason
     * @return Purchase
     * @throws DomainException
     */
    public function rejectPurchase(Purchase $purchase, User $user, string $reason): Purchase
    {
        if ($user->role !== UserRole::ADMIN) {
            throw new DomainException('Hanya Admin yang berwenang menolak Purchase Order.');
        }

        return DB::transaction(function () use ($purchase, $user, $reason) {
            $trimmedReason = trim($reason);
            if (empty($trimmedReason)) {
                throw new DomainException('Alasan penolakan PO wajib diisi.');
            }

            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (config('nayara.tenant_isolation_enabled', false)) {
                $outletId = app(\App\Services\System\TenantContext::class)->outletIdFor($user);
                if (! $lockedPurchase->hasConsistentTenantRelations($outletId)) {
                    abort(404);
                }
            }

            if ($lockedPurchase->status !== PurchaseStatus::DRAFT) {
                throw new DomainException("Hanya PO berstatus DRAFT yang dapat ditolak. Status saat ini: {$lockedPurchase->status->label()}.");
            }

            if ($lockedPurchase->approval_status !== PurchaseApprovalStatus::PENDING_APPROVAL) {
                throw new DomainException("PO tidak dapat ditolak karena status persetujuan saat ini adalah: {$lockedPurchase->approval_status->label()}.");
            }

            $fromStatus = $lockedPurchase->approval_status;

            $lockedPurchase->update([
                'approval_status' => PurchaseApprovalStatus::REJECTED,
                'review_note'     => $trimmedReason,
            ]);

            PurchaseApprovalHistory::create([
                'purchase_id' => $lockedPurchase->id,
                'action'      => 'REJECTED',
                'from_status' => $fromStatus->value,
                'to_status'   => PurchaseApprovalStatus::REJECTED->value,
                'user_id'     => $user->id,
                'note'        => $trimmedReason,
                'metadata'    => null,
            ]);

            return $lockedPurchase->fresh(['supplier', 'items.material.unit']);
        });
    }
}
