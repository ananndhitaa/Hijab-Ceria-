<?php

namespace Tests\Unit;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\PaymentService;
use DomainException;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
        $this->invoiceService = app(InvoiceService::class);
    }

    private function createSampleInvoice(int $totalAmount = 13500000): Invoice
    {
        $customer = Customer::create([
            'customer_name' => 'Toko Hijab Berkah',
            'phone'         => '081234567891',
            'address'       => 'Jl. Riau No. 50 Bandung',
        ]);
        $product = Product::create([
            'product_name' => 'Voal Syar\'i',
            'active'       => true,
        ]);
        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Black',
            'sku'                => 'SKU-VOAL-BLK-' . uniqid(),
            'base_selling_price' => $totalAmount,
        ]);

        $order = Order::create([
            'order_no'    => 'SO-202608-' . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'order_date'  => '2026-08-27',
            'status'      => OrderStatus::DEAL,
            'total_qty'   => 1,
            'grand_total' => $totalAmount,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant->id,
            'qty_ordered'        => 1,
            'unit_selling_price' => $totalAmount,
            'subtotal'           => $totalAmount,
        ]);

        return $this->invoiceService->createForOrder($order);
    }

    public function test_can_calculate_dp_amount_as_50_percent_of_invoice_total(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $dp = $this->invoiceService->calculateDpAmount($invoice);
        $this->assertEquals('6750000.00', $dp);

        $suggested = $this->invoiceService->calculateSuggestedAmount($invoice, PaymentType::DP);
        $this->assertEquals('6750000.00', $suggested);
    }

    public function test_can_calculate_pelunasan_amount_as_exact_outstanding_balance(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $pelunasan = $this->invoiceService->calculateSuggestedAmount($invoice, PaymentType::PELUNASAN);
        $this->assertEquals('13500000.00', $pelunasan);

        // Record a partial payment of 5.000.000 via LAINNYA
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 5000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer BCA',
        ]);

        $outstanding = $this->invoiceService->calculateOutstandingBalance($invoice);
        $this->assertEquals('8500000.00', $outstanding);

        $pelunasanAfterPartial = $this->invoiceService->calculateSuggestedAmount($invoice, PaymentType::PELUNASAN);
        $this->assertEquals('8500000.00', $pelunasanAfterPartial);
    }

    public function test_dp_is_capped_when_outstanding_is_less_than_50_percent(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        // Pay 9.500.000 via LAINNYA, leaving outstanding = 4.000.000 (< 50% which is 6.750.000)
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 9500000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer BCA',
        ]);

        $dp = $this->invoiceService->calculateDpAmount($invoice);
        $this->assertEquals('4000000.00', $dp);

        // Recording DP of 4.000.000 is accepted because it equals capped DP
        $payment = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 4000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer BCA',
        ]);

        $this->assertEquals('4000000.00', $payment->amount);
        $reloaded = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PAID, $reloaded->status);
    }

    public function test_can_record_dp_payment_and_transition_invoice_to_partial(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $payment = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer BCA',
        ]);

        $this->assertDatabaseHas('payments', [
            'id'             => $payment->id,
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP->value,
            'amount'         => '6750000.00',
            'status'         => PaymentStatus::CONFIRMED->value,
            'payment_method' => 'Transfer Bank',
        ]);

        $reloadedInvoice = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PARTIAL, $reloadedInvoice->status);
    }

    public function test_tampered_dp_amount_is_rejected(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nominal DP harus sebesar 50% dari total tagihan.');

        // 5.000.000 is not equal to 50% (6.750.000)
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 5000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer BCA',
        ]);
    }

    public function test_tampered_pelunasan_amount_is_rejected(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        // Record partial payment of 5.000.000
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 5000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer BCA',
        ]);

        // Outstanding is 8.500.000. Tampered attempt to submit 8.000.000 as PELUNASAN must be rejected.
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nominal pelunasan harus sesuai dengan sisa tagihan.');

        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 8000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer BCA',
        ]);
    }

    public function test_lainnya_allows_valid_manual_partial_amount(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $payment = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 3000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Tunai',
        ]);

        $this->assertEquals('3000000.00', $payment->amount);
        $reloaded = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PARTIAL, $reloaded->status);
    }

    public function test_payment_amount_must_be_greater_than_zero(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Jumlah pembayaran harus lebih besar dari 0.');

        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 0,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
        ]);
    }

    public function test_overpayment_is_strictly_rejected_under_lock(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        // Record initial confirmed payment of 10.000.000 (remaining: 3.500.000)
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 10000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
        ]);

        // Attempt to pay 4.000.000 via LAINNYA (exceeds 3.500.000 remaining)
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Jumlah pembayaran melebihi sisa tagihan. Maksimal pembayaran yang dapat dicatat adalah Rp 3.500.000.');

        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 4000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
        ]);
    }

    public function test_full_pelunasan_updates_invoice_to_paid(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 13500000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
        ]);

        $reloadedInvoice = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PAID, $reloadedInvoice->status);
    }

    public function test_cancel_payment_updates_status_and_recalculates_invoice(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $payment1 = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
        ]);

        $payment2 = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
        ]);

        $reloadedInvoice = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PAID, $reloadedInvoice->status);

        // Cancel payment2
        $this->paymentService->cancelPayment($payment2);

        $reloadedPayment2 = Payment::find($payment2->id);
        $this->assertEquals(PaymentStatus::CANCELLED, $reloadedPayment2->status);

        $reloadedInvoice2 = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PARTIAL, $reloadedInvoice2->status);

        // Cancelled payment must not reduce outstanding
        $outstanding = $this->invoiceService->calculateOutstandingBalance($reloadedInvoice2);
        $this->assertEquals('6750000.00', $outstanding);
    }

    public function test_cancelling_already_cancelled_payment_is_rejected(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $payment = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
        ]);

        $this->paymentService->cancelPayment($payment);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Pembayaran sudah dalam status dibatalkan.');

        $this->paymentService->cancelPayment($payment);
    }

    public function test_payment_on_cancelled_invoice_is_rejected(): void
    {
        $invoice = $this->createSampleInvoice(13500000);
        $invoice->status = InvoiceStatus::CANCELLED;
        $invoice->save();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Tidak dapat mencatat pembayaran untuk invoice yang sudah dibatalkan.');

        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
        ]);
    }
}
