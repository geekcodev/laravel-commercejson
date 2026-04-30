<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counterparties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->string('type')->index('counterparties_type_idx'); // legal_entity, individual, individual_entrepreneur
            $table->string('name')->index('counterparties_name_idx');
            $table->string('short_name')->nullable();

            // Tax IDs - индексы для поиска по ИНН/ОГРН
            $table->string('inn')->nullable()->unique();
            $table->char('kpp', 9)->nullable();
            $table->string('ogrn')->nullable()->unique();
            $table->string('okved')->nullable();
            $table->string('okpo')->nullable();
            $table->string('okopf')->nullable();
            $table->string('okfs')->nullable();
            $table->date('registration_date')->nullable();

            // Legal address - денормализованные поля для производительности
            $table->char('legal_address_country', 2)->nullable();
            $table->string('legal_address_region')->nullable();
            $table->string('legal_address_district')->nullable();
            $table->string('legal_address_city')->nullable()->index('counterparties_legal_city_idx');
            $table->string('legal_address_street')->nullable();
            $table->string('legal_address_house')->nullable();
            $table->string('legal_address_building')->nullable();
            $table->string('legal_address_apartment')->nullable();
            $table->string('legal_address_postal_code')->nullable();
            $table->text('legal_address_full')->nullable();

            // Actual address
            $table->char('actual_address_country', 2)->nullable();
            $table->string('actual_address_region')->nullable();
            $table->string('actual_address_district')->nullable();
            $table->string('actual_address_city')->nullable()->index('counterparties_actual_city_idx');
            $table->string('actual_address_street')->nullable();
            $table->string('actual_address_house')->nullable();
            $table->string('actual_address_building')->nullable();
            $table->string('actual_address_apartment')->nullable();
            $table->string('actual_address_postal_code')->nullable();
            $table->text('actual_address_full')->nullable();

            // Credit info
            $table->uuid('price_type_id')->nullable()->index('counterparties_price_type_id_idx');
            $table->decimal('credit_limit_amount', 15, 2)->nullable();
            $table->char('credit_limit_currency', 3)->nullable();

            $table->boolean('is_active')->nullable()->default(true)->index('counterparties_is_active_idx');

            $table->softDeletes()->index('counterparties_deleted_at_idx');
            $table->timestamps();

            $table->foreign('price_type_id')
                ->references('id')
                ->on('price_types')
                ->nullOnDelete();

            // Композитные индексы для частых запросов
            $table->index(['type', 'is_active'], 'counterparties_type_active_idx');
            $table->index(['inn', 'type'], 'counterparties_inn_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterparties');
    }
};
