<?php

namespace Tests\Feature\System;

use App\Enums\BatchStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseApprovalStatus;
use App\Enums\PurchaseStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\System\User;
use App\Support\StatusBadgePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class GlobalStatusBadgeSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A. Sales Order Selesai renders green semantic class (emerald).
     */
    public function test_sales_order_selesai_renders_green_semantic_class(): void
    {
        $classes = StatusBadgePresenter::classes(OrderStatus::SELESAI, 'order');
        $this->assertStringContainsString('emerald', $classes);
        $this->assertStringContainsString('bg-emerald-50', $classes);
        $this->assertStringContainsString('text-emerald-700', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="order" />', [
            'status' => OrderStatus::SELESAI,
        ]);
        $this->assertStringContainsString('emerald', $rendered);
        $this->assertStringContainsString('Selesai', $rendered);
    }

    /**
     * B. Sales Order Dikirim renders teal semantic class.
     */
    public function test_sales_order_dikirim_renders_teal_semantic_class(): void
    {
        $classes = StatusBadgePresenter::classes(OrderStatus::DIKIRIM, 'order');
        $this->assertStringContainsString('teal', $classes);
        $this->assertStringContainsString('bg-teal-50', $classes);
        $this->assertStringContainsString('text-teal-700', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="order" />', [
            'status' => OrderStatus::DIKIRIM,
        ]);
        $this->assertStringContainsString('teal', $rendered);
        $this->assertStringContainsString('Dikirim', $rendered);
    }

    /**
     * C. Purchase Rejected renders red semantic class.
     */
    public function test_purchase_rejected_renders_red_semantic_class(): void
    {
        $classes = StatusBadgePresenter::classes(PurchaseApprovalStatus::REJECTED, 'approval');
        $this->assertStringContainsString('red', $classes);
        $this->assertStringContainsString('bg-red-50', $classes);
        $this->assertStringContainsString('text-red-700', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="approval" />', [
            'status' => PurchaseApprovalStatus::REJECTED,
        ]);
        $this->assertStringContainsString('red', $rendered);
        $this->assertStringContainsString('Ditolak', $rendered);
    }

    /**
     * D. Purchase Approved renders green semantic class.
     */
    public function test_purchase_approved_renders_green_semantic_class(): void
    {
        $classes = StatusBadgePresenter::classes(PurchaseApprovalStatus::APPROVED, 'approval');
        $this->assertStringContainsString('emerald', $classes);
        $this->assertStringContainsString('bg-emerald-50', $classes);
        $this->assertStringContainsString('text-emerald-700', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="approval" />', [
            'status' => PurchaseApprovalStatus::APPROVED,
        ]);
        $this->assertStringContainsString('emerald', $rendered);
        $this->assertStringContainsString('Disetujui', $rendered);
    }

    /**
     * E. Invoice Lunas renders green semantic class.
     */
    public function test_invoice_lunas_renders_green_semantic_class(): void
    {
        $classes = StatusBadgePresenter::classes(InvoiceStatus::PAID, 'invoice');
        $this->assertStringContainsString('emerald', $classes);
        $this->assertStringContainsString('bg-emerald-50', $classes);
        $this->assertStringContainsString('text-emerald-700', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="invoice" />', [
            'status' => InvoiceStatus::PAID,
        ]);
        $this->assertStringContainsString('emerald', $rendered);
        $this->assertStringContainsString('Lunas', $rendered);
    }

    /**
     * F. Invoice Partial renders amber semantic class.
     */
    public function test_invoice_partial_renders_amber_semantic_class(): void
    {
        $classes = StatusBadgePresenter::classes(InvoiceStatus::PARTIAL, 'invoice');
        $this->assertStringContainsString('amber', $classes);
        $this->assertStringContainsString('bg-amber-50', $classes);
        $this->assertStringContainsString('text-amber-700', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="invoice" />', [
            'status' => InvoiceStatus::PARTIAL,
        ]);
        $this->assertStringContainsString('amber', $rendered);
    }

    /**
     * G. Production QC renders blue/sky semantic class.
     */
    public function test_production_qc_renders_blue_sky_semantic_class(): void
    {
        $classes = StatusBadgePresenter::classes(BatchStatus::QC, 'batch');
        $this->assertStringContainsString('sky', $classes);
        $this->assertStringContainsString('bg-sky-50', $classes);
        $this->assertStringContainsString('text-sky-700', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="batch" />', [
            'status' => BatchStatus::QC,
        ]);
        $this->assertStringContainsString('sky', $rendered);
        $this->assertStringContainsString('QC', $rendered);
    }

    /**
     * H. Shipment Dikirim renders teal semantic class.
     */
    public function test_shipment_dikirim_renders_teal_semantic_class(): void
    {
        $classes = StatusBadgePresenter::classes(ShipmentStatus::SHIPPED, 'shipment');
        $this->assertStringContainsString('teal', $classes);
        $this->assertStringContainsString('bg-teal-50', $classes);
        $this->assertStringContainsString('text-teal-700', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="shipment" />', [
            'status' => ShipmentStatus::SHIPPED,
        ]);
        $this->assertStringContainsString('teal', $rendered);
        $this->assertStringContainsString('Dikirim', $rendered);
    }

    /**
     * I. Customer completed status renders green.
     */
    public function test_customer_completed_status_renders_green(): void
    {
        $classes = StatusBadgePresenter::classes(OrderStatus::SELESAI, 'order', customer: true);
        $this->assertStringContainsString('emerald', $classes);

        $rendered = Blade::render('<x-status-badge :status="$status" context="order" :customer="true" />', [
            'status' => OrderStatus::SELESAI,
        ]);
        $this->assertStringContainsString('emerald', $rendered);
        $this->assertStringContainsString('Selesai', $rendered);
    }

    /**
     * J. Unknown status uses neutral fallback.
     */
    public function test_unknown_status_uses_neutral_fallback(): void
    {
        $classes = StatusBadgePresenter::classes('RANDOM_UNREGISTERED_STATUS', 'order');
        $this->assertSame('bg-slate-50 text-slate-700 border-slate-200', $classes);

        $rendered = Blade::render('<x-status-badge status="RANDOM_UNREGISTERED_STATUS" context="order" />');
        $this->assertStringContainsString('bg-slate-50', $rendered);
        $this->assertStringContainsString('text-slate-700', $rendered);
        $this->assertStringContainsString('border-slate-200', $rendered);
    }

    /**
     * K. Reusable component/helper does not change status value.
     */
    public function test_reusable_component_helper_does_not_change_status_value(): void
    {
        $status = OrderStatus::SELESAI;
        $info = StatusBadgePresenter::get($status, 'order');

        $this->assertSame(OrderStatus::SELESAI, $status);
        $this->assertSame('SELESAI', $status->value);
        $this->assertSame('Selesai', $info['label']);
        $this->assertStringContainsString('bg-emerald-50', $info['classes']);
        $this->assertStringContainsString('rounded-lg', $info['full_classes']);
    }

    /**
     * L. Role visibility/security unchanged.
     */
    public function test_role_visibility_and_security_unchanged(): void
    {
        $gudang = User::factory()->create([
            'role'   => UserRole::GUDANG,
            'status' => UserStatus::ACTIVE,
        ]);

        $customer = User::factory()->create([
            'role'   => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        // Gudang cannot access financial records
        $response = $this->actingAs($gudang)->get(route('admin.financial-records.index'));
        $this->assertTrue(in_array($response->status(), [403, 302]));

        // Gudang cannot access admin purchase approvals
        $response = $this->actingAs($gudang)->get(route('admin.purchases.approvals.index'));
        $this->assertTrue(in_array($response->status(), [403, 302]));

        // Customer cannot access admin orders
        $response = $this->actingAs($customer)->get(route('admin.orders.index'));
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }
}
