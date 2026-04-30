<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_linked_documents', function (Blueprint $table) {
            $table->uuid('order_id');
            $table->uuid('linked_order_id');
            $table->string('external_id')->nullable();
            $table->string('type')->index('order_linked_documents_type_idx'); // DocumentType enum

            $table->primary(['order_id', 'linked_order_id'], 'order_linked_documents_primary');
            $table->index('linked_order_id', 'order_linked_documents_linked_order_id_idx');

            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('linked_order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            // Композитный индекс для поиска связанных документов по типу
            $table->index(['order_id', 'type'], 'order_linked_documents_order_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_linked_documents');
    }
};
