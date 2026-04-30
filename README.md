# Laravel CommerceJSON

[![Latest Version on Packagist](https://img.shields.io/packagist/v/geekcodev/laravel-commercejson.svg?style=flat-square)](https://packagist.org/packages/geekcodev/laravel-commercejson)
[![Total Downloads](https://img.shields.io/packagist/dt/geekcodev/laravel-commercejson.svg?style=flat-square)](https://packagist.org/packages/geekcodev/laravel-commercejson)
[![Code Coverage](https://img.shields.io/codecov/c/github/geekcodev/laravel-commercejson/main?style=flat-square)](https://codecov.io/gh/geekcodev/laravel-commercejson)
![GitHub Actions (main)](https://img.shields.io/github/actions/workflow/status/geekcodev/laravel-commercejson/run-tests.yml?branch=main&label=tests&style=flat-square)
![GitHub Actions (main)](https://img.shields.io/github/actions/workflow/status/geekcodev/laravel-commercejson/phpstan.yml?branch=main&label=phpstan&style=flat-square)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Пакет для интеграции с CommerceJSON API v1.0.8 в Laravel 12+. Предназначен для обмена данными с системами 1С и другими ERP-системами, поддерживающими стандарт CommerceJSON.

## Оглавление

- [Возможности](#возможности)
- [Установка](#установка)
- [Использование](#использование)
  - [Быстрый старт](#быстрый-старт)
  - [Использование сервисов](#использование-сервисов)
  - [Console-команды](#console-команды)
- [Архитектура](#архитектура)
  - [Структура пакета](#структура-пакета)
  - [Таблицы базы данных](#таблицы-базы-данных)
- [Тестирование](#тестирование)
  - [Покрытие кода](#покрытие-кода)
- [Документация](#документация)
  - [Доступные методы](#доступные-методы)
  - [События](#события)
- [Конфигурация](#конфигурация)
- [Синхронизация](#синхронизация)
  - [Полная синхронизация](#полная-синхронизация)
  - [Инкрементальная синхронизация](#инкрементальная-синхронизация)
  - [Планирование синхронизации](#планирование-синхронизации)
- [Обработка ошибок](#обработка-ошибок)
- [Очереди заданий](#очереди-заданий)
- [Чеклист для production](#чеклист-для-production)
- [История версий](#история-версий)
- [Лицензия](#лицензия)
- [Поддержка](#поддержка)
- [Ссылки](#ссылки)

## Возможности

- HTTP-клиент с поддержкой повторных запросов, идемпотентности и пагинации
- 23 модели Eloquent с отношениями и приведением типов
- 24 миграции базы данных с оптимизированными индексами
- 49 Data-классов для валидации данных
- 6 сервисов для бизнес-логики
- 7 очередей заданий для асинхронных операций
- 7 Artisan-команд для работы через CLI
- 11 событий для интеграции
- Фабрики и сидеры для тестирования
- Полная документация

## Установка

```bash
composer require geekcodev/laravel-commercejson
```

### Публикация конфигурации и миграций

```bash
# Публикация конфигурации
php artisan vendor:publish --tag=commercejson-config

# Публикация миграций
php artisan vendor:publish --tag=commercejson-migrations

# Запуск миграций
php artisan migrate
```

### Переменные окружения

Добавьте в файл `.env`:

```env
# CommerceJSON API
COMMERCEJSON_BASE_URL=https://api.your-erp.com/v1
COMMERCEJSON_AUTH_TYPE=bearer
COMMERCEJSON_AUTH_TOKEN=your-api-token
COMMERCEJSON_TIMEOUT=30
COMMERCEJSON_RETRY_ATTEMPTS=3

# Очереди (рекомендуется для production)
COMMERCEJSON_QUEUE_ENABLED=true
COMMERCEJSON_QUEUE_CONNECTION=redis

# Синхронизация
COMMERCEJSON_SYNC_SCHEDULE=0 * * * *
COMMERCEJSON_INCREMENTAL_SYNC=true

# Логирование
COMMERCEJSON_LOGGING=true
COMMERCEJSON_LOG_CHANNEL=stack
COMMERCEJSON_LOG_REQUESTS=false
```

## Использование

### Быстрый старт

```php
use GeekCo\CommerceJson\Facades\CommerceJson;

// Получить товары
$products = CommerceJson::products()->getProducts(
    page: 1,
    limit: 100,
    categoryId: 'uuid-here'
);

// Получить заказ
$order = CommerceJson::orders()->getOrder($orderId);

// Создать заказ
$newOrder = CommerceJson::orders()->createOrder($orderData);

// Импортировать предложения (цены и остатки)
CommerceJson::offers()->importOffers($offerImportData);
```

### Использование сервисов

```php
use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Services\OrderService;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private OrderService $orderService
    ) {}

    public function index()
    {
        $products = $this->productService->getProducts();
        return view('products.index', compact('products'));
    }

    public function store(OrderCreateData $data)
    {
        $order = $this->orderService->createOrder($data);
        return response()->json($order);
    }
}
```

### Console-команды

```bash
# Проверка соединения
php artisan commercejson:handshake

# Полная синхронизация
php artisan commercejson:sync --full

# Инкрементальная синхронизация (изменения за последний час)
php artisan commercejson:sync --incremental

# Импорт товаров
php artisan commercejson:import-products --queue

# Импорт заказов
php artisan commercejson:import-orders --updated-after=2026-01-01T00:00:00Z

# Экспорт заказов в ERP
php artisan commercejson:export-orders --limit=50
```

## Архитектура

### Структура пакета

```
src/
├── CommerceJsonServiceProvider.php
├── config/
│   └── commercejson.php
├── Http/Client/
│   └── CommerceJsonConnector.php
├── Services/
│   ├── ProductService.php
│   ├── OrderService.php
│   ├── OfferService.php
│   ├── ClassifierService.php
│   ├── WarehouseService.php
│   └── CounterpartyService.php
├── Models/                  (23 модели)
├── Data/                    (49 DTO-классов)
├── Enums/                   (11 перечислений)
├── Jobs/                    (7 очередей заданий)
├── Console/Commands/        (7 команд)
├── Events/                  (11 событий)
├── Exceptions/              (6 исключений)
├── Facades/
│   └── CommerceJson.php
└── database/
    ├── migrations/          (24 миграции)
    ├── factories/           (17 фабрик)
    └── seeders/             (7 сидеров)
```

### Таблицы базы данных

**24 таблицы:**
- `categories` — категории товаров (иерархия)
- `price_types` — типы цен (розница, опт, дилер)
- `warehouses` — склады
- `property_definitions` — свойства товаров
- `property_values` — значения свойств
- `counterparties` — контрагенты
- `contacts` — контактная информация
- `bank_accounts` — банковские счета
- `representatives` — представители
- `products` — каталог товаров
- `product_variants` — варианты товаров
- `product_images` — изображения товаров
- `offers` — торговые предложения
- `offer_prices` — цены предложений
- `stocks` — остатки на складах
- `orders` — заказы клиентов
- `order_items` — позиции заказов
- `order_item_taxes` — налоги позиций
- `status_history_entries` — история статусов
- `custom_attributes` — пользовательские атрибуты
- `signatories` — подписанты документов
- `product_analogues` — аналоги товаров
- `product_components` — комплектующие
- `order_linked_documents` — связанные документы

## Тестирование

Документация по генерации большого объёма данных для нагрузочного тестирования: [TESTING.md](TESTING.md).

```bash
# Запуск всех тестов
composer test

# Unit-тесты
php vendor/bin/phpunit --testsuite=Unit

# Feature-тесты
php vendor/bin/phpunit --testsuite=Feature

# С покрытием (HTML отчёт)
composer test:coverage

# С покрытием (текстовый отчёт)
composer test:coverage-text
```

После генерации HTML отчёта, откройте `coverage/index.html` в браузере.

### Покрытие кода

Текущее покрытие: [![Code Coverage](https://img.shields.io/codecov/c/github/geekcodev/laravel-commercejson/main?style=flat-square)](https://codecov.io/gh/geekcodev/laravel-commercejson)

Минимальные требования к покрытию:
- Services: 85%
- Models: 80%
- Jobs: 80%
- Console Commands: 75%
- **Общее:** 80%

См. подробную документацию в [COVERAGE.md](COVERAGE.md).

## Документация

### Доступные методы

#### ProductService

```php
// Получить товары с пагинацией
$products = $productService->getProducts(
    page: 1,
    limit: 100,
    categoryId: 'uuid',
    isActive: true,
    updatedAfter: new DateTime('2026-01-01')
);

// Получить товар по ID
$product = $productService->getProduct($id);

// Импортировать товары
$result = $productService->importProducts($productsData);

// Деактивировать товар
$productService->deactivateProduct($id);
```

#### OrderService

```php
// Получить заказы
$orders = $orderService->getOrders(
    page: 1,
    limit: 50,
    status: 'new',
    updatedAfter: new DateTime('2026-01-01')
);

// Создать заказ
$order = $orderService->createOrder($orderCreateData);

// Обновить статус заказа
$orderService->updateOrderStatus($orderId, 'confirmed');

// Экспортировать новые заказы
$exported = $orderService->exportOrders(limit: 50);
```

### События

```php
use GeekCo\CommerceJson\Events\ProductsImported;
use GeekCo\CommerceJson\Events\OrderImported;
use GeekCo\CommerceJson\Events\SyncCompleted;

Event::listen(ProductsImported::class, function ($event) {
    Log::info("Импортировано {$event->importedCount} товаров");
});

Event::listen(OrderImported::class, function ($event) {
    // Обработка нового заказа
});

Event::listen(SyncCompleted::class, function ($event) {
    Log::info("Синхронизация завершена за {$event->durationSeconds}с");
});
```

## Конфигурация

```php
// config/commercejson.php
return [
    'base_url' => env('COMMERCEJSON_BASE_URL'),
    
    'auth' => [
        'type' => env('COMMERCEJSON_AUTH_TYPE', 'bearer'),
        'token' => env('COMMERCEJSON_AUTH_TOKEN'),
    ],

    'timeout' => env('COMMERCEJSON_TIMEOUT', 30),
    'retry_attempts' => env('COMMERCEJSON_RETRY_ATTEMPTS', 3),

    'exchange' => [
        'mode' => env('COMMERCEJSON_EXCHANGE_MODE', 'auto'),
        'batch_size' => [
            'products' => 100,
            'offers' => 200,
            'orders' => 50,
        ],
        'queue' => [
            'enabled' => env('COMMERCEJSON_QUEUE_ENABLED', true),
            'connection' => env('COMMERCEJSON_QUEUE_CONNECTION', 'redis'),
        ],
    ],

    'sync' => [
        'schedule' => env('COMMERCEJSON_SYNC_SCHEDULE', '0 * * * *'),
        'incremental' => [
            'enabled' => env('COMMERCEJSON_INCREMENTAL_SYNC', true),
        ],
    ],

    'logging' => [
        'enabled' => env('COMMERCEJSON_LOGGING', true),
        'channel' => env('COMMERCEJSON_LOG_CHANNEL', 'stack'),
    ],
];
```

## Синхронизация

### Полная синхронизация

```bash
# Синхронизация всех данных
php artisan commercejson:sync --full
```

### Инкрементальная синхронизация

```bash
# Синхронизация изменений за последний час
php artisan commercejson:sync --incremental

# Синхронизация изменений с указанной даты
php artisan commercejson:sync --incremental --since=2026-01-01T00:00:00Z
```

### Планирование синхронизации

Добавьте в `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Ежечасная инкрементальная синхронизация
    $schedule->command('commercejson:sync --incremental')
        ->hourly();

    // Еженедельная полная синхронизация
    $schedule->command('commercejson:sync --full')
        ->weeklyOn(0, '2:00');
}
```

## Обработка ошибок

```php
use GeekCo\CommerceJson\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Exceptions\BusinessException;
use GeekCo\CommerceJson\Exceptions\RateLimitException;

try {
    $order = CommerceJson::orders()->createOrder($data);
} catch (AuthenticationException $e) {
    // 401, 403 - Неверные учётные данные
    Log::error('Ошибка авторизации: ' . $e->getMessage());
} catch (ValidationException $e) {
    // 400 - Неверные данные
    foreach ($e->errors() as $error) {
        Log::error($error);
    }
} catch (BusinessException $e) {
    // 422 - Бизнес-ошибка
    Log::error('Бизнес-ошибка: ' . $e->getBusinessCode());
} catch (RateLimitException $e) {
    // 429 - Слишком много запросов
    $retryAfter = $e->retryAfter(); // секунд
    Log::warning("Превышен лимит запросов. Повтор через {$retryAfter}с");
}
```

## Очереди заданий

```php
use GeekCo\CommerceJson\Jobs\Import\ImportProductsJob;
use GeekCo\CommerceJson\Jobs\Import\ImportOrdersJob;
use GeekCo\CommerceJson\Jobs\Sync\SyncFullJob;

// Импорт товаров асинхронно
ImportProductsJob::dispatch(
    page: 1,
    limit: 100,
    updatedAfter: now()->subHour()
);

// Цепочка заданий
ImportProductsJob::dispatch()->chain([
    new ImportOrdersJob(),
    new \GeekCo\CommerceJson\Jobs\Export\ExportOrdersJob(),
]);

// Полная синхронизация
SyncFullJob::dispatch();
```

## Чеклист для production

- [ ] Опубликовать конфигурацию: `php artisan vendor:publish --tag=commercejson-config`
- [ ] Опубликовать миграции: `php artisan vendor:publish --tag=commercejson-migrations`
- [ ] Запустить миграции: `php artisan migrate`
- [ ] Настроить worker очередей для асинхронных операций
- [ ] Настроить планирование синхронизации в Kernel.php
- [ ] Настроить мониторинг неудачных заданий
- [ ] Настроить канал логирования
- [ ] Проверить соединение: `php artisan commercejson:handshake`
- [ ] Запустить начальную полную синхронизацию: `php artisan commercejson:sync --full`

## История версий

### 1.0.0 (2026-04-24)
- Начальный выпуск
- Поддержка CommerceJSON v1.0.8
- 24 миграции
- 23 модели
- 49 Data-классов
- 6 сервисов
- 7 очередей заданий
- 7 console-команд
- 11 событий
- Полное тестовое покрытие

## Лицензия

Пакет распространяется под лицензией [MIT](LICENSE).

## Поддержка

- **Email:** geekco@yandex.ru
- **Issues:** [GitHub Issues](https://github.com/geekcodev/laravel-commercejson/issues)
- **Документация:** [Wiki](https://github.com/geekcodev/laravel-commercejson/wiki)

## Ссылки

- [Packagist](https://packagist.org/packages/geekcodev/laravel-commercejson)
- [GitHub](https://github.com/geekcodev/laravel-commercejson)
- [Спецификация CommerceJSON](https://commercejson.ru/docs)
