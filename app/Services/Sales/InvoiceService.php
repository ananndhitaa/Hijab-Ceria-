<?php

namespace App\Services\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Services\System\DocumentNumberGenerator;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private DocumentNumberGenerator $numberGenerator
    ) {}

    /**
     * Allowed order statuses eligible for invoice creation.
     *
     * @return array<OrderStatus>
     */
    public static function eligibleStatuses(): array
    {
        return [
            OrderStatus::DEAL,
            OrderStatus::BAHAN,
            OrderStatus::PRINTING,
            OrderStatus::PRODUKSI,
            OrderStatus::QC,
            OrderStatus::PACKING,
            OrderStatus::DIKIRIM,
            OrderStatus::SELESAI,
        ];
    }

    /**
     * Check if an order is eligible for invoice creation.
     *
     * @param Order $order
     * @return bool
     */
    public function canCreateForOrder(Order $order): bool
    {
        return in_array($order->status, self::eligibleStatuses(), true)
            && ! $order->invoice()->exists();
    }

    /**
     * Create an Invoice for an Order.
     * Order must have status DEAL or later operational statuses.
     * Uses DB transaction and row-level locking on the Order.
     *
     * @param Order $order
     * @param \DateTimeInterface|null $invoiceDate
     * @return Invoice
     * @throws DomainException
     */
    public function createForOrder(Order $order, ?\DateTimeInterface $invoiceDate = null): Invoice
    {
        return DB::transaction(function () use ($order, $invoiceDate) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedOrder->status, self::eligibleStatuses(), true)) {
                throw new DomainException('Invoice hanya dapat dibuat untuk pesanan yang sudah mencapai status DEAL atau setelahnya.');
            }

            if ($lockedOrder->invoice()->exists()) {
                throw new DomainException('Invoice untuk pesanan ini sudah pernah dibuat.');
            }

            $items = $lockedOrder->items;
            if ($items->isEmpty()) {
                throw new DomainException('Pesanan tidak memiliki item produk.');
            }

            // Decimal-safe snapshot sum using BCMath
            $totalAmount = '0.00';
            foreach ($items as $item) {
                $subtotal = bcmul((string) $item->qty_ordered, (string) $item->unit_selling_price, 2);
                $totalAmount = bcadd($totalAmount, $subtotal, 2);
            }

            if (bccomp($totalAmount, '0.00', 2) <= 0) {
                throw new DomainException('Total nominal invoice harus lebih besar dari 0.');
            }

            $invoiceNo = $this->numberGenerator->generate('INV');
            $date = $invoiceDate
                ? Carbon::instance($invoiceDate)->setTimezone('Asia/Jakarta')->toDateString()
                : Carbon::now('Asia/Jakarta')->toDateString();

            return Invoice::create([
                'order_id'     => $lockedOrder->id,
                'invoice_no'   => $invoiceNo,
                'invoice_date' => $date,
                'total_amount' => $totalAmount,
                'status'       => InvoiceStatus::UNPAID,
            ]);
        });
    }

    /**
     * Calculate total confirmed paid amount for an invoice using decimal-safe string representation.
     *
     * @param Invoice $invoice
     * @return string
     */
    public function calculatePaidAmount(Invoice $invoice): string
    {
        if ($invoice->relationLoaded('payments')) {
            $totalPaid = '0.00';
            foreach ($invoice->payments as $payment) {
                $status = $payment->status instanceof PaymentStatus ? $payment->status->value : (string) $payment->status;
                if ($status === PaymentStatus::CONFIRMED->value) {
                    $totalPaid = bcadd($totalPaid, (string) $payment->amount, 2);
                }
            }

            return $totalPaid;
        }

        $payments = $invoice->payments()
            ->where('status', PaymentStatus::CONFIRMED->value)
            ->pluck('amount');

        $totalPaid = '0.00';
        foreach ($payments as $amount) {
            $totalPaid = bcadd($totalPaid, (string) $amount, 2);
        }

        return $totalPaid;
    }

    /**
     * Calculate outstanding balance for an invoice using decimal-safe string representation.
     *
     * @param Invoice $invoice
     * @return string
     */
    public function calculateOutstandingBalance(Invoice $invoice): string
    {
        $paid = $this->calculatePaidAmount($invoice);
        $total = (string) $invoice->total_amount;

        $balance = bcsub($total, $paid, 2);

        return bccomp($balance, '0.00', 2) > 0 ? $balance : '0.00';
    }

    /**
     * Calculate default/expected DP amount (50% of total_amount capped by outstanding balance) using decimal-safe BCMath.
     *
     * @param Invoice $invoice
     * @return string
     */
    public function calculateDpAmount(Invoice $invoice): string
    {
        $total = (string) $invoice->total_amount;
        $half = bcdiv($total, '2', 2);
        $outstanding = $this->calculateOutstandingBalance($invoice);

        if (bccomp($half, $outstanding, 2) > 0) {
            return $outstanding;
        }

        return $half;
    }

    /**
     * Calculate suggested payment amount based on payment type using decimal-safe BCMath.
     *
     * @param Invoice $invoice
     * @param PaymentType|string $type
     * @return string
     */
    public function calculateSuggestedAmount(Invoice $invoice, PaymentType|string $type): string
    {
        $paymentType = is_string($type) ? PaymentType::tryFrom($type) : $type;
        $outstanding = $this->calculateOutstandingBalance($invoice);

        return match ($paymentType) {
            PaymentType::DP        => $this->calculateDpAmount($invoice),
            PaymentType::PELUNASAN => $outstanding,
            default                => '',
        };
    }

    /**
     * Recalculate invoice status based on confirmed payments.
     * Preserves CANCELLED status if already cancelled.
     *
     * @param Invoice $invoice
     * @return Invoice
     */
    public function recalculateStatus(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::CANCELLED) {
            return $invoice;
        }

        $paid = $this->calculatePaidAmount($invoice);
        $total = (string) $invoice->total_amount;

        if (bccomp($paid, '0.00', 2) === 0) {
            $invoice->status = InvoiceStatus::UNPAID;
        } elseif (bccomp($paid, $total, 2) >= 0) {
            $invoice->status = InvoiceStatus::PAID;
        } else {
            $invoice->status = InvoiceStatus::PARTIAL;
        }

        $invoice->save();

        return $invoice;
    }

    /**
     * Determine whether an Order is fully paid with confirmed payments using decimal-safe BCMath.
     *
     * Invariants:
     * - Order must have an issued Invoice.
     * - Invoice must not be CANCELLED.
     * - Only CONFIRMED payments are counted towards the paid total.
     * - Total confirmed payments must equal or exceed total_amount (outstanding balance == '0.00').
     *
     * @param Order $order
     * @return bool
     */
    public function isOrderFullyPaid(Order $order): bool
    {
        /** @var Invoice|null $invoice */
        $invoice = $order->relationLoaded('invoice')
            ? $order->invoice
            : $order->invoice()->first();

        if (! $invoice) {
            return false;
        }

        if ($invoice->status === InvoiceStatus::CANCELLED) {
            return false;
        }

        if ($invoice->status === InvoiceStatus::UNPAID || $invoice->status === InvoiceStatus::PARTIAL) {
            return false;
        }

        $outstanding = $this->calculateOutstandingBalance($invoice);

        return bccomp($outstanding, '0.00', 2) === 0;
    }

    /**
     * Determine whether an Order has a valid confirmed initial payment (Down Payment) using decimal-safe BCMath.
     *
     * Invariants:
     * - Order must have an issued Invoice.
     * - Invoice must not be CANCELLED.
     * - Only CONFIRMED payments are counted towards the paid total.
     * - Total confirmed payments must be greater than zero (> 0.00).
     *
     * @param Order $order
     * @return bool
     */
    public function hasConfirmedDownPayment(Order $order): bool
    {
        /** @var Invoice|null $invoice */
        $invoice = $order->relationLoaded('invoice')
            ? $order->invoice
            : $order->invoice()->first();

        if (! $invoice) {
            return false;
        }

        if ($invoice->status === InvoiceStatus::CANCELLED) {
            return false;
        }

        $paid = $this->calculatePaidAmount($invoice);

        return bccomp($paid, '0.00', 2) > 0;
    }

    /**
     * Determine whether an Invoice already has a confirmed Down Payment (DP).
     * Only payments with status CONFIRMED and payment_type DP count.
     * PENDING, CANCELLED, or other non-CONFIRMED payments do NOT count.
     *
     * @param Invoice $invoice
     * @return bool
     */
    public function hasConfirmedDp(Invoice $invoice): bool
    {
        if ($invoice->relationLoaded('payments')) {
            return $invoice->payments->contains(function ($payment) {
                $status = $payment->status instanceof PaymentStatus
                    ? $payment->status
                    : PaymentStatus::tryFrom((string) $payment->status);

                $type = $payment->payment_type instanceof PaymentType
                    ? $payment->payment_type
                    : PaymentType::tryFrom((string) $payment->payment_type);

                return $status === PaymentStatus::CONFIRMED && $type === PaymentType::DP;
            });
        }

        return $invoice->payments()
            ->where('payment_type', PaymentType::DP->value)
            ->where('status', PaymentStatus::CONFIRMED->value)
            ->exists();
    }

    /**
     * Get detailed status and user-facing message regarding Down Payment for an Order.
     *
     * @param Order $order
     * @return array{
     *     has_confirmed_dp: bool,
     *     status_code: 'NO_INVOICE'|'NO_PAYMENT'|'PENDING'|'CONFIRMED'|'CANCELLED_ONLY'|'INVOICE_CANCELLED',
     *     message: string,
     *     total_confirmed_paid: string
     * }
     */
    public function getDownPaymentStatus(Order $order): array
    {
        /** @var Invoice|null $invoice */
        $invoice = $order->relationLoaded('invoice')
            ? $order->invoice
            : $order->invoice()->with(['payments'])->first();

        if (! $invoice) {
            return [
                'has_confirmed_dp'     => false,
                'status_code'          => 'NO_INVOICE',
                'message'              => 'Terbitkan invoice dan konfirmasi pembayaran DP sebelum membuat batch.',
                'total_confirmed_paid' => '0.00',
            ];
        }

        if ($invoice->status === InvoiceStatus::CANCELLED) {
            return [
                'has_confirmed_dp'     => false,
                'status_code'          => 'INVOICE_CANCELLED',
                'message'              => 'Invoice dibatalkan. Terbitkan invoice baru dan konfirmasi pembayaran DP.',
                'total_confirmed_paid' => '0.00',
            ];
        }

        $paid = $this->calculatePaidAmount($invoice);

        if (bccomp($paid, '0.00', 2) > 0) {
            return [
                'has_confirmed_dp'     => true,
                'status_code'          => 'CONFIRMED',
                'message'              => 'DP telah diterima.',
                'total_confirmed_paid' => $paid,
            ];
        }

        // Invoice exists but confirmed paid is 0.00
        $payments = $invoice->relationLoaded('payments') ? $invoice->payments : $invoice->payments()->get();

        if ($payments->isEmpty()) {
            return [
                'has_confirmed_dp'     => false,
                'status_code'          => 'NO_PAYMENT',
                'message'              => 'Menunggu pembayaran DP',
                'total_confirmed_paid' => '0.00',
            ];
        }

        $hasPending = $payments->contains(fn ($p) => $p->status === PaymentStatus::PENDING || $p->status === PaymentStatus::PENDING->value);
        if ($hasPending) {
            return [
                'has_confirmed_dp'     => false,
                'status_code'          => 'PENDING',
                'message'              => 'Pembayaran DP menunggu konfirmasi',
                'total_confirmed_paid' => '0.00',
            ];
        }

        return [
            'has_confirmed_dp'     => false,
            'status_code'          => 'CANCELLED_ONLY',
            'message'              => 'Menunggu pembayaran DP',
            'total_confirmed_paid' => '0.00',
        ];
    }
}
