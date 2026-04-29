<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('counterparty_id')->index('contacts_counterparty_id_idx');
            $table->string('type')->index('contacts_type_idx'); // phone, mobile, email, fax, web, etc.
            $table->string('value')->index('contacts_value_idx');
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->foreign('counterparty_id')
                ->references('id')
                ->on('counterparties')
                ->cascadeOnDelete();

            // Композитный индекс для быстрого поиска контактов контрагента по типу
            $table->index(['counterparty_id', 'type'], 'contacts_counterparty_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
