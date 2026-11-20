<?php

namespace App\Services\System;

use App\Enums\UserRole;
use App\Models\Finance\BusinessExpense;
use App\Models\Finance\BusinessIncome;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\StockMovement;
use App\Models\Production\ProductionBatch;
use App\Models\Production\ProductionResult;
use App\Models\Production\QcResult;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\Payment;
use App\Models\System\OrderStatusHistory;
use App\Models\System\Outlet;
use App\Models\System\OutletActivityLog;
use App\Models\System\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TenantActivitySyncService
{
    /**
     * Synchronize all legitimate historical transactions into outlet_activity_logs.
     *
     * @param int|null $outletId If null, synchronizes all active outlets.
     * @return int Total number of new log records inserted.
     */
    public static function syncOutletLogs(?int $outletId = null): int
    {
        $outlets = $outletId !== null
            ? Outlet::where('id', $outletId)->get()
            : Outlet::all();

        $totalInserted = 0;

        foreach ($outlets as $outlet) {
            $totalInserted += self::syncSingleOutlet((int) $outlet->id);
        }

        // Clean up any historical .0000 pcs in existing log descriptions
        DB::table('outlet_activity_logs')
            ->where('description', 'like', '%.0000 pcs%')
            ->update([
                'description' => DB::raw("REPLACE(description, '.0000 pcs', ' pcs')"),
            ]);

        return $totalInserted;
    }

    /**
     * Synchronize records for one outlet.
     */
    protected static function syncSingleOutlet(int $outletId): int
    {
        $users = User::where('outlet_id', $outletId)->get();
        $adminUser = $users->firstWhere('role', UserRole::ADMIN) ?? $users->first();
        $gudangUser = $users->firstWhere('role', UserRole::GUDANG) ?? $adminUser;
        $produksiUser = $users->firstWhere('role', UserRole::PRODUKSI) ?? $adminUser;
        $ownerUser = $users->firstWhere('role', UserRole::OWNER) ?? $adminUser;

        $newLogs = 0;

        // 1. Sales Orders
        $orders = Order::where('outlet_id', $outletId)->with('customer.user')->get();
        foreach ($orders as $order) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->where('action_type', 'CREATE_ORDER')
                ->exists();

            if (! $exists) {
                $actor = $order->customer?->user ?? $adminUser;
                $createdAt = $order->created_at ?? Carbon::parse($order->order_date ?? now());

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $actor?->id;
                $log->user_name = $actor?->name ?? 'Admin Penjualan';
                $log->user_role = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'ADMIN');
                $log->module = 'SALES_ORDER';
                $log->action_type = 'CREATE_ORDER';
                $log->reference_no = $order->order_no;
                $log->subject_type = Order::class;
                $log->subject_id = $order->id;
                $log->description = "Sales Order {$order->order_no} dibuat untuk pelanggan " . ($order->customer?->customer_name ?? 'Pelanggan') . " dengan nilai Rp " . number_format((float) $order->total_amount, 0, ',', '.') . ".";
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            }
        }

        // 2. Order Status Histories
        $orderHistories = OrderStatusHistory::whereHas('order', fn ($q) => $q->where('outlet_id', $outletId))
            ->with(['order', 'changedByUser'])
            ->get();

        foreach ($orderHistories as $history) {
            $statusLabel = is_object($history->status) && method_exists($history->status, 'label')
                ? $history->status->label()
                : ($history->status->value ?? (string) $history->status);
            $notesText = $history->notes ? " ({$history->notes})" : '';
            $desc = "Status pesanan {$history->order?->order_no} diperbarui menjadi {$statusLabel}{$notesText}.";
            $actor = $history->changedByUser ?? $adminUser;
            $createdAt = $history->changed_at ?? $history->created_at ?? now();

            $existingLog = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', OrderStatusHistory::class)
                ->where('subject_id', $history->id)
                ->first();

            if (! $existingLog) {
                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $actor?->id;
                $log->user_name = $actor?->name ?? 'Sistem';
                $log->user_role = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'ADMIN');
                $log->module = 'SALES_ORDER';
                $log->action_type = 'UPDATE_STATUS';
                $log->reference_no = $history->order?->order_no;
                $log->subject_type = OrderStatusHistory::class;
                $log->subject_id = $history->id;
                $log->description = $desc;
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            } else {
                if (str_contains($existingLog->description, 'dari Awal ke')) {
                    $existingLog->update([
                        'description' => $desc,
                        'created_at'  => $createdAt,
                        'updated_at'  => $createdAt,
                    ]);
                }
            }
        }

        // 3. Invoices
        $invoices = Invoice::whereHas('order', fn ($q) => $q->where('outlet_id', $outletId))
            ->with('order')
            ->get();

        foreach ($invoices as $invoice) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', Invoice::class)
                ->where('subject_id', $invoice->id)
                ->where('action_type', 'CREATE_INVOICE')
                ->exists();

            if (! $exists) {
                $createdAt = $invoice->created_at ?? Carbon::parse($invoice->invoice_date ?? now());

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $adminUser?->id;
                $log->user_name = $adminUser?->name ?? 'Admin Keuangan';
                $log->user_role = $adminUser?->role instanceof \BackedEnum ? $adminUser->role->value : ($adminUser?->role ?? 'ADMIN');
                $log->module = 'KEUANGAN';
                $log->action_type = 'CREATE_INVOICE';
                $log->reference_no = $invoice->invoice_no;
                $log->subject_type = Invoice::class;
                $log->subject_id = $invoice->id;
                $log->description = "Invoice {$invoice->invoice_no} diterbitkan untuk pesanan {$invoice->order?->order_no} sebesar Rp " . number_format((float) $invoice->total_amount, 0, ',', '.') . ".";
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            }
        }

        // 4. Payments
        $payments = Payment::whereHas('invoice.order', fn ($q) => $q->where('outlet_id', $outletId))
            ->with(['invoice.order', 'confirmedByUser'])
            ->get();

        foreach ($payments as $payment) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', Payment::class)
                ->where('subject_id', $payment->id)
                ->exists();

            if (! $exists) {
                $actor = $payment->confirmedByUser ?? $adminUser;
                $paymentType = is_object($payment->payment_type) && method_exists($payment->payment_type, 'label')
                    ? $payment->payment_type->label()
                    : (string) ($payment->payment_type ?? 'Pembayaran');
                $createdAt = $payment->created_at ?? Carbon::parse($payment->payment_date ?? now());

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $actor?->id;
                $log->user_name = $actor?->name ?? 'Kasir / Admin';
                $log->user_role = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'ADMIN');
                $log->module = 'KEUANGAN';
                $log->action_type = 'RECORD_PAYMENT';
                $log->reference_no = $payment->payment_no ?: $payment->invoice?->invoice_no;
                $log->subject_type = Payment::class;
                $log->subject_id = $payment->id;
                $log->description = "Pembayaran ({$paymentType}) diterima sebesar Rp " . number_format((float) $payment->amount, 0, ',', '.') . " untuk {$payment->invoice?->invoice_no}.";
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            }
        }

        // 5. Purchases (Pengadaan Bahan)
        $purchases = Purchase::where('outlet_id', $outletId)
            ->with(['supplier', 'submittedByUser', 'items'])
            ->get();

        foreach ($purchases as $purchase) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', Purchase::class)
                ->where('subject_id', $purchase->id)
                ->exists();

            if (! $exists) {
                $actor = $purchase->submittedByUser ?? $gudangUser ?? $adminUser;
                $createdAt = $purchase->created_at ?? Carbon::parse($purchase->purchase_date ?? now());
                $statusVal = is_object($purchase->status) && method_exists($purchase->status, 'label')
                    ? $purchase->status->label()
                    : ($purchase->status->value ?? (string) $purchase->status);

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $actor?->id;
                $log->user_name = $actor?->name ?? 'Staff Pengadaan';
                $log->user_role = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'GUDANG');
                $log->module = 'PURCHASING';
                $log->action_type = 'CREATE_PO';
                $log->reference_no = $purchase->purchase_no;
                $log->subject_type = Purchase::class;
                $log->subject_id = $purchase->id;
                $log->description = "Purchase Order {$purchase->purchase_no} kepada supplier " . ($purchase->supplier?->supplier_name ?? 'Pemasok') . " senilai Rp " . number_format((float) $purchase->total_amount, 0, ',', '.') . " (Status: {$statusVal}).";
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            }
        }

        // 6. Production Batches
        $batches = ProductionBatch::forOutlet($outletId)
            ->with(['orderItem.variant', 'productionEmployee'])
            ->get();

        foreach ($batches as $batch) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', ProductionBatch::class)
                ->where('subject_id', $batch->id)
                ->where('action_type', 'CREATE_BATCH')
                ->exists();

            if (! $exists) {
                $actor = $adminUser;
                $targetFloat = (float) $batch->target_qty;
                $targetStr = $targetFloat == (int) $targetFloat
                    ? number_format($targetFloat, 0, ',', '.')
                    : number_format($targetFloat, 2, ',', '.');

                $picText = $batch->productionEmployee
                    ? " dan PIC ditugaskan kepada {$batch->productionEmployee->name}"
                    : " tanpa PIC";

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $actor?->id;
                $log->user_name = $actor?->name ?? 'Admin Produksi';
                $log->user_role = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'ADMIN');
                $log->module = 'BATCH_PRODUKSI';
                $log->action_type = 'CREATE_BATCH';
                $log->reference_no = $batch->batch_no;
                $log->subject_type = ProductionBatch::class;
                $log->subject_id = $batch->id;
                $log->description = "Batch produksi {$batch->batch_no} dibuat dengan target {$targetStr} pcs{$picText}.";
                $log->created_at = $batch->created_at ?? now();
                $log->updated_at = $batch->created_at ?? now();
                $log->save();

                $newLogs++;
            }
        }

        // 7. Production Results
        $prodResults = ProductionResult::whereHas('batch', fn ($q) => $q->forOutlet($outletId))
            ->with(['batch', 'recordedByUser'])
            ->get();

        foreach ($prodResults as $pr) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', ProductionResult::class)
                ->where('subject_id', $pr->id)
                ->exists();

            if (! $exists) {
                $actor = $pr->recordedByUser ?? $produksiUser;
                $actualFloat = (float) $pr->actual_qty;
                $actualStr = $actualFloat == (int) $actualFloat
                    ? number_format($actualFloat, 0, ',', '.')
                    : number_format($actualFloat, 2, ',', '.');

                $createdAt = $pr->created_at ?? Carbon::parse($pr->result_date ?? now());

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $actor?->id;
                $log->user_name = $actor?->name ?? 'Operator Produksi';
                $log->user_role = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'PRODUKSI');
                $log->module = 'PRODUKSI';
                $log->action_type = 'RECORD_PRODUCTION';
                $log->reference_no = $pr->batch?->batch_no;
                $log->subject_type = ProductionResult::class;
                $log->subject_id = $pr->id;
                $log->description = "Hasil pengerjaan Batch {$pr->batch?->batch_no} dicatat sebanyak {$actualStr} pcs oleh {$log->user_name}.";
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            }
        }

        // 8. QC Results
        $qcResults = QcResult::whereHas('productionResult.batch', fn ($q) => $q->forOutlet($outletId))
            ->with(['productionResult.batch', 'qcByUser'])
            ->get();

        foreach ($qcResults as $qc) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', QcResult::class)
                ->where('subject_id', $qc->id)
                ->exists();

            if (! $exists) {
                $actor = $qc->qcByUser ?? $produksiUser;
                $goodFloat = (float) $qc->good_qty;
                $goodStr = $goodFloat == (int) $goodFloat
                    ? number_format($goodFloat, 0, ',', '.')
                    : number_format($goodFloat, 2, ',', '.');
                $rejectFloat = (float) $qc->reject_qty;
                $rejectStr = $rejectFloat == (int) $rejectFloat
                    ? number_format($rejectFloat, 0, ',', '.')
                    : number_format($rejectFloat, 2, ',', '.');

                $createdAt = $qc->qc_at ?? $qc->created_at ?? now();

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $actor?->id;
                $log->user_name = $actor?->name ?? 'Inspector QC';
                $log->user_role = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'PRODUKSI');
                $log->module = 'QC';
                $log->action_type = 'RECORD_QC';
                $log->reference_no = $qc->productionResult?->batch?->batch_no;
                $log->subject_type = QcResult::class;
                $log->subject_id = $qc->id;
                $log->description = "Inspeksi QC Batch {$qc->productionResult?->batch?->batch_no} selesai (Lolos: {$goodStr} pcs, Reject: {$rejectStr} pcs).";
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            }
        }

        // 9. Stock Movements (Gudang)
        $stockMovements = StockMovement::whereHas('material', fn ($q) => $q->where('materials.outlet_id', $outletId))
            ->with(['material.unit', 'createdByUser'])
            ->latest('id')
            ->limit(100) // keep reasonable volume
            ->get();

        foreach ($stockMovements as $sm) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', StockMovement::class)
                ->where('subject_id', $sm->id)
                ->exists();

            if (! $exists) {
                $actor = $sm->createdByUser ?? $gudangUser;
                $mType = $sm->movement_type instanceof \BackedEnum ? $sm->movement_type->value : (string) $sm->movement_type;
                $qtyFloat = (float) $sm->qty;
                $qtyStr = ($qtyFloat >= 0 ? '+' : '') . ($qtyFloat == (int) $qtyFloat
                    ? number_format($qtyFloat, 0, ',', '.')
                    : rtrim(rtrim(number_format($qtyFloat, 4, ',', '.'), '0'), ','));

                $symbol = $sm->material?->unit?->symbol ?? 'unit';
                $createdAt = $sm->created_at ?? Carbon::parse($sm->movement_date ?? now());

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $actor?->id;
                $log->user_name = $actor?->name ?? 'Petugas Gudang';
                $log->user_role = $actor?->role instanceof \BackedEnum ? $actor->role->value : ($actor?->role ?? 'GUDANG');
                $log->module = 'GUDANG';
                $log->action_type = 'MUTASI_' . strtoupper($mType);
                $log->reference_no = $sm->material?->material_name;
                $log->subject_type = StockMovement::class;
                $log->subject_id = $sm->id;
                $log->description = "Mutasi stok {$mType} material {$sm->material?->material_name} sejumlah {$qtyStr} {$symbol}.";
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            }
        }

        // 10. Business Expenses (if any)
        $expenses = BusinessExpense::where('outlet_id', $outletId)->get();
        foreach ($expenses as $expense) {
            $exists = OutletActivityLog::where('outlet_id', $outletId)
                ->where('subject_type', BusinessExpense::class)
                ->where('subject_id', $expense->id)
                ->exists();

            if (! $exists) {
                $createdAt = $expense->created_at ?? Carbon::parse($expense->expense_date ?? now());

                $log = new OutletActivityLog();
                $log->outlet_id = $outletId;
                $log->user_id = $adminUser?->id;
                $log->user_name = $adminUser?->name ?? 'Admin Keuangan';
                $log->user_role = $adminUser?->role instanceof \BackedEnum ? $adminUser->role->value : ($adminUser?->role ?? 'ADMIN');
                $log->module = 'KEUANGAN';
                $log->action_type = 'RECORD_EXPENSE';
                $log->reference_no = $expense->expense_number ?: 'EXP-' . $expense->id;
                $log->subject_type = BusinessExpense::class;
                $log->subject_id = $expense->id;
                $log->description = "Pencatatan pengeluaran operasional '{$expense->description}' sebesar Rp " . number_format((float) $expense->amount, 0, ',', '.') . ".";
                $log->created_at = $createdAt;
                $log->updated_at = $createdAt;
                $log->save();

                $newLogs++;
            }
        }

        return $newLogs;
    }
}
