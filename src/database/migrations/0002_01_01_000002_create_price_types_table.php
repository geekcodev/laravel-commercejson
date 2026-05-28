<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('currency', 3)->nullable()->default(CurrencyEnum::RUB->value);
            $table->text('description')->nullable();
            $table->boolean('is_default')->nullable()->default(false)->index('price_types_is_default_idx');

            $table->timestamps();

            // Индекс для быстрого поиска типа цены по умолчанию
            $table->index(['is_default', 'currency'], 'price_types_default_currency_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_types');
    }
};
