<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counterparties', function (Blueprint $table) {
            $table->decimal('credit_limit_remaining_amount', 15, 2)->nullable()->after('credit_limit_currency');
            $table->char('credit_limit_remaining_currency', 3)->nullable()->after('credit_limit_remaining_amount');
            $table->unsignedInteger('payment_deferral_days')->nullable()->after('credit_limit_remaining_currency');
            $table->decimal('outstanding_debt_amount', 15, 2)->nullable()->after('payment_deferral_days');
            $table->char('outstanding_debt_currency', 3)->nullable()->after('outstanding_debt_amount');
        });
    }

    public function down(): void
    {
        Schema::table('counterparties', function (Blueprint $table) {
            $table->dropColumn([
                'credit_limit_remaining_amount',
                'credit_limit_remaining_currency',
                'payment_deferral_days',
                'outstanding_debt_amount',
                'outstanding_debt_currency',
            ]);
        });
    }
};
