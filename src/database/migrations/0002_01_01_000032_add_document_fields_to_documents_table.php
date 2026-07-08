<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('paid')->nullable();
            $table->date('document_date')->nullable();
            $table->decimal('document_amount_amount', 15, 2)->nullable();
            $table->char('document_amount_currency', 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['paid', 'document_date', 'document_amount_amount', 'document_amount_currency']);
        });
    }
};
