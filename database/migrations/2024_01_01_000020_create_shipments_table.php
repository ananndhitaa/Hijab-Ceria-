<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('tracking_no')->nullable();
            $table->string('courier')->nullable();
            $table->string('courier_service')->nullable();
            $table->decimal('shipping_cost', 18, 2)->default(0);
            $table->text('recipient_address')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('status')->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index('tracking_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
