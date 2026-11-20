<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Finance\OpeningCashBalance;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\PurchasePayment;
use App\Services\Finance\FinancialRecordService;
use App\Services\System\TenantContext;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchasePaymentController extends Controller
{
    public function __construct(
        protected FinancialRecordService $financialRecordService
    ) {}

    public function index(): View
    {
        $outletId = $this->getActiveTenantOutletId();
        $openingRecord = $outletId !== null
            ? OpeningCashBalance::forOutlet($outletId)->latest('opening_date')->latest('id')->first()
            : OpeningCashBalance::latest('opening_date')->latest('id')->first();
        $hasOpening = $openingRecord !== null;
        $kasTersedia = $hasOpening ? $this->financialRecordService->calculateCurrentCash($openingRecord, $outletId) : '0.00';
        $procurementSummary = $this->financialRecordService->calculateProcurementSummary($kasTersedia, $outletId);

        return view('admin.purchases.payments.index', [
            'kasTersedia'        => $kasTersedia,
            'hasOpening'         => $hasOpening,
            'openingRecord'      => $openingRecord,
            'procurementSummary' => $procurementSummary,
            'unpaidPurchases'    => $procurementSummary['unpaid_purchases'],
        ]);
    }

    public function create(Purchase $purchase): View|RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        if ($purchase->status === PurchaseStatus::CANCELLED) {
            return redirect()->route('admin.financial-records.index')
                ->with('error', 'Tidak dapat mencatat pembayaran untuk Purchase Order yang telah Dibatalkan.');
        }

        if ($purchase->approval_status !== PurchaseApprovalStatus::APPROVED) {
            return redirect()->route('admin.financial-records.index')
                ->with('error', 'Pembayaran supplier hanya dapat dicatat untuk Purchase Order yang telah disetujui (APPROVED) oleh Admin.');
        }

        $purchase->load(['supplier', 'items', 'payments.createdByUser', 'approvedByUser']);
        $outstanding = $purchase->outstanding_amount;

        if (bccomp($outstanding, '0.00', 2) <= 0) {
            return redirect()->route('admin.financial-records.index')
                ->with('info', "Purchase Order #{$purchase->purchase_no} sudah lunas.");
        }

        $openingRecord = $outletId !== null
            ? OpeningCashBalance::forOutlet($outletId)->latest('opening_date')->latest('id')->first()
            : OpeningCashBalance::latest('opening_date')->latest('id')->first();
        if (! $openingRecord) {
            return redirect()->route('admin.financial-records.index')
                ->with('error', 'Saldo awal kas belum ditentukan. Atur saldo awal kas sebelum mencatat pembayaran supplier.');
        }

        $openingDate = $openingRecord->opening_date->format('Y-m-d');
        $kasTersedia = $this->financialRecordService->calculateCurrentCash($openingRecord, $outletId);
        $isSufficient = bccomp($kasTersedia, $outstanding, 2) >= 0;
        $maxPayment = $isSufficient ? $outstanding : (bccomp($kasTersedia, '0.00', 2) > 0 ? $kasTersedia : '0.00');

        return view('admin.purchases.payments.create', [
            'purchase'      => $purchase,
            'outstanding'   => $outstanding,
            'kasTersedia'   => $kasTersedia,
            'isSufficient'  => $isSufficient,
            'openingRecord' => $openingRecord,
            'openingDate'   => $openingDate,
            'maxPayment'    => $maxPayment,
        ]);
    }

    public function store(Request $request, Purchase $purchase): RedirectResponse
    {
        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            abort_unless($purchase->hasConsistentTenantRelations($outletId), 404);
        }

        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'gt:0'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference_no'   => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'transfer_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'amount.required'         => 'Nominal pembayaran wajib diisi.',
            'amount.numeric'          => 'Nominal pembayaran harus berupa angka.',
            'amount.gt'               => 'Nominal pembayaran harus lebih besar dari 0.',
            'payment_date.required'   => 'Tanggal pembayaran wajib diisi.',
            'payment_date.date'       => 'Format tanggal pembayaran tidak valid.',
            'transfer_proof.required' => 'Bukti transfer wajib diunggah.',
            'transfer_proof.file'     => 'Bukti transfer harus berupa berkas yang valid.',
            'transfer_proof.mimes'    => 'Format bukti transfer harus berupa JPG, PNG, atau PDF.',
            'transfer_proof.max'      => 'Ukuran bukti transfer maksimal 5 MB.',
        ]);

        $storedProofPath = null;
        if ($request->hasFile('transfer_proof')) {
            $storedProofPath = $request->file('transfer_proof')->store('transfer-proofs', 'local');
        }

        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        try {
            return DB::transaction(function () use ($request, $purchase, $validated, $storedProofPath, $outletId, $isIsolationEnabled) {
                if ($isIsolationEnabled && $outletId !== null) {
                    // Level 1: Stable Tenant Cash Mutex - Lock authorized tenant Outlet row first
                    $lockedOutlet = \App\Models\System\Outlet::whereKey($outletId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (! $lockedOutlet->isActive()) {
                        abort(403, 'Akses ditolak: Outlet tenant tidak aktif.');
                    }
                }

                // Level 2: Lock PO row to prevent concurrent modifications
                $po = Purchase::where('id', $purchase->id)->lockForUpdate()->firstOrFail();

                if ($outletId !== null) {
                    abort_unless($po->hasConsistentTenantRelations($outletId), 404);
                }

                if ($po->status === PurchaseStatus::CANCELLED) {
                    throw ValidationException::withMessages([
                        'amount' => 'Tidak dapat membayar Purchase Order yang telah Dibatalkan.',
                    ]);
                }

                if ($po->approval_status !== PurchaseApprovalStatus::APPROVED) {
                    throw ValidationException::withMessages([
                        'amount' => 'Pembayaran supplier hanya dapat dicatat untuk Purchase Order yang telah disetujui (APPROVED) oleh Admin.',
                    ]);
                }

                // Recalculate outstanding under DB lock
                $po->load(['items', 'payments']);
                $outstanding = $po->outstanding_amount;
                $inputAmount = (string) $validated['amount'];

                if (bccomp($inputAmount, $outstanding, 2) > 0) {
                    $formattedMax = number_format((float) $outstanding, 0, ',', '.');
                    $formattedInput = number_format((float) $inputAmount, 0, ',', '.');
                    throw ValidationException::withMessages([
                        'amount' => "Nominal pembayaran (Rp {$formattedInput}) melebihi sisa tagihan PO (Rp {$formattedMax}).",
                    ]);
                }

                // Lock latest opening cash record to serialize operational cash disbursements per tenant
                $openingRecord = $outletId !== null
                    ? OpeningCashBalance::forOutlet($outletId)->lockForUpdate()->latest('opening_date')->latest('id')->first()
                    : OpeningCashBalance::lockForUpdate()->latest('opening_date')->latest('id')->first();

                if (! $openingRecord) {
                    throw ValidationException::withMessages([
                        'amount' => 'Saldo awal kas belum ditentukan. Atur saldo awal kas sebelum mencatat pembayaran supplier.',
                    ]);
                }

                $openingDate = $openingRecord->opening_date->format('Y-m-d');
                $paymentDate = Carbon::parse($validated['payment_date'])->format('Y-m-d');
                $isHistorical = strcmp($paymentDate, $openingDate) < 0;

                // For non-historical payments (>= opening_date), enforce HARD CASH GUARD
                if (! $isHistorical) {
                    $currentCash = $this->financialRecordService->calculateCurrentCash($openingRecord, $outletId);
                    if ($currentCash === null || bccomp($inputAmount, (string) $currentCash, 2) > 0) {
                        $formattedCash = number_format((float) ($currentCash ?? '0.00'), 0, ',', '.');
                        throw ValidationException::withMessages([
                            'amount' => "Kas tidak mencukupi untuk pembayaran supplier. Kas tersedia: Rp {$formattedCash}.",
                        ]);
                    }
                }

                // Record PurchasePayment with transfer proof path
                PurchasePayment::create([
                    'purchase_id'         => $po->id,
                    'amount'              => $inputAmount,
                    'payment_date'        => $validated['payment_date'],
                    'payment_method'      => $validated['payment_method'] ?? 'TRANSFER',
                    'reference_no'        => $validated['reference_no'] ?? null,
                    'notes'               => $validated['notes'] ?? null,
                    'transfer_proof_path' => $storedProofPath,
                    'created_by'          => $request->user()->id,
                ]);

                // Recalculate cumulative payments & trigger ATOMIC AUTO ORDERED if fully paid
                $po->load('payments');
                if ($po->isFullyPaid()) {
                    $po->status = PurchaseStatus::ORDERED;
                    $po->save();
                }

                $formattedInput = number_format((float) $inputAmount, 0, ',', '.');
                $msg = "Pembayaran Rp{$formattedInput} untuk {$po->purchase_no} berhasil.";
                if ($po->isFullyPaid()) {
                    $msg .= ' PO kini Dipesan.';
                }
                if ($isHistorical) {
                    $msg .= ' (Transaksi historis).';
                }

                return redirect()->route('admin.financial-records.index')->with('success', $msg);
            });
        } catch (\Throwable $e) {
            if ($storedProofPath && Storage::disk('local')->exists($storedProofPath)) {
                Storage::disk('local')->delete($storedProofPath);
            }
            throw $e;
        }
    }

    /**
     * Display / stream transfer proof securely.
     */
    public function showProof(PurchasePayment $payment): \Symfony\Component\HttpFoundation\StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, [UserRole::ADMIN, UserRole::OWNER], true)) {
            abort(403, 'Akses ditolak.');
        }

        $outletId = $this->getActiveTenantOutletId();
        if ($outletId !== null) {
            $payment->loadMissing('purchase');
            if (! $payment->purchase || $payment->purchase->outlet_id === null || (int) $payment->purchase->outlet_id !== $outletId) {
                abort(404, 'Bukti transfer tidak ditemukan.');
            }
        }

        if (! $payment->transfer_proof_path || ! Storage::disk('local')->exists($payment->transfer_proof_path)) {
            abort(404, 'Bukti transfer tidak ditemukan.');
        }

        return Storage::disk('local')->response($payment->transfer_proof_path);
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
