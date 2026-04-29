<?php

use GeekCo\CommerceJson\Events\OrderImported;
use GeekCo\CommerceJson\Events\ProductsImported;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use GeekCo\CommerceJson\Models\PriceType;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\ProductVariant;
use GeekCo\CommerceJson\Models\Warehouse;

return [
    /*
    |--------------------------------------------------------------------------
    | CommerceJSON API Settings
    |--------------------------------------------------------------------------
    |
    | Настройки подключения к CommerceJSON API
    |
    */

    'base_url' => env('COMMERCEJSON_BASE_URL', 'https://api.example.com/v1'),

    'auth' => [
        'type' => env('COMMERCEJSON_AUTH_TYPE', 'bearer'), // bearer, basic, session
        'token' => env('COMMERCEJSON_AUTH_TOKEN'),
        'login' => env('COMMERCEJSON_LOGIN'),
        'password' => env('COMMERCEJSON_PASSWORD'),
    ],

    // Таймаут HTTP запросов (секунды)
    'timeout' => env('COMMERCEJSON_TIMEOUT', 30),

    // Количество попыток повторного запроса при ошибках
    'retry_attempts' => env('COMMERCEJSON_RETRY_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Exchange Settings
    |--------------------------------------------------------------------------
    |
    | Настройки обмена данными с CommerceJSON API
    |
    */

    'exchange' => [
        // Режим обмена: auto (автоматический), manual (ручной)
        'mode' => env('COMMERCEJSON_EXCHANGE_MODE', 'auto'),

        // Размер пакетов для импорта
        'batch_size' => [
            'classifier' => 50,
            'products' => 100,
            'offers' => 200,
            'orders' => 50,
        ],

        // Настройки очереди
        'queue' => [
            'enabled' => env('COMMERCEJSON_QUEUE_ENABLED', true),
            'connection' => env('COMMERCEJSON_QUEUE_CONNECTION', 'sync'),
            'import_queue' => 'commercejson-import',
            'export_queue' => 'commercejson-export',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Настройки синхронизации данных
    |
    */

    'sync' => [
        // Расписание автоматической синхронизации (cron format)
        'schedule' => env('COMMERCEJSON_SYNC_SCHEDULE', '0 * * * *'),

        // Инкрементальная синхронизация
        'incremental' => [
            'enabled' => env('COMMERCEJSON_INCREMENTAL_SYNC', true),
            // Хранить историю синхронизаций (дней)
            'retention_days' => 30,
        ],

        // Полная синхронизация
        'full' => [
            // Автоматическая полная синхронизация (раз в неделю по умолчанию)
            'schedule' => env('COMMERCEJSON_FULL_SYNC_SCHEDULE', '0 2 * * 0'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Debug
    |--------------------------------------------------------------------------
    |
    | Настройки логирования и отладки
    |
    */

    'logging' => [
        'enabled' => env('COMMERCEJSON_LOGGING', true),
        'channel' => env('COMMERCEJSON_LOG_CHANNEL', 'stack'),
        'log_requests' => env('COMMERCEJSON_LOG_REQUESTS', false),
        'log_responses' => env('COMMERCEJSON_LOG_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events & Notifications
    |--------------------------------------------------------------------------
    |
    | Настройки событий и уведомлений
    |
    */

    'events' => [
        // Диспетчеризация событий Laravel
        'dispatch' => env('COMMERCEJSON_DISPATCH_EVENTS', true),

        // Слушатели событий
        'listen' => [
            ProductsImported::class => [
                // \App\Listeners\UpdateProductSearchIndex::class,
            ],
            OrderImported::class => [
                // \App\Listeners\ProcessOrderPayment::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Mappings
    |--------------------------------------------------------------------------
    |
    | Маппинг моделей для кастомизации
    |
    */

    'models' => [
        'product' => Product::class,
        'product_variant' => ProductVariant::class,
        'category' => Category::class,
        'offer' => Offer::class,
        'order' => Order::class,
        'order_item' => OrderItem::class,
        'counterparty' => Counterparty::class,
        'warehouse' => Warehouse::class,
        'price_type' => PriceType::class,
    ],
];
