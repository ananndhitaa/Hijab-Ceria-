<?php

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Models\Master\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\Payment;
use App\Models\Master\Product;
use App\Models\Master\ProductVariant;
use App\Models\System\User;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
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

    private function createSampleInvoice(int $totalAmount = 13500000): Invoice
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

    public function test_admin_can_record_dp_payment_transitioning_invoice_to_partial(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        // Exact 50% = 6.750.000 with canonical Transfer Bank
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::DP->value,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP->value,
            'amount'         => '6750000.00',
            'status'         => PaymentStatus::CONFIRMED->value,
            'payment_method' => 'Transfer Bank',
        ]);

        $this->assertEquals(InvoiceStatus::PARTIAL, Invoice::find($invoice->id)->status);
    }

    public function test_tampered_dp_amount_rejected_with_error_message(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        // Attempting to submit 5.000.000 as DP (must be 6.750.000)
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::DP->value,
            'amount'         => 5000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('error', 'Nominal DP harus sebesar 50% dari total tagihan.');
        $this->assertDatabaseMissing('payments', ['amount' => '5000000.00']);
    }

    public function test_admin_can_record_full_pelunasan_transitioning_invoice_to_paid(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::PELUNASAN->value,
            'amount'         => 13500000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('success');
        $this->assertEquals(InvoiceStatus::PAID, Invoice::find($invoice->id)->status);
    }

    public function test_tampered_pelunasan_amount_rejected_with_error_message(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        // Record initial partial payment of 5.000.000 via LAINNYA
        $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 5000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        // Outstanding is 8.500.000. Tampered submit 8.000.000 as PELUNASAN must be rejected
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::PELUNASAN->value,
            'amount'         => 8000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('error', 'Nominal pelunasan harus sesuai dengan sisa tagihan.');
    }

    public function test_admin_can_record_manual_partial_payment_via_lainnya(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::LAINNYA->value,
            'amount'         => 3000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TUNAI->value,
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::LAINNYA->value,
            'amount'         => '3000000.00',
            'status'         => PaymentStatus::CONFIRMED->value,
            'payment_method' => 'Tunai',
        ]);

        $this->assertEquals(InvoiceStatus::PARTIAL, Invoice::find($invoice->id)->status);
    }

    public function test_payment_requires_valid_data_and_amount_gt_zero(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => 'INVALID_TYPE',
            'amount'         => -50000,
            'payment_date'   => 'not-a-date',
            'payment_method' => 'INVALID_METHOD',
        ]);

        $response->assertSessionHasErrors(['payment_type', 'amount', 'payment_date', 'payment_method']);
    }

    public function test_overpayment_submission_is_rejected(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        // Attempting to pay 14.000.000 via LAINNYA (exceeds 13.500.000 total)
        $response = $this->actingAs($this->admin)->post(route('admin.invoices.payments.store', $invoice), [
            'payment_type'   => PaymentType::LAINNYA->value,
            'amount'         => 14000000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK->value,
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('payments', ['amount' => '14000000.00']);
    }

    public function test_admin_can_cancel_payment_recalculating_invoice_status(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $payment1 = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        $payment2 = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        $this->assertEquals(InvoiceStatus::PAID, Invoice::find($invoice->id)->status);

        // Cancel payment2 via controller
        $response = $this->actingAs($this->admin)->post(route('admin.payments.cancel', $payment2));

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $response->assertSessionHas('success');

        $this->assertEquals(PaymentStatus::CANCELLED, Payment::find($payment2->id)->status);
        $this->assertEquals(InvoiceStatus::PARTIAL, Invoice::find($invoice->id)->status);
    }

    public function test_invoice_show_displays_precalculated_amounts_and_canonical_payment_method(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertSee('6750000');
        $response->assertSee('13500000');
        $response->assertSee('50% dari total tagihan');
        $response->assertSee('Transfer Bank');
        $response->assertSee('Unduh CSV');
    }

    public function test_admin_can_export_invoice_csv(): void
    {
        $invoice = $this->createSampleInvoice(13500000);

        $response = $this->actingAs($this->admin)->get(route('admin.invoices.export-csv', $invoice));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString("invoice_{$invoice->invoice_no}.csv", $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();

        // Check UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Check Header columns
        $this->assertStringContainsString('Nomor Invoice', $content);
        $this->assertStringContainsString('Tanggal Invoice', $content);
        $this->assertStringContainsString('Status Invoice', $content);
        $this->assertStringContainsString('Nomor Sales Order', $content);
        $this->assertStringContainsString('Customer', $content);
        $this->assertStringContainsString('Telepon', $content);
        $this->assertStringContainsString('Produk', $content);
        $this->assertStringContainsString('Varian', $content);
        $this->assertStringContainsString('SKU', $content);
        $this->assertStringContainsString('Qty', $content);
        $this->assertStringContainsString('Harga Satuan', $content);
        $this->assertStringContainsString('Subtotal', $content);
        $this->assertStringContainsString('Total Invoice', $content);
        $this->assertStringContainsString('Total Terbayar', $content);
        $this->assertStringContainsString('Sisa Tagihan', $content);

        // Check machine-readable numeric value without "Rp"
        $this->assertStringContainsString('13500000.00', $content);
        $this->assertStringNotContainsString('Rp', $content);

        // Ensure no Owner metrics
        $this->assertStringNotContainsString('HPP', $content);
        $this->assertStringNotContainsString('Profit', $content);
        $this->assertStringNotContainsString('EBITDA', $content);
    }

    public function test_payment_destroy_route_is_absent(): void
    {
        $invoice = $this->createSampleInvoice(13500000);
        $payment = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/payments/{$payment->id}");
        $this->assertContains($response->status(), [404, 405]);
    }

    public function test_non_admin_cannot_record_or_cancel_payments_or_export_invoice_csv(): void
    {
        $invoice = $this->createSampleInvoice(13500000);
        $payment = $this->paymentService->recordPayment($invoice, [
            'payment_type'   => PaymentType::DP,
            'amount'         => 6750000,
            'payment_date'   => '2026-08-27',
            'payment_method' => PaymentMethod::TRANSFER_BANK,
        ]);

        $roles = [UserRole::GUDANG, UserRole::PRODUKSI, UserRole::CUSTOMER];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->post(route('admin.invoices.payments.store', $invoice), [
                'payment_type'   => PaymentType::PELUNASAN->value,
                'amount'         => 6750000,
                'payment_date'   => '2026-08-27',
                'payment_method' => 'Transfer Bank',
            ])->assertStatus(403);

            $this->actingAs($user)->post(route('admin.payments.cancel', $payment))->assertStatus(403);
            $this->actingAs($user)->get(route('admin.invoices.export-csv', $invoice))->assertStatus(403);
        }
    }
}
