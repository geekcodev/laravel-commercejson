<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('counterparty_id')->index('representatives_counterparty_id_idx');
            $table->string('name');
            $table->string('relation'); // "Контактное лицо", "Филиал"
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('position')->nullable();

            $table->timestamps();

            $table->foreign('counterparty_id')
                ->references('id')
                ->on('counterparties')
                ->cascadeOnDelete();

            // Индекс для поиска представителей контрагента
            $table->index(['counterparty_id', 'relation'], 'representatives_counterparty_relation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representatives');
    }
};
