<?php

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\System\User;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\PaymentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPaymentTypeFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private InvoiceService $invoiceService;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->invoiceService = app(InvoiceService::class);
        $this->paymentService = app(PaymentService::class);
    }

    private function createSampleInvoice(int $totalAmount = 3217000): Invoice
    {
        $customer = Customer::create([
            'customer_name' => 'Toko Busana Muslim',
            'phone'         => '081234567893',
            'address'       => 'Jl. Dago No. 15 Bandung',
        ]);
        $product = Product::create([
            'product_name' => 'Bergo Maryam',
            'active'       => true,
        ]);
        $variant = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Navy',
            'sku'                => 'SKU-BERGO-NVY-' . uniqid(),
            'base_selling_price' => $totalAmount,
        ]);

        $order = Order::create([
            'order_no'    => 'SO-202610-' . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'order_date'  => '2026-10-12',
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

    /**
     * A. No confirmed payment -> first payment may be DP.
     */
    public function test_a_no_confirmed_payment_allows_first_payment_to_be_dp(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        $this->assertFalse($invoice->hasConfirmedDp());
        $this->assertFalse($this->invoiceService->hasConfirmedDp($invoice));

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice));
        $response->assertOk();
        $response->assertViewHas('hasConfirmedDp', false);

        // UI default is DP and DP option is available
        $content = $response->getContent();
        $this->assertStringContainsString("paymentType: 'DP'", $content);
        $this->assertStringContainsString('<option value="DP">Uang Muka (DP)</option>', $content);

        // Record first payment as DP (50% = 1608500.00)
        $postResponse = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::DP->value,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $postResponse->assertRedirect(route('admin.invoices.show', $invoice));
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'invoice_id'   => $invoice->id,
            'payment_type' => PaymentType::DP->value,
            'amount'       => '1608500.00',
            'status'       => PaymentStatus::CONFIRMED->value,
        ]);

        $reloaded = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PARTIAL, $reloaded->status);
    }

    /**
     * B. Confirmed DP exists -> next form defaults to Pelunasan.
     */
    public function test_b_confirmed_dp_exists_causes_next_form_to_default_to_pelunasan(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        // Record confirmed DP
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $this->assertTrue($invoice->hasConfirmedDp());

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice));
        $response->assertOk();
        $response->assertViewHas('hasConfirmedDp', true);

        $content = $response->getContent();
        $this->assertStringContainsString("paymentType: 'PELUNASAN'", $content);
        $this->assertStringContainsString("inputAmount: '1608500.00'", $content);
    }

    /**
     * C. Confirmed DP exists -> DP option unavailable / cannot be selected.
     */
    public function test_c_confirmed_dp_exists_dp_option_unavailable_and_cannot_be_selected(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice));
        $response->assertOk();

        $content = $response->getContent();
        // DP option must NOT be present in select options
        $this->assertStringNotContainsString('<option value="DP">', $content);
        // Pelunasan must be present
        $this->assertStringContainsString('<option value="PELUNASAN">Pelunasan</option>', $content);
    }

    /**
     * D. Direct manipulated request with second DP -> rejected server-side.
     */
    public function test_d_direct_manipulated_request_with_second_dp_rejected_server_side(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        // First DP is confirmed
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        // Attempt second DP via direct POST
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::DP->value,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('error', 'Uang muka (DP) sudah tercatat. Pembayaran berikutnya dicatat sebagai Pelunasan.');

        // Verify only 1 payment exists
        $this->assertEquals(1, $invoice->payments()->count());
    }

    /**
     * E. Pending DP only -> does not count as confirmed DP.
     */
    public function test_e_pending_dp_only_does_not_count_as_confirmed_dp(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'status'         => PaymentStatus::PENDING,
        ]);

        $this->assertFalse($invoice->hasConfirmedDp());

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice));
        $response->assertOk();
        $response->assertViewHas('hasConfirmedDp', false);

        $content = $response->getContent();
        $this->assertStringContainsString("paymentType: 'DP'", $content);
        $this->assertStringContainsString('<option value="DP">Uang Muka (DP)</option>', $content);
    }

    /**
     * F. Cancelled DP only -> does not count.
     */
    public function test_f_cancelled_dp_only_does_not_count(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'status'         => PaymentStatus::CANCELLED,
        ]);

        $this->assertFalse($invoice->hasConfirmedDp());

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice));
        $response->assertOk();
        $response->assertViewHas('hasConfirmedDp', false);

        $content = $response->getContent();
        $this->assertStringContainsString("paymentType: 'DP'", $content);
        $this->assertStringContainsString('<option value="DP">Uang Muka (DP)</option>', $content);
    }

    /**
     * G. Rejected / non-confirmed status only does not count.
     */
    public function test_g_non_confirmed_payment_does_not_activate_dp_rule(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        // Create both a CANCELLED and a PENDING DP
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-10',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'status'         => PaymentStatus::CANCELLED,
        ]);
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-11',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'status'         => PaymentStatus::PENDING,
        ]);

        $this->assertFalse($this->invoiceService->hasConfirmedDp($invoice));
    }

    /**
     * H. Confirmed DP + subsequent Pelunasan -> accepted.
     */
    public function test_h_confirmed_dp_plus_subsequent_pelunasan_accepted(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        // 1. Initial DP
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        // 2. Subsequent Pelunasan
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::PELUNASAN->value,
            'amount'         => 1608500,
            'payment_date'   => '2026-10-13',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('success');

        $reloaded = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PAID, $reloaded->status);
        $this->assertEquals('0.00', $this->invoiceService->calculateOutstandingBalance($reloaded));
        $this->assertEquals('3217000.00', $this->invoiceService->calculatePaidAmount($reloaded));
    }

    /**
     * I. Multiple post-DP payments, if existing partial-payment flow allows them -> all categorized Pelunasan.
     */
    public function test_i_subsequent_settlement_is_categorized_pelunasan(): void
    {
        $invoice = $this->createSampleInvoice(10000000);

        // Confirmed DP = 5.000.000
        $dpPayment = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 5000000,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);
        $this->assertEquals(PaymentType::DP, $dpPayment->payment_type);

        // Next payment for remaining balance is Pelunasan
        $pelunasanPayment = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 5000000,
            'payment_date'   => '2026-10-13',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);
        $this->assertEquals(PaymentType::PELUNASAN, $pelunasanPayment->payment_type);

        $reloaded = Invoice::find($invoice->id);
        $this->assertEquals(InvoiceStatus::PAID, $reloaded->status);
    }

    /**
     * J. Outstanding = 0 -> additional payment rejected & CTA hidden.
     */
    public function test_j_outstanding_zero_rejects_additional_payment_and_hides_cta(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        // Full payment via Pelunasan
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 3217000,
            'payment_date'   => '2026-10-12',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $this->assertEquals(InvoiceStatus::PAID, Invoice::find($invoice->id)->status);

        // CTA Catat Pembayaran must NOT be rendered
        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice));
        $response->assertOk();
        $response->assertDontSee('Catat Pembayaran');

        // Extra payment attempt must be rejected server-side
        $postResponse = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::PELUNASAN->value,
            'amount'         => 500000,
            'payment_date'   => '2026-10-13',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $postResponse->assertRedirect(route('admin.invoices.show', $invoice));
        $postResponse->assertSessionHas('error', 'Tagihan invoice ini sudah lunas.');
    }

    /**
     * K. Historical payment records unchanged.
     */
    public function test_k_historical_payment_records_remain_unchanged(): void
    {
        $invoice = $this->createSampleInvoice(3217000);

        $payment = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 1608500,
            'payment_date'   => '2026-09-01',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        $this->invoiceService->recalculateStatus($invoice);

        // Verify record values
        $fresh = Payment::find($payment->id);
        $this->assertEquals('1608500.00', $fresh->amount);
        $this->assertEquals(PaymentType::DP, $fresh->payment_type);
        $this->assertEquals(PaymentStatus::CONFIRMED, $fresh->status);
        $this->assertEquals('2026-09-01', $fresh->payment_date->toDateString());
    }
}
