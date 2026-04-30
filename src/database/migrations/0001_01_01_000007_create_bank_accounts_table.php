<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('counterparty_id')->index('bank_accounts_counterparty_id_idx');
            $table->string('bank_name')->nullable();
            $table->char('bik', 9);
            $table->char('account', 20);
            $table->char('corr_account', 20)->nullable();
            $table->char('swift', 11)->nullable();
            $table->boolean('is_default')->nullable()->default(false)->index('bank_accounts_is_default_idx');

            $table->timestamps();

            $table->foreign('counterparty_id')
                ->references('id')
                ->on('counterparties')
                ->cascadeOnDelete();

            // Уникальный индекс на счет + контрагент
            $table->unique(['counterparty_id', 'account'], 'bank_accounts_counterparty_account_unique');

            // Индекс для поиска по БИК
            $table->index('bik', 'bank_accounts_bik_idx');

            // Индекс для поиска дефолтного счета контрагента
            $table->index(['counterparty_id', 'is_default'], 'bank_accounts_counterparty_default_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
