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
use App\Services\System\DocumentNumberGenerator;
use App\Services\Sales\InvoiceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = app(InvoiceService::class);
    }

    private function createSampleOrder(OrderStatus $status = OrderStatus::DEAL): Order
    {
        $customer = Customer::create([
            'customer_name' => 'Toko Busana Muslimah',
            'phone'         => '081234567890',
            'address'       => 'Jl. Sukajadi No. 100 Bandung',
        ]);
        $product = Product::create([
            'product_name' => 'Pashmina Silk Premium',
            'active'       => true,
        ]);
        $variant1 = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Mauve',
            'sku'                => 'SKU-SILK-MV-' . uniqid(),
            'base_selling_price' => 50000,
        ]);
        $variant2 = ProductVariant::create([
            'product_id'         => $product->id,
            'variant_name'       => 'Dusty Pink',
            'sku'                => 'SKU-SILK-DP-' . uniqid(),
            'base_selling_price' => 75000,
        ]);

        $order = Order::create([
            'order_no'    => 'SO-202608-' . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'order_date'  => '2026-08-27',
            'status'      => $status,
            'total_qty'   => 3,
            'grand_total' => 175000,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant1->id,
            'qty_ordered'        => 2,
            'unit_selling_price' => 50000,
            'subtotal'           => 100000,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'variant_id'         => $variant2->id,
            'qty_ordered'        => 1,
            'unit_selling_price' => 75000,
            'subtotal'           => 75000,
        ]);

        return $order;
    }

    public function test_can_create_invoice_for_deal_order(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);

        $invoice = $this->invoiceService->createForOrder($order);

        $this->assertDatabaseHas('invoices', [
            'id'           => $invoice->id,
            'order_id'     => $order->id,
            'status'       => InvoiceStatus::UNPAID->value,
            'total_amount' => '175000.00',
        ]);

        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{4}$/', $invoice->invoice_no);
        $this->assertEquals(InvoiceStatus::UNPAID, $invoice->status);
    }

    public function test_can_create_invoice_for_later_operational_statuses(): void
    {
        $statuses = [
            OrderStatus::BAHAN,
            OrderStatus::PRINTING,
            OrderStatus::PRODUKSI,
            OrderStatus::QC,
            OrderStatus::PACKING,
            OrderStatus::DIKIRIM,
            OrderStatus::SELESAI,
        ];

        foreach ($statuses as $status) {
            $order = $this->createSampleOrder($status);
            $invoice = $this->invoiceService->createForOrder($order);
            $this->assertNotNull($invoice->id);
            $this->assertEquals(InvoiceStatus::UNPAID, $invoice->status);
        }
    }

    public function test_cannot_create_invoice_for_komunikasi_order(): void
    {
        $order = $this->createSampleOrder(OrderStatus::KOMUNIKASI);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invoice hanya dapat dibuat untuk pesanan yang sudah mencapai status DEAL atau setelahnya.');

        $this->invoiceService->createForOrder($order);
    }

    public function test_one_order_cannot_have_two_invoices(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);

        $this->invoiceService->createForOrder($order);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invoice untuk pesanan ini sudah pernah dibuat.');

        $this->invoiceService->createForOrder($order);
    }

    public function test_invoice_total_uses_order_item_snapshot_prices(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        $this->assertEquals('175000.00', (string) $invoice->total_amount);

        // Changing base product variant master prices should not affect existing invoice total
        ProductVariant::query()->update(['base_selling_price' => 999999]);

        $reloadedInvoice = Invoice::find($invoice->id);
        $this->assertEquals('175000.00', (string) $reloadedInvoice->total_amount);
    }

    public function test_calculate_paid_amount_counts_only_confirmed_payments(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 50000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 75000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
            'status'         => PaymentStatus::PENDING, // Not counted
        ]);

        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::LAINNYA,
            'amount'         => 25000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
            'status'         => PaymentStatus::CANCELLED, // Not counted
        ]);

        $paidAmount = $this->invoiceService->calculatePaidAmount($invoice);
        $this->assertEquals('50000.00', $paidAmount);

        $outstanding = $this->invoiceService->calculateOutstandingBalance($invoice);
        $this->assertEquals('125000.00', $outstanding);
    }

    public function test_recalculate_status_transitions_correctly(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        // Initially UNPAID
        $this->invoiceService->recalculateStatus($invoice);
        $this->assertEquals(InvoiceStatus::UNPAID, $invoice->status);

        // Record partial payment
        $payment1 = Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::DP,
            'amount'         => 100000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        $this->invoiceService->recalculateStatus($invoice);
        $this->assertEquals(InvoiceStatus::PARTIAL, $invoice->status);

        // Record remaining payment
        Payment::create([
            'invoice_id'     => $invoice->id,
            'payment_type'   => PaymentType::PELUNASAN,
            'amount'         => 75000,
            'payment_date'   => '2026-08-27',
            'payment_method' => 'Transfer',
            'status'         => PaymentStatus::CONFIRMED,
        ]);

        $this->invoiceService->recalculateStatus($invoice);
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);

        // Cancel payment1 -> drops back to PARTIAL
        $payment1->status = PaymentStatus::CANCELLED;
        $payment1->save();

        $this->invoiceService->recalculateStatus($invoice);
        $this->assertEquals(InvoiceStatus::PARTIAL, $invoice->status);
    }

    public function test_cancelled_invoice_preserves_cancelled_status_on_recalculation(): void
    {
        $order = $this->createSampleOrder(OrderStatus::DEAL);
        $invoice = $this->invoiceService->createForOrder($order);

        $invoice->status = InvoiceStatus::CANCELLED;
        $invoice->save();

        $this->invoiceService->recalculateStatus($invoice);
        $this->assertEquals(InvoiceStatus::CANCELLED, $invoice->status);
    }

    public function test_can_create_for_order_evaluates_correctly(): void
    {
        $komunikasiOrder = $this->createSampleOrder(OrderStatus::KOMUNIKASI);
        $this->assertFalse($this->invoiceService->canCreateForOrder($komunikasiOrder));

        $dealOrder = $this->createSampleOrder(OrderStatus::DEAL);
        $this->assertTrue($this->invoiceService->canCreateForOrder($dealOrder));

        $bahanOrder = $this->createSampleOrder(OrderStatus::BAHAN);
        $this->assertTrue($this->invoiceService->canCreateForOrder($bahanOrder));

        // When invoice already created
        $this->invoiceService->createForOrder($dealOrder);
        $this->assertFalse($this->invoiceService->canCreateForOrder($dealOrder));
    }
}
