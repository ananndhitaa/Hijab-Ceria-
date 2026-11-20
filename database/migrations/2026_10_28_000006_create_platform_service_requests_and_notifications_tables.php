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
        if (! Schema::hasTable('platform_service_requests')) {
            Schema::create('platform_service_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('service_type', 50); // SETUP_ONBOARDING, TRAINING_STAFF, CUSTOM_CONSULTATION
                $table->text('scope_details');
                $table->date('preferred_date')->nullable();
                $table->string('status', 30)->default('SUBMITTED'); // SUBMITTED, IN_REVIEW, SCOPED, SCHEDULED, IN_PROGRESS, COMPLETED, CANCELLED
                $table->decimal('custom_quote_amount', 18, 2)->nullable();
                $table->text('super_admin_notes')->nullable();
                $table->timestamps();

                $table->index(['outlet_id', 'status']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('platform_notifications')) {
            Schema::create('platform_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outlet_id')->nullable()->constrained('outlets')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('type', 50); // AD_REVIEW, AD_REMINDER, INVOICE_EXPIRY, SUBSCRIPTION_EXPIRY, REFUND_UPDATE, SERVICE_REQUEST
                $table->string('title', 150);
                $table->text('message');
                $table->string('action_url', 255)->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->index(['outlet_id', 'is_read']);
                $table->index(['user_id', 'is_read']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_notifications');
        Schema::dropIfExists('platform_service_requests');
    }
};
