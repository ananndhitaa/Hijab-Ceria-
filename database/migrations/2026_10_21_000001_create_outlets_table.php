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
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('outlet_name', 150);
            $table->string('outlet_code', 50)->unique();
            $table->string('slug', 150)->nullable()->unique();
            $table->string('phone_wa', 30)->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->boolean('is_public_showcase_enabled')->default(true);
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
