<?php

use GeekCo\CommerceJson\Enums\CurrencyEnum;
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

    // Валюта по умолчанию (ISO 4217) — используется при создании заказов без указания base_currency
    'default_currency' => env('COMMERCEJSON_DEFAULT_CURRENCY', CurrencyEnum::RUB->value),

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
    | API Routes Settings
    |--------------------------------------------------------------------------
    |
    | Настройки маршрутов API для входящих запросов (от 1С к сайту)
    |
    */
    'api_routes' => [
        // Префикс для всех маршрутов API пакета
        'prefix' => env('COMMERCEJSON_API_PREFIX', 'api/commercejson'),

        // Middleware для защиты маршрутов API (кроме handshake)
        'middleware' => ['api', 'auth:sanctum'], // Пример: ['api', 'auth:sanctum'] или ['api', 'commercejson.auth']

        // Rate limiting: max попыток / минута (null = отключено)
        'rate_limit' => env('COMMERCEJSON_RATE_LIMIT', 60),
        'rate_limit_decay' => env('COMMERCEJSON_RATE_LIMIT_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency Settings
    |--------------------------------------------------------------------------
    |
    | Настройки идемпотентности запросов через X-Idempotency-Key
    |
    */
    'idempotency' => [
        // TTL кеша идемпотентных ключей (секунды)
        'ttl' => (int) env('COMMERCEJSON_IDEMPOTENCY_TTL', 3600),

        // Драйвер кеша (null = использовать драйвер по умолчанию)
        'store' => env('COMMERCEJSON_IDEMPOTENCY_STORE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Endpoints
    |--------------------------------------------------------------------------
    |
    | Пути к эндпоинтам внешней системы 1С, используемые для исходящих запросов.
    |
    */
    'external_api_endpoints' => [
        'handshake' => '/handshake',
        'classifier' => '/catalog/classifier',
        'products' => '/catalog/products',
        'offers' => '/offers',
        'price_types' => '/offers/price-types',
        'orders' => '/orders',
        'orders_bulk' => '/orders/bulk',
        'counterparties' => '/counterparties',
        'warehouses' => '/warehouses',
    ],

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
    | API Request Logging
    |--------------------------------------------------------------------------
    |
    | Настройки логирования входящих API-запросов (от 1С/ERP к сайту).
    | Логи пишутся в канал 'commercejson-api' (должен быть определён
    | в config/logging.php приложения).
    |
    | Пример канала для config/logging.php:
    | 'commercejson-api' => [
    |     'driver' => 'daily',
    |     'path' => storage_path('logs/commercejson-api.log'),
    |     'level' => env('LOG_LEVEL', 'debug'),
    |     'days' => 14,
    | ],
    |
    */

    'api_logging' => [
        'enabled' => env('COMMERCEJSON_API_LOGGING', true),

        // Канал логирования: 'commercejson-api', 'commercejson', 'stack', etc.
        'channel' => env('COMMERCEJSON_API_LOG_CHANNEL', 'commercejson-api'),

        // Запасной канал, если основной не определён
        'fallback_channel' => 'commercejson',

        // Логировать тело запроса
        'log_request_body' => env('COMMERCEJSON_API_LOG_REQUEST_BODY', true),

        // Логировать тело ответа (осторожно — большие ответы)
        'log_response_body' => env('COMMERCEJSON_API_LOG_RESPONSE_BODY', false),

        // Максимальная длина тела не-JSON ответа (символов)
        'log_response_body_max_length' => env('COMMERCEJSON_API_LOG_RESPONSE_BODY_MAX_LENGTH', 1000),

        // Пути, исключённые из логирования (префиксы URL)
        'exclude_paths' => ['handshake'],
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

    /*
    |--------------------------------------------------------------------------
    | Seeding (testing & load generation)
    |--------------------------------------------------------------------------
    |
    | Настройки генерации тестовых данных сидерами.
    | Важно: env() используется только здесь (в конфиге), а в коде сидеров
    | используются значения через config('commercejson.seeding.*').
    |
    */

    'seeding' => [
        // Профиль сидинга: 'default' | 'load'
        'profile' => env('COMMERCEJSON_SEED_PROFILE', 'default'),

        // Load profile parameters
        'load' => [
            'seed' => (int) env('COMMERCEJSON_SEED_SEED', 1234),
            'run_key' => env('COMMERCEJSON_SEED_RUN_KEY'), // optional
            'chunk' => (int) env('COMMERCEJSON_SEED_CHUNK', 1000),

            'extra_categories' => (int) env('COMMERCEJSON_SEED_EXTRA_CATEGORIES', 5000),
            'extra_counterparties' => (int) env('COMMERCEJSON_SEED_EXTRA_COUNTERPARTIES', 200),

            'products' => (int) env('COMMERCEJSON_SEED_PRODUCTS', 20000),

            'variants_ratio' => (float) env('COMMERCEJSON_SEED_VARIANTS_RATIO', 0.35),
            'variants_min' => (int) env('COMMERCEJSON_SEED_VARIANTS_MIN', 2),
            'variants_max' => (int) env('COMMERCEJSON_SEED_VARIANTS_MAX', 5),

            'price_tiers' => (int) env('COMMERCEJSON_SEED_PRICE_TIERS', 2),
            'stocks_per_offer' => (int) env('COMMERCEJSON_SEED_STOCKS_PER_OFFER', -1), // -1 = все склады

            'properties' => (int) env('COMMERCEJSON_SEED_PROPERTIES', 40),
            'product_properties' => (int) env('COMMERCEJSON_SEED_PRODUCT_PROPERTIES', 6),
            'variant_properties' => (int) env('COMMERCEJSON_SEED_VARIANT_PROPERTIES', 3),

            // reserved for future extensions
            'orders' => (int) env('COMMERCEJSON_SEED_ORDERS', 0),
        ],
    ],
];
