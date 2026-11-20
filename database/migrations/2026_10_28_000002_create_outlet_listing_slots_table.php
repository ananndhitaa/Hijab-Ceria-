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
        if (! Schema::hasTable('outlet_listing_slots')) {
            Schema::create('outlet_listing_slots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outlet_id')->unique()->constrained('outlets')->cascadeOnDelete();
                $table->unsignedInteger('purchased_slots')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_listing_slots');
    }
};
