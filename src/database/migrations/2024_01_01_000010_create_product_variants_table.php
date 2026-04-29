<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->index('product_variants_product_id_idx');
            $table->string('external_id')->nullable()->unique();
            $table->string('name');
            $table->string('code')->nullable()->index('product_variants_code_idx');
            $table->char('barcode', 14)->nullable()->unique();
            $table->boolean('is_active')->nullable()->default(true)->index('product_variants_is_active_idx');

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            // Композитные индексы для производительных запросов
            $table->index(['product_id', 'is_active'], 'product_variants_product_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
