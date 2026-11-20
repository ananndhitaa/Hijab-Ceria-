<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Target master data tables receiving outlet_id.
     *
     * @var array<int, string>
     */
    protected array $targetTables = [
        'products',
        'product_variants',
        'materials',
        'suppliers',
        'customers',
        'bom_templates',
        'document_sequences',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->targetTables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'outlet_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $column = $table->foreignId('outlet_id')
                        ->nullable()
                        ->constrained('outlets')
                        ->restrictOnDelete();

                    // Optional column placement for cleaner table schema inspection
                    if ($tableName === 'product_variants') {
                        $column->after('product_id');
                    } else {
                        $column->after('id');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->targetTables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'outlet_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                });
            }
        }
    }
};
