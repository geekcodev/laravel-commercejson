<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->string('name');
            $table->string('code')->nullable()->unique();

            // Address fields - денормализованные для производительности
            $table->char('address_country', 2)->nullable();
            $table->string('address_region')->nullable();
            $table->string('address_district')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_house')->nullable();
            $table->string('address_building')->nullable();
            $table->string('address_apartment')->nullable();
            $table->string('address_postal_code')->nullable();
            $table->text('address_full')->nullable();

            $table->boolean('is_active')->nullable()->default(true)->index('warehouses_is_active_idx');
            $table->boolean('is_default')->nullable()->default(false)->index('warehouses_is_default_idx');

            $table->softDeletes()->index('warehouses_deleted_at_idx');
            $table->timestamps();

            // Композитные индексы для частых запросов
            $table->index(['is_active', 'is_default'], 'warehouses_active_default_idx');
            $table->index(['address_city', 'is_active'], 'warehouses_city_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
