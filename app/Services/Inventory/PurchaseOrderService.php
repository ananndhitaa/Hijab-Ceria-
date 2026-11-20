<?php

namespace App\Services\Inventory;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchaseApprovalHistory;
use App\Models\Inventory\PurchaseItem;
use App\Models\Master\Material;
use App\Models\Master\Supplier;
use App\Services\System\DocumentNumberGenerator;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseOrderService
{
    public function __construct(
        protected DocumentNumberGenerator $documentNumberGenerator
    ) {}

    /**
     * Create a new Purchase Order in DRAFT status.
     *
     * @param array $data
     * @return Purchase
     * @throws DomainException|InvalidArgumentException
     */
    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $supplierId = $data['supplier_id'];
            $supplier = Supplier::find($supplierId);

            if (! $supplier) {
                throw new DomainException('Supplier tidak ditemukan.');
            }

            if (! $supplier->active) {
                throw new DomainException('Supplier yang dipilih tidak aktif. Silakan pilih supplier yang aktif.');
            }

            $isolationEnabled = config('nayara.tenant_isolation_enabled', false);
            $outletId = $data['outlet_id'] ?? null;

            if ($isolationEnabled && $outletId !== null) {
                if ($supplier->outlet_id === null || (int) $supplier->outlet_id !== (int) $outletId) {
                    throw new DomainException('Supplier tidak valid atau bukan milik outlet aktif.');
                }

                if (! empty($data['batch_id'])) {
                    $batch = \App\Models\Production\ProductionBatch::find($data['batch_id']);
                    if (! $batch || ! $batch->hasConsistentTenantRelations((int) $outletId)) {
                        throw new DomainException('Batch produksi tidak valid atau bukan milik outlet aktif.');
                    }
                }
            }

            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new DomainException('Purchase Order harus memiliki minimal 1 item material.');
            }

            // Check duplicate materials in request
            $materialIds = array_column($items, 'material_id');
            if (count($materialIds) !== count(array_unique($materialIds))) {
                throw new DomainException('Terdapat material duplikat dalam daftar pesanan pembelian.');
            }

            // Verify all materials are active on creation
            $materials = Material::whereIn('id', $materialIds)->get()->keyBy('id');
            foreach ($items as $item) {
                $material = $materials->get($item['material_id']);
                if (! $material) {
                    throw new DomainException("Material ID {$item['material_id']} tidak ditemukan.");
                }
                if (! $material->active) {
                    throw new DomainException("Material \"{$material->material_name}\" tidak aktif dan tidak dapat dipilih untuk PO baru.");
                }
                if ($isolationEnabled && $outletId !== null) {
                    if ($material->outlet_id === null || (int) $material->outlet_id !== (int) $outletId) {
                        throw new DomainException("Material \"{$material->material_name}\" tidak valid atau bukan milik outlet aktif.");
                    }
                }
                if (bccomp((string) ($item['qty'] ?? '0'), '0', 4) <= 0) {
                    throw new DomainException('Jumlah kuantitas (qty) harus lebih besar dari 0.');
                }
                if (bccomp((string) ($item['unit_price'] ?? '0'), '0', 2) <= 0) {
                    throw new DomainException('Harga beli satuan harus lebih besar dari 0.');
                }
            }

            $purchaseNo = $this->documentNumberGenerator->generate('PO');

            $purchase = Purchase::create([
                'outlet_id'       => $outletId,
                'supplier_id'     => $supplier->id,
                'batch_id'        => $data['batch_id'] ?? null,
                'purchase_no'     => $purchaseNo,
                'purchase_date'   => $data['purchase_date'],
                'status'          => PurchaseStatus::DRAFT,
                'approval_status' => PurchaseApprovalStatus::NOT_SUBMITTED,
                'notes'           => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                PurchaseItem::create([
                    'purchase_id'  => $purchase->id,
                    'material_id'  => (int) $item['material_id'],
                    'qty'          => (string) $item['qty'],
                    'unit_price'   => (string) $item['unit_price'],
                    'qty_received' => '0.0000',
                ]);
            }

            return $purchase->load(['supplier', 'items.material.unit']);
        });
    }

    /**
     * Update an existing DRAFT Purchase Order.
     *
     * @param Purchase $purchase
     * @param array $data
     * @return Purchase
     * @throws DomainException
     */
    public function updatePurchase(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {
            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $isolationEnabled = config('nayara.tenant_isolation_enabled', false);
            if ($isolationEnabled) {
                $outletId = app(\App\Services\System\TenantContext::class)->currentOutletId();
                if (! $lockedPurchase->hasConsistentTenantRelations($outletId)) {
                    abort(404);
                }
            }

            if ($lockedPurchase->status !== PurchaseStatus::DRAFT) {
                throw new DomainException("PO dengan status {$lockedPurchase->status->label()} tidak dapat diubah.");
            }

            if ($lockedPurchase->approval_status === PurchaseApprovalStatus::PENDING_APPROVAL) {
                throw new DomainException('PO sedang menunggu persetujuan Owner dan tidak dapat diubah.');
            }

            if ($lockedPurchase->approval_status === PurchaseApprovalStatus::REJECTED) {
                throw new DomainException('PO yang telah ditolak tidak dapat diubah.');
            }

            if ($lockedPurchase->payments()->exists()) {
                throw new DomainException('PO sudah memiliki catatan pembayaran supplier dan tidak dapat diubah.');
            }

            $newSupplierId = (int) $data['supplier_id'];
            $supplier = Supplier::find($newSupplierId);
            if (! $supplier) {
                throw new DomainException('Supplier tidak ditemukan.');
            }

            if ($isolationEnabled && $lockedPurchase->outlet_id !== null) {
                if ($supplier->outlet_id === null || (int) $supplier->outlet_id !== (int) $lockedPurchase->outlet_id) {
                    throw new DomainException('Supplier tidak valid atau bukan milik outlet aktif.');
                }
            }

            // If supplier was changed, it must be active
            if ($newSupplierId !== (int) $lockedPurchase->supplier_id && ! $supplier->active) {
                throw new DomainException('Supplier yang baru dipilih tidak aktif.');
            }

            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new DomainException('Purchase Order harus memiliki minimal 1 item material.');
            }

            // Check duplicate materials
            $materialIds = array_column($items, 'material_id');
            if (count($materialIds) !== count(array_unique($materialIds))) {
                throw new DomainException('Terdapat material duplikat dalam daftar pesanan pembelian.');
            }

            // Existing items map of this PO
            $existingItems = $lockedPurchase->items()->get()->keyBy('id');
            $existingItemIds = $existingItems->keys()->all();

            $suppliedItemIds = [];
            foreach ($items as $item) {
                if (! empty($item['purchase_item_id'])) {
                    $itemId = (int) $item['purchase_item_id'];
                    // Prevent cross-purchase injection
                    if (! in_array($itemId, $existingItemIds, true)) {
                        throw new DomainException('Item pembelian tidak valid atau bukan milik PO ini.');
                    }
                    if (in_array($itemId, $suppliedItemIds, true)) {
                        throw new DomainException('ID item pembelian duplikat dalam permintaan update.');
                    }
                    $suppliedItemIds[] = $itemId;
                }
            }

            // Validate materials
            $materials = Material::whereIn('id', $materialIds)->get()->keyBy('id');
            foreach ($items as $item) {
                $material = $materials->get($item['material_id']);
                if (! $material) {
                    throw new DomainException("Material ID {$item['material_id']} tidak ditemukan.");
                }

                $itemId = ! empty($item['purchase_item_id']) ? (int) $item['purchase_item_id'] : null;
                $isExistingItem = $itemId && $existingItems->has($itemId);
                $isSameMaterial = $isExistingItem && ((int) $existingItems->get($itemId)->material_id === (int) $material->id);

                // If adding new item or changing material on existing item, material must be active
                if (! $isSameMaterial && ! $material->active) {
                    throw new DomainException("Material \"{$material->material_name}\" tidak aktif dan tidak dapat dipilih.");
                }

                if ($isolationEnabled && $lockedPurchase->outlet_id !== null) {
                    if ($material->outlet_id === null || (int) $material->outlet_id !== (int) $lockedPurchase->outlet_id) {
                        throw new DomainException("Material \"{$material->material_name}\" tidak valid atau bukan milik outlet aktif.");
                    }
                }

                if (bccomp((string) ($item['qty'] ?? '0'), '0', 4) <= 0) {
                    throw new DomainException('Jumlah kuantitas (qty) harus lebih besar dari 0.');
                }
                if (bccomp((string) ($item['unit_price'] ?? '0'), '0', 2) <= 0) {
                    throw new DomainException('Harga beli satuan harus lebih besar dari 0.');
                }
            }

            // Phase 10: Revision / edits invalidate previous approval
            $oldApproval = $lockedPurchase->approval_status;
            $needsReapproval = in_array($oldApproval, [
                PurchaseApprovalStatus::APPROVED,
                PurchaseApprovalStatus::REVISION_REQUIRED,
            ], true);

            $updateData = [
                'supplier_id'   => $supplier->id,
                'purchase_date' => $data['purchase_date'],
                'notes'         => $data['notes'] ?? null,
            ];

            if ($needsReapproval) {
                $updateData['approval_status'] = PurchaseApprovalStatus::NOT_SUBMITTED;
                $updateData['approved_at'] = null;
                $updateData['approved_by'] = null;
            }

            // Update main purchase attributes (purchase_no remains immutable)
            $lockedPurchase->update($updateData);

            if ($needsReapproval && $oldApproval === PurchaseApprovalStatus::APPROVED) {
                PurchaseApprovalHistory::create([
                    'purchase_id' => $lockedPurchase->id,
                    'action'      => 'INVALIDATED',
                    'from_status' => $oldApproval->value,
                    'to_status'   => PurchaseApprovalStatus::NOT_SUBMITTED->value,
                    'user_id'     => auth()->id(),
                    'note'        => 'Data PO diubah, persetujuan Admin sebelumnya tidak berlaku lagi dan harus diajukan ulang.',
                    'metadata'    => null,
                ]);
            }

            // Atomically replace / update items
            $lockedPurchase->items()->delete();

            foreach ($items as $item) {
                PurchaseItem::create([
                    'purchase_id'  => $lockedPurchase->id,
                    'material_id'  => (int) $item['material_id'],
                    'qty'          => (string) $item['qty'],
                    'unit_price'   => (string) $item['unit_price'],
                    'qty_received' => '0.0000',
                ]);
            }

            return $lockedPurchase->load(['supplier', 'items.material.unit']);
        });
    }

    /**
     * Confirm a DRAFT Purchase Order to ORDERED status.
     *
     * @param Purchase $purchase
     * @return Purchase
     * @throws DomainException
     */
    public function confirmPurchase(Purchase $purchase): Purchase
    {
        throw new DomainException('Alur konfirmasi manual ke supplier telah ditiadakan. Purchase Order otomatis menjadi ORDERED (Dipesan) saat pembayaran telah diselesaikan oleh Admin.');
    }

    /**
     * Cancel a DRAFT or ORDERED Purchase Order.
     *
     * @param Purchase $purchase
     * @return Purchase
     * @throws DomainException
     */
    public function cancelPurchase(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            /** @var Purchase $lockedPurchase */
            $lockedPurchase = Purchase::query()
                ->whereKey($purchase->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (config('nayara.tenant_isolation_enabled', false)) {
                $outletId = app(\App\Services\System\TenantContext::class)->currentOutletId();
                if (! $lockedPurchase->hasConsistentTenantRelations($outletId)) {
                    abort(404);
                }
            }

            if ($lockedPurchase->status === PurchaseStatus::CANCELLED) {
                throw new DomainException('PO sudah dalam status Dibatalkan.');
            }

            if ($lockedPurchase->status === PurchaseStatus::RECEIVED) {
                throw new DomainException('PO yang sudah berstatus Diterima tidak dapat dibatalkan.');
            }

            // Protect against partial receiving if ORDERED
            $hasReceivedQty = $lockedPurchase->items()
                ->where('qty_received', '>', 0)
                ->exists();

            if ($hasReceivedQty) {
                throw new DomainException('PO tidak dapat dibatalkan karena sudah memiliki kuantitas barang yang diterima.');
            }

            $lockedPurchase->update([
                'status' => PurchaseStatus::CANCELLED,
            ]);

            return $lockedPurchase->load(['supplier', 'items.material.unit']);
        });
    }

    /**
     * Calculate total amount of a Purchase using exact BCMath.
     *
     * @param Purchase $purchase
     * @return string
     */
    public function calculateTotal(Purchase $purchase): string
    {
        $total = '0.00';
        foreach ($purchase->items as $item) {
            $qty = (string) ($item->qty ?? '0');
            $price = (string) ($item->unit_price ?? '0');
            $subtotal = bcmul($qty, $price, 2);
            $total = bcadd($total, $subtotal, 2);
        }

        return $total;
    }

    /**
     * Check if a Purchase can be edited.
     *
     * @param Purchase $purchase
     * @return bool
     */
    public function canEdit(Purchase $purchase): bool
    {
        return $purchase->status === PurchaseStatus::DRAFT
            && in_array($purchase->approval_status, [
                PurchaseApprovalStatus::NOT_SUBMITTED,
                PurchaseApprovalStatus::REVISION_REQUIRED,
            ], true)
            && ! $purchase->payments()->exists();
    }

    /**
     * Check if a Purchase can be submitted for Owner approval.
     *
     * @param Purchase $purchase
     * @return bool
     */
    public function canSubmitApproval(Purchase $purchase): bool
    {
        return $purchase->status === PurchaseStatus::DRAFT
            && in_array($purchase->approval_status, [
                PurchaseApprovalStatus::NOT_SUBMITTED,
                PurchaseApprovalStatus::REVISION_REQUIRED,
            ], true);
    }

    /**
     * Check if a Purchase can be confirmed to supplier (OBSOLETE: full payment auto-transitions to ORDERED).
     *
     * @param Purchase $purchase
     * @return bool
     */
    public function canConfirm(Purchase $purchase): bool
    {
        return false;
    }

    /**
     * Check if a Purchase can be cancelled.
     *
     * @param Purchase $purchase
     * @return bool
     */
    public function canCancel(Purchase $purchase): bool
    {
        if ($purchase->status === PurchaseStatus::CANCELLED || $purchase->status === PurchaseStatus::RECEIVED) {
            return false;
        }

        if ($purchase->payments()->exists()) {
            return false;
        }

        return in_array($purchase->status, [PurchaseStatus::DRAFT, PurchaseStatus::ORDERED], true);
    }

    /**
     * Get prioritized actionable Purchase Orders for the Gudang dashboard action queue.
     *
     * @param int $limit
     * @return array{items: \Illuminate\Support\Collection, total: int}
     */
    public function getGudangActionQueue(int $limit = 5): array
    {
        $candidates = Purchase::with(['supplier', 'items', 'payments'])
            ->whereNotIn('status', [PurchaseStatus::RECEIVED, PurchaseStatus::CANCELLED])
            ->get();

        $actionable = collect();

        foreach ($candidates as $purchase) {
            $mapped = $this->mapActionQueueItem($purchase);
            if ($mapped !== null) {
                $actionable->push($mapped);
            }
        }

        $sorted = $actionable->sort(function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }
            return strcmp($b['sort_key'], $a['sort_key']);
        })->values();

        return [
            'total' => $sorted->count(),
            'items' => $sorted->take($limit),
        ];
    }

    /**
     * Map a Purchase Order into its actionable queue representation.
     *
     * @param Purchase $purchase
     * @return array|null
     */
    public function mapActionQueueItem(Purchase $purchase): ?array
    {
        if ($purchase->status === PurchaseStatus::RECEIVED || $purchase->status === PurchaseStatus::CANCELLED) {
            return null;
        }

        $priority = 99;
        $statusLabel = '';
        $actionLabel = 'Lihat';
        $badgeClass = '';
        $buttonClass = 'border border-[#EBDDE4] bg-white text-[#172B4D] hover:bg-[#F7E6EE]';

        if ($purchase->status === PurchaseStatus::DRAFT) {
            if ($purchase->approval_status === PurchaseApprovalStatus::REVISION_REQUIRED) {
                $priority = 1;
                $statusLabel = 'Perlu Revisi';
                $actionLabel = 'Perbaiki';
                $badgeClass = 'bg-orange-50 text-orange-700 border-orange-200';
                $buttonClass = 'bg-orange-600 text-white hover:bg-orange-700';
            } elseif ($purchase->approval_status === PurchaseApprovalStatus::APPROVED) {
                $priority = 7;
                $statusLabel = 'Menunggu Pembayaran Admin';
                $actionLabel = 'Lihat';
                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                $buttonClass = 'border border-[#EBDDE4] bg-white text-[#172B4D] hover:bg-[#F7E6EE]';
            } elseif ($purchase->approval_status === PurchaseApprovalStatus::NOT_SUBMITTED) {
                $priority = 6;
                $statusLabel = 'Belum Diajukan';
                $actionLabel = 'Ajukan';
                $badgeClass = 'bg-slate-50 text-slate-700 border-slate-200';
                $buttonClass = 'border border-[#8E1B5C] text-[#8E1B5C] bg-white hover:bg-[#F7E6EE]';
            } elseif ($purchase->approval_status === PurchaseApprovalStatus::PENDING_APPROVAL) {
                $priority = 8;
                $statusLabel = 'Menunggu Persetujuan Admin';
                $actionLabel = 'Lihat';
                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                $buttonClass = 'border border-[#EBDDE4] bg-white text-[#172B4D] hover:bg-[#F7E6EE]';
            } elseif ($purchase->approval_status === PurchaseApprovalStatus::REJECTED) {
                $priority = 10;
                $statusLabel = 'Ditolak';
                $actionLabel = 'Lihat';
                $badgeClass = 'bg-red-50 text-red-700 border-red-200';
                $buttonClass = 'border border-[#EBDDE4] bg-white text-[#172B4D] hover:bg-[#F7E6EE]';
            } else {
                return null;
            }
        } elseif ($purchase->status === PurchaseStatus::ORDERED) {
            $priority = 4;
            $statusLabel = 'Menunggu Barang';
            $actionLabel = 'Terima Barang';
            $badgeClass = 'bg-cyan-50 text-cyan-700 border-cyan-200';
            $buttonClass = 'bg-[#8E1B5C] text-white hover:bg-[#68113F]';
        } else {
            return null;
        }

        $pDate = $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '-';
        $sortKey = ($purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : '1970-01-01')
            . '_'
            . ($purchase->created_at ? $purchase->created_at->format('Y-m-d_H:i:s') : '00:00:00')
            . '_'
            . str_pad((string) $purchase->id, 8, '0', STR_PAD_LEFT);

        return [
            'domain'        => 'purchase',
            'purchase'      => $purchase,
            'purchase_id'   => $purchase->id,
            'purchase_no'   => $purchase->purchase_no,
            'reference'     => $purchase->purchase_no,
            'supplier_name' => $purchase->supplier?->supplier_name ?? '-',
            'subtitle'      => $purchase->supplier?->supplier_name ?? '-',
            'date'          => $pDate,
            'priority'      => $priority,
            'status_label'  => $statusLabel,
            'action_label'  => $actionLabel,
            'badge_class'   => $badgeClass,
            'button_class'  => $buttonClass,
            'action_url'    => route('gudang.purchases.show', $purchase),
            'sort_key'      => $sortKey,
        ];
    }
}
