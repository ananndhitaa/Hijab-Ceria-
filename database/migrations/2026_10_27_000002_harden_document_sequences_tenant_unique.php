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
        if (Schema::hasTable('document_sequences')) {
            Schema::table('document_sequences', function (Blueprint $table) {
                if (! Schema::hasColumn('document_sequences', 'tenant_seq_scope')) {
                    $table->unsignedBigInteger('tenant_seq_scope')
                        ->storedAs('COALESCE(outlet_id, 0)')
                        ->after('outlet_id');
                }

                $table->dropUnique('document_sequences_prefix_period_unique');
                $table->unique(['tenant_seq_scope', 'prefix', 'period'], 'document_sequences_tenant_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('document_sequences')) {
            Schema::table('document_sequences', function (Blueprint $table) {
                $table->dropUnique('document_sequences_tenant_unique');

                if (Schema::hasColumn('document_sequences', 'tenant_seq_scope')) {
                    $table->dropColumn('tenant_seq_scope');
                }

                $table->unique(['prefix', 'period'], 'document_sequences_prefix_period_unique');
            });
        }
    }
};
