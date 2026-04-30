<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // name может быть строкой или JSON-объектом локализации {ru: "...", en: "..."}
            $table->json('name');
            $table->string('code')->nullable()->unique();
            $table->string('type')->index('property_definitions_type_idx'); // string, number, boolean, enum, multiselect, color, datetime
            $table->string('unit')->nullable();
            $table->boolean('is_filterable')->nullable()->default(false)->index('property_definitions_is_filterable_idx');
            $table->boolean('is_required')->nullable()->default(false);
            $table->boolean('use_for_catalog')->nullable()->default(true)->index('property_definitions_use_for_catalog_idx');
            $table->boolean('use_for_offers')->nullable()->default(false);
            $table->boolean('use_for_documents')->nullable()->default(false);
            // enum_values: JSON массив объектов {id: uuid, value: string}
            $table->json('enum_values')->nullable();
            $table->boolean('applies_to_all')->nullable()->default(false)->index('property_definitions_applies_to_all_idx');
            // category_ids: JSON массив UUID
            $table->json('category_ids')->nullable();

            $table->timestamps();

            // Композитные индексы для фильтрации
            $table->index(['type', 'is_filterable'], 'property_definitions_type_filterable_idx');
            $table->index(['use_for_catalog', 'use_for_documents'], 'property_definitions_use_cases_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_definitions');
    }
};
