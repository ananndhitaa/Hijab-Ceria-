<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fase 8.4.5D4A: Additive creation of business_expense_histories table
     * to provide an immutable, tenant-scoped audit trail for expense amendments.
     */
    public function up(): void
    {
        if (! Schema::hasTable('business_expense_histories')) {
            Schema::create('business_expense_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_expense_id')
                    ->constrained('business_expenses')
                    ->onDelete('restrict');
                $table->foreignId('outlet_id')
                    ->nullable()
                    ->constrained('outlets')
                    ->onDelete('restrict');
                $table->foreignId('actor_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('restrict');
                $table->string('reason', 500);

                // Financial & Date Snapshots
                $table->decimal('old_amount', 18, 2);
                $table->decimal('new_amount', 18, 2);
                $table->date('old_expense_date');
                $table->date('new_expense_date');

                // Metadata Snapshots
                $table->foreignId('old_order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('new_order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('old_expense_type');
                $table->string('new_expense_type');
                $table->string('old_campaign_name')->nullable();
                $table->string('new_campaign_name')->nullable();
                $table->text('old_description')->nullable();
                $table->text('new_description')->nullable();

                // Immutable creation timestamp
                $table->timestamp('created_at')->useCurrent();

                // Compound Indexes for fast tenant and expense history lookups
                $table->index(['outlet_id', 'created_at'], 'idx_expense_histories_outlet_created');
                $table->index(['business_expense_id', 'created_at'], 'idx_expense_histories_expense_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_expense_histories');
    }
};
