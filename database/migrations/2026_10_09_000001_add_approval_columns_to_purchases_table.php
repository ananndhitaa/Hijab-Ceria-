<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('supplier_id')->constrained('production_batches')->nullOnDelete();
            $table->string('approval_status')->default('NOT_SUBMITTED')->after('status');
            $table->timestamp('submitted_at')->nullable()->after('approval_status');
            $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('submitted_by');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable()->after('approved_by');

            $table->index('approval_status');
            $table->index('batch_id');
        });

        // Historical safety: Preserve legacy ORDERED and RECEIVED POs as APPROVED
        DB::table('purchases')
            ->whereIn('status', ['ORDERED', 'RECEIVED'])
            ->update([
                'approval_status' => 'APPROVED',
                'approved_at'     => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'batch_id',
                'approval_status',
                'submitted_at',
                'submitted_by',
                'approved_at',
                'approved_by',
                'review_note',
            ]);
        });
    }
};
