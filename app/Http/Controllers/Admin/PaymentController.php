<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Services\Sales\PaymentService;
use DomainException;
use InvalidArgumentException;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function store(
        StorePaymentRequest $request,
        Invoice $invoice,
        PaymentService $paymentService
    ): RedirectResponse {
        try {
            $paymentService->recordPayment($invoice, $request->validated());

            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', 'Pembayaran berhasil dicatat dan diverifikasi.');
        } catch (DomainException | InvalidArgumentException $e) {
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(
        Payment $payment,
        PaymentService $paymentService
    ): RedirectResponse {
        try {
            $paymentService->cancelPayment($payment);

            return redirect()->route('admin.invoices.show', $payment->invoice_id)
                ->with('success', 'Pembayaran berhasil dibatalkan dan status invoice telah disesuaikan.');
        } catch (DomainException $e) {
            return redirect()->route('admin.invoices.show', $payment->invoice_id)
                ->with('error', $e->getMessage());
        }
    }
}
