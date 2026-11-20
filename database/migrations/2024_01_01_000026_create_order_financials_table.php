<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->decimal('revenue_amount', 18, 2)->default(0);
            $table->decimal('hpp_amount', 18, 2)->default(0);
            $table->decimal('hpp_per_good_pcs', 18, 4)->nullable();
            $table->decimal('gross_profit', 18, 2)->default(0);
            $table->decimal('gross_margin', 12, 4)->default(0); // percentage
            $table->decimal('net_profit', 18, 2)->default(0);
            $table->decimal('net_margin', 12, 4)->default(0); // percentage
            $table->decimal('roi_percent', 12, 4)->nullable();
            $table->decimal('marketing_spend', 18, 2)->default(0);
            $table->decimal('mer_value', 12, 4)->nullable(); // Marketing Efficiency Ratio
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_financials');
    }
};
