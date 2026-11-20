<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('first_contact_date')->nullable()->after('customer_name');
            $table->text('notes')->nullable()->after('address');
            $table->foreignId('handled_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();

            $table->index('first_contact_date');
            $table->index('handled_by_user_id');
        });

        // Safe historical backfill for existing customers
        DB::table('customers')->whereNull('first_contact_date')->update([
            'first_contact_date' => DB::raw('DATE(created_at)'),
        ]);

        // Safely attribute PIC only where verifiable historical evidence exists from order creation
        $customers = DB::table('customers')->whereNull('handled_by_user_id')->get();
        foreach ($customers as $c) {
            $firstOrderHistory = DB::table('orders')
                ->join('order_status_histories', 'orders.id', '=', 'order_status_histories.order_id')
                ->join('users', 'users.id', '=', 'order_status_histories.changed_by_user_id')
                ->where('orders.customer_id', $c->id)
                ->where('users.role', 'ADMIN')
                ->orderBy('orders.id', 'asc')
                ->orderBy('order_status_histories.id', 'asc')
                ->select('order_status_histories.changed_by_user_id')
                ->first();

            if ($firstOrderHistory) {
                DB::table('customers')->where('id', $c->id)->update([
                    'handled_by_user_id' => $firstOrderHistory->changed_by_user_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['handled_by_user_id']);
            $table->dropIndex(['handled_by_user_id']);
            $table->dropIndex(['first_contact_date']);
            $table->dropColumn(['first_contact_date', 'notes', 'handled_by_user_id']);
        });
    }
};
