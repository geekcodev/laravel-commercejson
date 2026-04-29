<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_values', function (Blueprint $table) {
            $table->id();
            $table->uuid('property_id')->index('property_values_property_id_idx');
            $table->uuid('product_id')->nullable()->index('property_values_product_id_idx');
            $table->uuid('variant_id')->nullable()->index('property_values_variant_id_idx');

            // Разные типы значений свойств
            $table->string('value_string')->nullable()->index('property_values_value_string_idx');
            $table->decimal('value_number', 15, 4)->nullable()->index('property_values_value_number_idx');
            $table->boolean('value_boolean')->nullable()->index('property_values_value_boolean_idx');
            $table->json('value_json')->nullable(); // для multiselect

            $table->timestamps();

            $table->foreign('property_id')
                ->references('id')
                ->on('property_definitions')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();

            // Композитные индексы для быстрого поиска
            $table->index(['product_id', 'property_id'], 'property_values_product_property_idx');
            $table->index(['variant_id', 'property_id'], 'property_values_variant_property_idx');

            // Уникальные ограничения - одно значение свойства на товар/вариант
            // CHECK: только одно из product_id или variant_id должно быть указано
            $table->unique(['product_id', 'property_id'], 'unique_product_property');
            $table->unique(['variant_id', 'property_id'], 'unique_variant_property');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_values');
    }
};
