<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->uuid('product_id')->index('product_images_product_id_idx');
            $table->string('url');
            $table->unsignedInteger('sort')->nullable()->default(0)->index('product_images_sort_idx');
            $table->string('alt')->nullable();
            $table->boolean('is_main')->nullable()->default(false)->index('product_images_is_main_idx');

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            // Композитные индексы для быстрого получения изображений товара
            $table->index(['product_id', 'is_main'], 'product_images_product_main_idx');
            $table->index(['product_id', 'sort'], 'product_images_product_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
