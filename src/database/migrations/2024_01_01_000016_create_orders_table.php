<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->string('external_id')->nullable()->unique();
            $table->string('status')->index('orders_status_idx');
            $table->string('document_type')->default('order')->index('orders_document_type_idx');
            $table->string('role')->nullable()->index('orders_role_idx');
            $table->char('base_currency', 3)->nullable()->default('RUB');
            $table->decimal('exchange_rate', 10, 4)->nullable()->default(1);
            $table->string('payment_terms')->nullable();
            $table->uuid('counterparty_id')->nullable()->index('orders_counterparty_id_idx');
            $table->uuid('warehouse_id')->nullable()->index('orders_warehouse_id_idx');
            $table->text('comment')->nullable();

            // Customer data - денормализованные поля для производительности
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable()->index('orders_customer_phone_idx');
            $table->string('customer_email')->nullable()->index('orders_customer_email_idx');
            $table->uuid('customer_counterparty_id')->nullable()->index('orders_customer_counterparty_id_idx');

            // Delivery data
            $table->string('delivery_type')->nullable()->index('orders_delivery_type_idx');
            $table->char('delivery_address_country', 2)->nullable();
            $table->string('delivery_address_region')->nullable();
            $table->string('delivery_address_district')->nullable();
            $table->string('delivery_address_city')->nullable()->index('orders_delivery_city_idx');
            $table->string('delivery_address_street')->nullable();
            $table->string('delivery_address_house')->nullable();
            $table->string('delivery_address_building')->nullable();
            $table->string('delivery_address_apartment')->nullable();
            $table->string('delivery_address_postal_code')->nullable();
            $table->text('delivery_address_full')->nullable();
            $table->string('delivery_method_id')->nullable();
            $table->string('delivery_method_name')->nullable();
            $table->decimal('delivery_cost_amount', 15, 2)->nullable();
            $table->char('delivery_cost_currency', 3)->nullable();
            $table->string('delivery_tracking_number')->nullable()->index('orders_delivery_tracking_idx');
            $table->timestamp('delivery_shipped_at')->nullable()->index('orders_delivery_shipped_at_idx');
            $table->date('delivery_estimated_date')->nullable()->index('orders_delivery_estimated_date_idx');

            // Payment data
            $table->string('payment_type')->nullable()->index('orders_payment_type_idx');
            $table->string('payment_status')->nullable()->index('orders_payment_status_idx');
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->char('payment_currency', 3)->nullable();
            $table->timestamp('payment_paid_at')->nullable()->index('orders_payment_paid_at_idx');
            $table->string('payment_transaction_id')->nullable()->index('orders_payment_transaction_id_idx');

            // Totals - денормализованные суммы для быстрых отчетов
            $table->decimal('totals_subtotal_amount', 15, 2)->nullable();
            $table->char('totals_subtotal_currency', 3)->nullable();
            $table->decimal('totals_discount_amount', 15, 2)->nullable();
            $table->char('totals_discount_currency', 3)->nullable();
            $table->decimal('totals_delivery_amount', 15, 2)->nullable();
            $table->char('totals_delivery_currency', 3)->nullable();
            $table->decimal('totals_tax_amount', 15, 2)->nullable();
            $table->char('totals_tax_currency', 3)->nullable();
            $table->decimal('totals_total_amount', 15, 2)->nullable();
            $table->char('totals_total_currency', 3)->nullable();

            $table->softDeletes()->index('orders_deleted_at_idx');
            $table->timestamps();

            $table->foreign('counterparty_id')
                ->references('id')
                ->on('counterparties')
                ->nullOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->nullOnDelete();

            // Композитные индексы для производительных запросов
            $table->index(['status', 'created_at'], 'orders_status_created_at_idx');
            $table->index(['status', 'document_type', 'created_at'], 'orders_status_document_created_at_idx');
            $table->index(['counterparty_id', 'status'], 'orders_counterparty_status_idx');
            $table->index(['counterparty_id', 'created_at'], 'orders_counterparty_created_at_idx');
            $table->index(['created_at', 'status'], 'orders_created_at_status_idx');
            $table->index(['updated_at', 'deleted_at'], 'orders_updated_at_deleted_at_idx');

            // Индекс для инкрементальной выгрузки
            $table->index(['updated_at', 'id'], 'orders_updated_at_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
