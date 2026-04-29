<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('attributable_type');
            $table->uuid('attributable_id');
            $table->string('key', 100);

            // Разные типы значений
            $table->string('value_string')->nullable()->index('custom_attributes_value_string_idx');
            $table->decimal('value_number', 15, 4)->nullable()->index('custom_attributes_value_number_idx');
            $table->boolean('value_boolean')->nullable()->index('custom_attributes_value_boolean_idx');
            $table->json('value_json')->nullable();

            $table->softDeletes()->index('custom_attributes_deleted_at_idx');
            $table->timestamps();

            // Полиморфный индекс
            $table->index(['attributable_type', 'attributable_id'], 'custom_attributes_attributable_idx');
            $table->index('key', 'custom_attributes_key_idx');

            // Уникальный индекс: один атрибут с данным ключом на сущность
            $table->unique(
                ['attributable_type', 'attributable_id', 'key'],
                'unique_custom_attribute'
            );

            // Индекс для быстрого поиска по типу сущности
            $table->index('attributable_type', 'custom_attributes_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_attributes');
    }
};
