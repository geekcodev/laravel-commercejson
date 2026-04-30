<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_history_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index('status_history_order_id_idx');
            $table->string('status')->index('status_history_status_idx');
            // changed_at с точностью до миллисекунд для корректного порядка событий
            $table->timestamp('changed_at', 3)->index('status_history_changed_at_idx');
            $table->string('changed_by')->nullable();
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            // Композитные индексы для быстрого получения истории по заказу
            $table->index(['order_id', 'changed_at'], 'status_history_order_changed_at_idx');
            $table->index(['order_id', 'status', 'changed_at'], 'status_history_order_status_changed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_history_entries');
    }
};
