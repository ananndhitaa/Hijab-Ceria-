<?php

namespace App\Services\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\System\Outlet;
use App\Models\System\User;
use App\Services\System\TenantContext;
use App\Support\MoneyFormatter;
use DomainException;
use InvalidArgumentException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Record a payment for an invoice with concurrency-safe overpayment protection,
     * tenant-scoped Level 1 Outlet cash mutex, and PRD v1.4 Payment Auto-Amount rules.
     *
     * Lock ordering (when tenant isolation enabled):
     * LEVEL 1: Outlet row (tenant cash mutex)
     * LEVEL 2: Invoice row
     *
     * @param Invoice $invoice
     * @param array $data
     * @param User|null $actor
     * @return Payment
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    public function recordPayment(Invoice $invoice, array $data, ?User $actor = null): Payment
    {
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        return DB::transaction(function () use ($invoice, $data, $actor, $isIsolationEnabled) {
            $caller = $actor ?? auth()->user();

            if ($isIsolationEnabled) {
                // Fail-closed: Must have an authenticated caller
                if (! $caller) {
                    abort(403, 'Akses ditolak: Pengguna belum terotentikasi.');
                }

                // Fail-closed: Only ADMIN can record customer payments
                if ($caller->role !== UserRole::ADMIN && $caller->role !== UserRole::ADMIN->value) {
                    abort(403, 'Akses ditolak: Hanya Admin yang berwenang mencatat pembayaran customer.');
                }

                // Resolve legitimate tenant outlet ID via TenantContext
                try {
                    $outletId = app(TenantContext::class)->outletIdFor($caller);
                } catch (AuthorizationException $e) {
                    abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak terikat ke outlet aktif.');
                }

                // LEVEL 1: Stable Tenant Cash Mutex - Lock authorized tenant Outlet row first
                /** @var Outlet $lockedOutlet */
                $lockedOutlet = Outlet::whereKey($outletId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedOutlet->isActive()) {
                    abort(403, 'Akses ditolak: Outlet tenant tidak aktif.');
                }
            } else {
                $outletId = null;
            }

            // LEVEL 2: Lock Invoice row next
            /** @var Invoice $lockedInvoice */
            $lockedInvoice = Invoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            if ($isIsolationEnabled) {
                // Revalidate invoice ownership and related order ownership under lock
                $order = $lockedInvoice->order;
                if (! $order || $order->outlet_id === null || (int) $order->outlet_id !== $outletId) {
                    abort(404, 'Invoice atau Order tidak ditemukan untuk outlet ini.');
                }
            }

            if ($lockedInvoice->status === InvoiceStatus::CANCELLED) {
                throw new DomainException('Tidak dapat mencatat pembayaran untuk invoice yang sudah dibatalkan.');
            }

            $amount = bcadd((string) $data['amount'], '0', 2);
            if (bccomp($amount, '0.00', 2) <= 0) {
                throw new InvalidArgumentException('Jumlah pembayaran harus lebih besar dari 0.');
            }

            // Recalculate current outstanding balance under row lock
            $outstanding = $this->invoiceService->calculateOutstandingBalance($lockedInvoice);

            if (bccomp($outstanding, '0.00', 2) <= 0) {
                throw new DomainException('Tagihan invoice ini sudah lunas.');
            }

            $paymentType = $data['payment_type'] instanceof PaymentType
                ? $data['payment_type']
                : PaymentType::from($data['payment_type']);

            // Enforce payment type amount validation (PRD v1.4)
            if ($paymentType === PaymentType::DP) {
                if ($this->invoiceService->hasConfirmedDp($lockedInvoice)) {
                    throw new DomainException('Uang muka (DP) sudah tercatat. Pembayaran berikutnya dicatat sebagai Pelunasan.');
                }

                $expectedDp = $this->invoiceService->calculateDpAmount($lockedInvoice);
                if (bccomp($amount, $expectedDp, 2) !== 0) {
                    throw new DomainException('Nominal DP harus sebesar 50% dari total tagihan.');
                }
            } elseif ($paymentType === PaymentType::PELUNASAN) {
                if (bccomp($amount, $outstanding, 2) !== 0) {
                    throw new DomainException('Nominal pelunasan harus sesuai dengan sisa tagihan.');
                }
            } else {
                // LAINNYA
                if (bccomp($amount, $outstanding, 2) > 0) {
                    $maxFormatted = MoneyFormatter::format($outstanding);
                    throw new DomainException("Jumlah pembayaran melebihi sisa tagihan. Maksimal pembayaran yang dapat dicatat adalah Rp {$maxFormatted}.");
                }
            }

            $payment = Payment::create([
                'invoice_id'     => $lockedInvoice->id,
                'payment_type'   => $paymentType,
                'amount'         => $amount,
                'payment_date'   => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'status'         => PaymentStatus::CONFIRMED,
                'confirmed_by'   => $isIsolationEnabled && $caller ? $caller->id : (auth()->id() ?? null),
                'confirmed_at'   => now(),
            ]);

            $this->invoiceService->recalculateStatus($lockedInvoice);

            return $payment;
        });
    }

    /**
     * Cancel an existing payment with consistent lock ordering and tenant serialization.
     *
     * Lock ordering (when tenant isolation enabled):
     * LEVEL 1: Outlet row (tenant cash mutex)
     * LEVEL 2: Invoice row
     * LEVEL 3: Payment row
     *
     * @param Payment $payment
     * @param User|null $actor
     * @return Payment
     * @throws DomainException
     */
    public function cancelPayment(Payment $payment, ?User $actor = null): Payment
    {
        $isIsolationEnabled = (bool) config('nayara.tenant_isolation_enabled', false);

        return DB::transaction(function () use ($payment, $actor, $isIsolationEnabled) {
            $caller = $actor ?? auth()->user();

            if ($isIsolationEnabled) {
                // Fail-closed: Must have an authenticated caller
                if (! $caller) {
                    abort(403, 'Akses ditolak: Pengguna belum terotentikasi.');
                }

                // Fail-closed: Only ADMIN can cancel customer payments
                if ($caller->role !== UserRole::ADMIN && $caller->role !== UserRole::ADMIN->value) {
                    abort(403, 'Akses ditolak: Hanya Admin yang berwenang membatalkan pembayaran customer.');
                }

                // Resolve legitimate tenant outlet ID via TenantContext
                try {
                    $outletId = app(TenantContext::class)->outletIdFor($caller);
                } catch (AuthorizationException $e) {
                    abort(403, $e->getMessage() ?: 'Akses ditolak: Pengguna tidak terikat ke outlet aktif.');
                }

                // LEVEL 1: Stable Tenant Cash Mutex - Lock authorized tenant Outlet row first
                /** @var Outlet $lockedOutlet */
                $lockedOutlet = Outlet::whereKey($outletId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedOutlet->isActive()) {
                    abort(403, 'Akses ditolak: Outlet tenant tidak aktif.');
                }
            } else {
                $outletId = null;
            }

            // LEVEL 2: Lock Invoice first
            /** @var Invoice $lockedInvoice */
            $lockedInvoice = Invoice::where('id', $payment->invoice_id)->lockForUpdate()->firstOrFail();

            // LEVEL 3: Lock Payment row next
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($isIsolationEnabled) {
                // Revalidate Payment belongs to the locked Invoice
                if ((int) $lockedPayment->invoice_id !== (int) $lockedInvoice->id) {
                    abort(404, 'Pembayaran tidak sesuai dengan invoice terkait.');
                }

                // Revalidate invoice and order ownership under lock
                $order = $lockedInvoice->order;
                if (! $order || $order->outlet_id === null || (int) $order->outlet_id !== $outletId) {
                    abort(404, 'Pembayaran atau Invoice tidak ditemukan untuk outlet ini.');
                }
            }

            if ($lockedPayment->status === PaymentStatus::CANCELLED) {
                throw new DomainException('Pembayaran sudah dalam status dibatalkan.');
            }

            $lockedPayment->status = PaymentStatus::CANCELLED;
            $lockedPayment->save();

            $this->invoiceService->recalculateStatus($lockedInvoice);

            return $lockedPayment;
        });
    }
}
