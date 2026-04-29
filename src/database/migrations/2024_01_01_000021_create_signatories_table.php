<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            $table->string('signatory_type');
            $table->uuid('signatory_id');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('position')->nullable();
            $table->string('basis')->nullable(); // Основание полномочий

            $table->timestamps();

            // Полиморфный индекс
            $table->index(['signatory_type', 'signatory_id'], 'signatories_signatory_idx');

            // Индекс для поиска по типу сущности
            $table->index('signatory_type', 'signatories_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatories');
    }
};
