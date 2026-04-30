<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_components', function (Blueprint $table) {
            $table->uuid('product_id');
            $table->uuid('component_id');
            $table->decimal('quantity', 10, 3);

            $table->primary(['product_id', 'component_id'], 'product_components_primary');
            $table->index('component_id', 'product_components_component_id_idx');

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('component_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            // Индекс для поиска товаров, в которых данный товар является комплектующим
            $table->index(['component_id', 'product_id'], 'product_components_component_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};
