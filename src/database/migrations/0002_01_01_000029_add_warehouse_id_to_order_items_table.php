<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->uuid('warehouse_id')->nullable();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->nullOnDelete();

            $table->index('warehouse_id', 'order_items_warehouse_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex('order_items_warehouse_id_idx');
            $table->dropColumn('warehouse_id');
        });
    }
};
