<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index('order_items_order_id_idx');
            $table->uuid('product_id')->index('order_items_product_id_idx');
            $table->uuid('variant_id')->nullable()->index('order_items_variant_id_idx');

            // Snapshot of product data - фиксируем данные на момент заказа
            $table->string('product_name');
            $table->string('product_code')->nullable();

            // Quantity and unit
            $table->decimal('quantity', 10, 3);
            $table->char('unit_code', 10)->nullable();
            $table->string('unit_short_name')->nullable();
            $table->string('unit_full_name')->nullable();
            $table->string('unit_international')->nullable();

            // Prices
            $table->decimal('price_amount', 15, 2);
            $table->char('price_currency', 3);
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->char('discount_currency', 3)->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->char('total_currency', 3);

            // Customs info
            $table->char('country_of_origin', 2)->nullable();
            $table->string('customs_declaration_number', 50)->nullable();

            // Tax info - упрощенные поля, детали в order_item_taxes
            $table->decimal('tax_rate', 5, 2)->nullable();

            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->restrictOnDelete();

            // Композитные индексы для производительных запросов
            $table->index(['order_id', 'product_id', 'variant_id'], 'order_items_order_product_variant_idx');
            $table->index(['product_id', 'created_at'], 'order_items_product_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
