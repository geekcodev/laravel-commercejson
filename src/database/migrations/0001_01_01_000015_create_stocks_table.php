<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('offer_id')->index('stocks_offer_id_idx');
            $table->uuid('warehouse_id')->index('stocks_warehouse_id_idx');
            $table->decimal('quantity', 10, 3)->default(0)->index('stocks_quantity_idx');
            $table->decimal('quantity_reserved', 10, 3)->nullable()->default(0);

            $table->timestamps();

            $table->foreign('offer_id')
                ->references('id')
                ->on('offers')
                ->cascadeOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->cascadeOnDelete();

            // Одно значение остатка на offer + warehouse
            $table->unique(['offer_id', 'warehouse_id'], 'unique_offer_warehouse_stock');

            // Композитный индекс для быстрого получения остатков по складу
            $table->index(['warehouse_id', 'quantity'], 'stocks_warehouse_quantity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
