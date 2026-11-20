<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('platform_invoices')) {
            Schema::create('platform_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_no', 50)->unique();
                $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('service_type', 50); // PREMIUM, ANALYTICS_ADDON, LISTING_SLOTS, UPGRADE_PREMIUM, SHOP_AD, PRODUCT_AD, SETUP_TRAINING
                $table->unsignedTinyInteger('package_duration_months')->nullable(); // 1, 3, 6, 12
                $table->decimal('amount', 18, 2);
                $table->string('status', 30)->default('UNPAID'); // UNPAID, PAYMENT_SUBMITTED, VERIFIED, EXPIRED, CANCELLED, REFUNDED
                $table->string('payment_method', 50)->nullable();
                $table->string('payment_proof_path', 255)->nullable();
                $table->dateTime('payment_submitted_at')->nullable();
                $table->dateTime('expires_at');
                $table->dateTime('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['outlet_id', 'status'], 'idx_platform_invoices_outlet_status');
                $table->index('status');
                $table->index('expires_at');
            });
        }

        if (! Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
                $table->string('type', 50); // PREMIUM, ANALYTICS_ADDON
                $table->dateTime('starts_at');
                $table->dateTime('expires_at');
                $table->string('status', 30)->default('ACTIVE'); // ACTIVE, EXPIRED, UPGRADED, CANCELLED
                $table->foreignId('platform_invoice_id')->nullable()->constrained('platform_invoices')->nullOnDelete();
                $table->timestamps();

                $table->index(['outlet_id', 'type', 'status'], 'idx_subscriptions_lookup');
                $table->index('expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('platform_invoices');
    }
};
