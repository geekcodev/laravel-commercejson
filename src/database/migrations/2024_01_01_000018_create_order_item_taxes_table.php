<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_item_id')->index('order_item_taxes_order_item_id_idx');
            $table->string('type'); // НДС, Акциз, УСН и т.д.
            $table->decimal('rate', 5, 2);
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->boolean('is_included')->nullable()->default(true);

            $table->timestamps();

            $table->foreign('order_item_id')
                ->references('id')
                ->on('order_items')
                ->cascadeOnDelete();

            // Уникальный индекс: один налог каждого типа на позицию
            $table->unique(['order_item_id', 'type'], 'order_item_taxes_item_type_unique');

            // Индекс для быстрого поиска по типу налога
            $table->index(['type', 'is_included'], 'order_item_taxes_type_included_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_taxes');
    }
};
