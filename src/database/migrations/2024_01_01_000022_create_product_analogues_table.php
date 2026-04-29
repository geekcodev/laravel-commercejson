<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_analogues', function (Blueprint $table) {
            $table->uuid('product_id');
            $table->uuid('analogue_id');

            $table->primary(['product_id', 'analogue_id'], 'product_analogues_primary');
            $table->index('analogue_id', 'product_analogues_analogue_id_idx');

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('analogue_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_analogues');
    }
};
