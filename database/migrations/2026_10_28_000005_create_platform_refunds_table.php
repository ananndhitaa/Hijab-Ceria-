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
        if (! Schema::hasTable('platform_refunds')) {
            Schema::create('platform_refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
                $table->foreignId('platform_invoice_id')->nullable()->constrained('platform_invoices')->nullOnDelete();
                $table->foreignId('ad_campaign_id')->nullable()->constrained('ad_campaigns')->nullOnDelete();
                $table->string('refund_type', 30); // FULL_100_PERCENT, PROPORTIONAL
                $table->decimal('amount', 18, 2);
                $table->text('reason');
                $table->string('status', 30)->default('REQUESTED'); // REQUESTED, APPROVED, COMPLETED, REJECTED
                $table->string('destination_bank_name', 100);
                $table->string('destination_account_number', 50);
                $table->string('destination_account_name', 150);
                $table->string('transfer_proof_path', 255)->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();

                $table->index(['outlet_id', 'status']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_refunds');
    }
};
