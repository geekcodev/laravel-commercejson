<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->index('offers_product_id_idx');
            $table->uuid('variant_id')->nullable()->index('offers_variant_id_idx');

            $table->softDeletes()->index('offers_deleted_at_idx');
            $table->timestamp('updated_at')->nullable()->index('offers_updated_at_idx');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();

            // Одно предложение на товар или вариант
            $table->unique(['product_id', 'variant_id'], 'offers_product_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
