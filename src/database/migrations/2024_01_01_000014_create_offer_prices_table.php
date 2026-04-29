<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_prices', function (Blueprint $table) {
            $table->id();
            $table->uuid('offer_id')->index('offer_prices_offer_id_idx');
            $table->uuid('price_type_id')->index('offer_prices_price_type_id_idx');

            // Price - основная цена
            $table->decimal('price_amount', 15, 2);
            $table->char('price_currency', 3)->default('RUB');

            // Discounted price - цена со скидкой
            $table->decimal('price_with_discount_amount', 15, 2)->nullable();
            $table->char('price_with_discount_currency', 3)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();

            // Quantity-based pricing - минимальное количество для цены
            $table->decimal('min_quantity', 10, 3)->nullable()->index('offer_prices_min_quantity_idx');

            // Unit info - единица измерения цены
            $table->char('unit_code', 10)->nullable();
            $table->string('unit_short_name')->nullable();
            $table->string('unit_full_name')->nullable();
            $table->string('unit_international')->nullable();

            // Validity period - период действия цены
            $table->timestamp('valid_from')->nullable()->index('offer_prices_valid_from_idx');
            $table->timestamp('valid_to')->nullable()->index('offer_prices_valid_to_idx');

            $table->timestamps();

            $table->foreign('offer_id')
                ->references('id')
                ->on('offers')
                ->cascadeOnDelete();

            $table->foreign('price_type_id')
                ->references('id')
                ->on('price_types')
                ->cascadeOnDelete();

            // Уникальный индекс: одно предложение на offer + price_type + quantity
            $table->unique(
                ['offer_id', 'price_type_id', 'min_quantity'],
                'unique_offer_price_quantity'
            );

            // Индекс для быстрого поиска действующих цен
            $table->index(['valid_from', 'valid_to'], 'offer_prices_valid_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_prices');
    }
};
