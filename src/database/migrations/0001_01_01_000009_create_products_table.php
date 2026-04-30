<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->string('name')->index('products_name_idx');
            $table->string('code')->nullable()->index('products_code_idx');
            $table->char('barcode', 14)->nullable()->unique();
            $table->uuid('category_id')->index('products_category_id_idx');
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();

            // Dimensions and weight
            $table->decimal('weight', 10, 3)->nullable(); // kg
            $table->decimal('dimensions_length', 10, 2)->nullable(); // cm
            $table->decimal('dimensions_width', 10, 2)->nullable();
            $table->decimal('dimensions_height', 10, 2)->nullable();

            // Manufacturer info - с индексами для джойнов
            $table->char('manufacturer_country', 2)->nullable();
            $table->string('manufacturer_brand')->nullable();
            $table->uuid('manufacturer_brand_owner_id')->nullable()->index('products_manufacturer_brand_owner_id_idx');
            $table->uuid('manufacturer_id')->nullable()->index('products_manufacturer_id_idx');

            // Unit info
            $table->char('unit_code', 10)->nullable();
            $table->string('unit_short_name')->nullable();
            $table->string('unit_full_name')->nullable();
            $table->string('unit_international')->nullable();

            $table->boolean('is_active')->nullable()->default(true)->index('products_is_active_idx');

            // SEO fields
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 1000)->nullable();
            $table->text('seo_keywords')->nullable();

            $table->softDeletes()->index('products_deleted_at_idx');
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();

            $table->foreign('manufacturer_brand_owner_id')
                ->references('id')
                ->on('counterparties')
                ->nullOnDelete();

            $table->foreign('manufacturer_id')
                ->references('id')
                ->on('counterparties')
                ->nullOnDelete();

            // Композитные индексы для производительных запросов
            $table->index(['category_id', 'is_active'], 'products_category_active_idx');
            $table->index(['category_id', 'is_active', 'updated_at'], 'products_category_active_updated_idx');
            $table->index(['manufacturer_id', 'is_active'], 'products_manufacturer_active_idx');

            // Fulltext индексы для поиска (только MySQL 5.7+)
            // Закомментировано для совместимости с SQLite в тестах
            // $table->fullText(['name', 'short_description'], 'products_name_description_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
