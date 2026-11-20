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
        if (! Schema::hasTable('owner_complaints')) {
            Schema::create('owner_complaints', function (Blueprint $table) {
                $table->id();
                $table->string('complaint_code', 32)->unique();
                $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('category', 50); // ERP_SYSTEM, ACCOUNT_ACCESS, BILLING_PAYMENT, SUBSCRIPTION, ADS_PROMOTION, SETUP_TRAINING, OTHER
                $table->string('title', 255);
                $table->text('description');
                $table->string('attachment_path', 255)->nullable();
                $table->string('status', 30)->default('NEW'); // NEW, IN_PROGRESS, WAITING_OWNER, RESOLVED, CLOSED
                $table->string('priority', 20)->default('NORMAL'); // NORMAL, HIGH, URGENT
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('last_reply_at')->nullable();
                $table->timestamps();

                $table->index(['outlet_id', 'status']);
                $table->index('status');
                $table->index('category');
            });
        }

        if (! Schema::hasTable('owner_complaint_replies')) {
            Schema::create('owner_complaint_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('complaint_id')->constrained('owner_complaints')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('is_from_super_admin')->default(false);
                $table->text('message');
                $table->string('attachment_path', 255)->nullable();
                $table->timestamps();

                $table->index(['complaint_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_complaint_replies');
        Schema::dropIfExists('owner_complaints');
    }
};

