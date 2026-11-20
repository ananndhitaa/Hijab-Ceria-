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
        if (! Schema::hasTable('tenant_registrations')) {
            Schema::create('tenant_registrations', function (Blueprint $table) {
                $table->id();
                $table->string('company_name', 150);
                $table->string('owner_name', 150);
                $table->string('email', 150)->index();
                $table->string('phone_wa', 50);
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->string('password'); // Hashed applicant password
                $table->string('status', 30)->default('PENDING')->index(); // PENDING, NEEDS_REVISION, APPROVED, REJECTED
                $table->text('revision_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_registration_histories')) {
            Schema::create('tenant_registration_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_registration_id')->constrained('tenant_registrations')->cascadeOnDelete();
                $table->string('action', 50); // SUBMIT, REQUEST_REVISION, RESUBMIT, APPROVE, REJECT
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->text('notes')->nullable();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('action');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_registration_histories');
        Schema::dropIfExists('tenant_registrations');
    }
};
