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
        if (! Schema::hasTable('ad_campaigns')) {
            Schema::create('ad_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ad_type', 30); // SHOP_AD, PRODUCT_AD
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('title', 150);
                $table->string('tagline', 255)->nullable();
                $table->string('banner_path', 255)->nullable();
                $table->decimal('total_budget', 18, 2)->default(0);
                $table->unsignedInteger('duration_days')->default(0);
                $table->decimal('effective_bid_per_day', 18, 2)->default(0);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->string('status', 30)->default('DRAFT');
                // DRAFT, PENDING_REVIEW, NEEDS_REVISION, APPROVED, PAYMENT_PENDING, SCHEDULED, ACTIVE, COMPLETED, CANCELLED, REJECTED, SUSPENDED
                $table->unsignedSmallInteger('schedule_change_count')->default(0);
                $table->unsignedSmallInteger('creative_revision_count')->default(0);
                $table->foreignId('platform_invoice_id')->nullable()->constrained('platform_invoices')->nullOnDelete();
                $table->dateTime('verified_payment_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['ad_type', 'status', 'starts_at', 'ends_at'], 'idx_ads_active_schedule');
                $table->index(['outlet_id', 'ad_type', 'status'], 'idx_ads_outlet_lookup');
                $table->index('effective_bid_per_day');
            });
        }

        if (! Schema::hasTable('ad_campaign_revisions')) {
            Schema::create('ad_campaign_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
                $table->string('title', 150);
                $table->string('tagline', 255)->nullable();
                $table->string('banner_path', 255)->nullable();
                $table->string('revision_type', 30); // CREATIVE, FACTUAL
                $table->string('status', 30)->default('PENDING'); // PENDING, APPROVED, REJECTED
                $table->text('review_notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('reviewed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_campaign_revisions');
        Schema::dropIfExists('ad_campaigns');
    }
};
