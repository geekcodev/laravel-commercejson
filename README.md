# Laravel CommerceJSON

[![Latest Version on Packagist](https://img.shields.io/packagist/v/geekcodev/laravel-commercejson.svg?style=flat-square)](https://packagist.org/packages/geekcodev/laravel-commercejson)
[![Total Downloads](https://img.shields.io/packagist/dt/geekcodev/laravel-commercejson.svg?style=flat-square)](https://packagist.org/packages/geekcodev/laravel-commercejson)
[![Code Coverage](https://img.shields.io/codecov/c/github/geekcodev/laravel-commercejson/main?style=flat-square)](https://codecov.io/gh/geekcodev/laravel-commercejson)
![GitHub Actions (main)](https://img.shields.io/github/actions/workflow/status/geekcodev/laravel-commercejson/run-tests.yml?branch=main&label=tests&style=flat-square)
![GitHub Actions (main)](https://img.shields.io/github/actions/workflow/status/geekcodev/laravel-commercejson/phpstan.yml?branch=main&label=phpstan&style=flat-square)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Пакет для интеграции с CommerceJSON API v1.0.8 в Laravel 13. Предназначен для обмена данными с системами 1С и другими
ERP-системами, поддерживающими стандарт CommerceJSON.

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

- HTTP-клиент с поддержкой повторных запросов, идемпотентности (`X-Idempotency-Key`) и пагинации
- 23 модели Eloquent с UUIDv4, SoftDeletes и отношениями
- 24 миграции базы данных с оптимизированными индексами
- 49 Spatie Data DTO для строгой валидации
- 6 сервисов для HTTP-клиента (классы для интеграции с ERP)
- 7 очередей заданий для асинхронной обработки
- 7 Artisan-команд для CLI-работы
- 11 событий для интеграции с приложением
- CQRS-архитектура: CommandBus (Laravel Bus) + QueryBus (in-memory)
- Репозитории через `RepositoryInterface` (инкапсуляция Eloquent)
- Rate limiting и идемпотентность на API-роутах
- Фабрики и сидеры для тестирования

## Установка

После установки пакета и публикации конфига, маршруты автоматически регистрируются в `CommerceJsonServiceProvider`:

```php
// Автоматическая регистрация в boot() методе
$this->loadRoutesFrom(__DIR__.'/routes/api.php');
```

**Готовые REST API endpoints** становятся доступны по префиксу `/api/commercejson` (настраивается через
`config('commercejson.api_routes.prefix')`).

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

### Два способа работы с пакетом

#### 1. REST API (Headless CMS) — рекомендуется для frontend

Пакет предоставляет готовые REST API endpoints. Используйте HTTP запросы из вашего frontend приложения:

```javascript
// Frontend (React/Vue/Next.js) или мобильное приложение
const response = await fetch('https://your-app.test/api/commercejson/products');
const products = await response.json();

// Или через axios
const {data} = await axios.get('/api/commercejson/products');

// Создать заказ
const {data: order} = await axios.post('/api/commercejson/orders', {
    number: 'ORD-001',
    status: 'new',
    items: [...]
});
```

**Преимущества:**

- ✅ Готовые endpoints из коробки
- ✅ Не нужно писать контроллеры
- ✅ Идеально для React/Vue/Next.js frontend
- ✅ Мобильные приложения получают доступ к API
- ✅ CQRS архитектура внутри контроллеров

#### 2. Сервисы — для кастомной бизнес-логики

Если вам нужна кастомная логика, используйте сервисы напрямую:

```php
use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Services\OrderService;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;

class OrderController extends Controller
{
    public function __construct(
        private HttpClientInterface $http,
        private OrderService $orderService
    ) {}

    public function index()
    {
        // Чтение через HTTP API
        $orders = $this->orderService->getOrders(page: 1, limit: 100);
        return view('orders.index', compact('orders'));
    }

    public function store(OrderCreateData $data)
    {
        // Создание через HTTP API + сохранение через CommandBus
        $order = $this->orderService->createOrder($data);
        return response()->json($order);
    }
}
```

### Тестирование

```php
use GeekCo\CommerceJson\Tests\TestCase;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use Mockery;

class ProductServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttp = Mockery::mock(HttpClientInterface::class);
        $this->productService = new ProductService($this->mockHttp, ...);
    }

    /** @test */
    public function get_products_returns_product_list(): void
    {
        $this->mockHttp->shouldReceive('get')
            ->once()
            ->andReturn(new ResponseDto(200, [], $mockResponse, ...));
        
        $products = $this->productService->getProducts(page: 1, limit: 100);
        $this->assertCount(10, $products->products);
    }
}
```

#### Использование factory для Data-классов

```php
// Создание тестовых данных
$productData = ProductData::factory()->from([
    'id' => 'uuid-here',
    'name' => 'Test Product',
    'code' => 'TEST-001',
    'is_active' => true,
]);

// В тестах
protected function createProductData(array $attributes = []): ProductData
{
    return ProductData::factory()->from([
        'id' => $this->createTestUuid(),
        'name' => 'Test Product',
        ...$attributes,
    ]);
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

Пакет использует архитектурные паттерны **CQRS** (Command Query Responsibility Segregation) и **Repository** для
разделения операций чтения и записи, что обеспечивает:

- **Чёткое разделение ответственности** — команды для записи, запросы для чтения
- **Тестируемость** — легко мокировать зависимости через интерфейсы
- **Масштабируемость** — независимое масштабирование чтения/записи
- **Поддерживаемость** — понятная структура кода
- **Headless CMS** — готовые REST API контроллеры и роуты из коробки

### Готовые REST API endpoints

Пакет автоматически регистрирует маршруты в соответствии с OpenAPI спецификацией CommerceJSON v1.0.8.
**Все эндпоинты (кроме `/handshake`) защищены аутентификацией, rate limiting и идемпотентностью.**

```
Без auth:
  GET  /handshake  →  HandshakeController

С auth middleware:
  GET    /catalog/classifier          →  ClassifierController@index
  POST   /catalog/classifier          →  ClassifierController@store
  GET    /catalog/products            →  ProductController@index
  POST   /catalog/products            →  ProductController@store
  GET    /catalog/products/{id}       →  ProductController@show
  DELETE /catalog/products/{id}       →  ProductController@destroy
  GET    /offers                      →  OfferController@index
  POST   /offers                      →  OfferController@store
  GET    /offers/price-types          →  OfferController@priceTypes
  GET    /orders                      →  OrderController@index
  POST   /orders                      →  OrderController@store
  POST   /orders/bulk                 →  OrderController@bulkUpdate
  GET    /orders/{id}                 →  OrderController@show
  PATCH  /orders/{id}                 →  OrderController@update
  GET    /counterparties              →  CounterpartyController@index
  POST   /counterparties              →  CounterpartyController@store
  GET    /counterparties/{id}         →  CounterpartyController@show
  GET    /warehouses                  →  WarehouseController@index
  POST   /warehouses                  →  WarehouseController@store
```

**Важно:** `POST /orders/bulk` должен быть объявлен до `GET /orders/{id}`.

**Пагинация:** query-параметр `limit` (не `per_page`). Ответ:
`{entity: [...], pagination: {page, limit, total, has_next}}`.

**Пример запроса:**

```bash
# Получить список товаров
curl -X GET "https://your-app.test/api/commercejson/catalog/products?page=1&limit=20" \
  -H "Accept: application/json"

# Создать товар
curl -X POST "https://your-app.test/api/commercejson/catalog/products" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"id": "uuid-here", "name": "Product 1", "code": "PROD-001", "is_active": true}'
```

### Архитектура

Подробное описание архитектуры (CQRS, SOLID, Repository pattern, DTO конвенции, security) — в [`AGENTS.md`](AGENTS.md).

### Структура пакета

```
src/
├── CommerceJsonServiceProvider.php    # Регистрация сервисов, Bus::map()
├── config/
│   └── commercejson.php               # Конфигурация (235 строк)
├── routes/
│   └── api.php                        # OpenAPI-совместимые роуты
├── Commands/                          # Command DTO (запись, 17)
│   ├── UpsertProductCommand.php
│   └── ...
├── Queries/                           # Query DTO (чтение, 13)
│   ├── GetProductQuery.php
│   └── ...
├── Bus/                               # Шины
│   └── QueryBusInterface.php          # Кастомная шина запросов
├── Handlers/                          # Обработчики (28)
│   ├── Commands/                      # Обработчики команд (Laravel Bus, 16)
│   └── Queries/                       # Обработчики запросов (11)
├── Repositories/                      # Репозитории (12)
│   ├── RepositoryInterface.php
│   ├── BaseRepository.php
│   ├── ProductRepository.php
│   └── ...
├── Http/
│   ├── Client/                        # HTTP-клиент для ERP
│   ├── Middleware/                     # IdempotencyMiddleware, throttle
│   └── Controllers/                   # API контроллеры (6 + HandshakeController)
├── Services/                          # HTTP-клиент для ERP (6)
│   ├── ProductService.php
│   ├── OrderService.php
│   └── ...
├── Exchange/                          # Синхронизация
│   ├── Import/
│   └── Export/
├── Jobs/                              # Очереди заданий (7)
│   ├── Import/
│   ├── Export/
│   └── Sync/
├── Console/Commands/                  # Artisan команды (7)
├── Events/                            # События (11)
├── Exceptions/                        # Исключения
│   └── SyncException.php
├── Models/                            # Eloquent модели (23)
├── Data/                              # Spatie Data DTO (49)
├── Enums/                             # Перечисления (11)
├── Facades/
│   └── CommerceJson.php
└── database/
    ├── migrations/                    # Миграции (24)
    ├── factories/                     # Фабрики (17)
    └── seeders/                       # Сидеры (8)
```

### Компоненты архитектуры

#### 1. HTTP Client Layer

```php
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;

// Внедрение зависимости
public function __construct(
    private HttpClientInterface $http
) {}

// Использование
$response = $this->http->get('/catalog/products', ['page' => 1]);
$data = $response->data; // Массив данных
```

#### 2. Command/Query Bus

```php
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\UpsertProductCommand;
use GeekCo\CommerceJson\Queries\GetProductQuery;
use Illuminate\Contracts\Bus\Dispatcher;

// Команда (запись) — через Laravel Bus
$command = new UpsertProductCommand($productData);
$product = $this->commandBus->dispatch($command); // Dispatcher

// Запрос (чтение) — через кастомный QueryBus
$query = new GetProductQuery($id);
$product = $this->queryBus->ask($query); // QueryBusInterface
```

#### 3. Services (бизнес-логика)

```php
use GeekCo\CommerceJson\Services\ProductService;

public function __construct(
    private ProductService $productService
) {}

// Чтение через HTTP API
$products = $this->productService->getProducts(page: 1, limit: 100);

// Запись через CommandBus
$product = $this->productService->syncProduct($productData);
```

#### 4. Controllers (API endpoints)

```php
use GeekCo\CommerceJson\Http\Controllers\ProductController;

// GET /api/commercejson/products
public function index(Request $request): JsonResponse
{
    $query = new GetProductsQuery(limit: 15);
    $products = $this->queryBus->ask($query);
    
    return response()->json(ProductData::collect($products->items()));
}

// POST /api/commercejson/products
public function store(Request $request): JsonResponse
{
    $command = new UpsertProductCommand(ProductData::from($request->all()));
    $product = $this->commandBus->dispatch($command);
    
    return response()->json(ProductData::from($product), 201);
}
```

#### 5. Middleware

API-роуты защищены слоем middleware:

- **Аутентификация** — `auth:commercejson` для всех эндпоинтов кроме `/handshake`
- **Rate limiting** — `throttle` на всех auth-роутах (конфиг `rate_limit`/`rate_limit_decay`, по умолч. 60/мин)
- **Идемпотентность** — `IdempotencyMiddleware` кеширует ответ POST/PATCH по `X-Idempotency-Key` + fingerprint запроса (
  TTL из конфига)
- **Rate limiting** — `throttle:rate_limit,rate_limit_decay` на всех write-роутах (по умолч. 60/мин)

#### 6. Exceptions

```php
use GeekCo\CommerceJson\Http\Client\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\Exceptions\BusinessException;
use GeekCo\CommerceJson\Http\Client\Exceptions\RateLimitException;

try {
    $this->http->post('/orders', $data);
} catch (AuthenticationException $e) {
    // 401, 403
} catch (ValidationException $e) {
    // 400 - $e->errorsAsString()
} catch (BusinessException $e) {
    // 422, 429
}
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
# PHP запускается внутри Docker-контейнера
docker compose exec app vendor/bin/pest

# Параллельный запуск
docker compose exec app vendor/bin/pest --parallel

# С покрытием (HTML отчёт)
docker compose exec app composer test:coverage

# PHPStan статический анализ
docker compose exec app composer analyse

# Laravel Pint code style
docker compose exec app composer format
```

После генерации HTML отчёта, откройте `coverage/index.html` в браузере.

### Покрытие кода

Текущее
покрытие: [![Code Coverage](https://img.shields.io/codecov/c/github/geekcodev/laravel-commercejson/main?style=flat-square)](https://codecov.io/gh/geekcodev/laravel-commercejson)

**49 тестов, 230 assertions.** Покрытие требует расширения — см. [CODE_AUDIT.md](CODE_AUDIT.md) (Фазы 2–5) и
[COVERAGE.md](COVERAGE.md).

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

### HTTP исключения

```php
use GeekCo\CommerceJson\Http\Client\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\Exceptions\BusinessException;
use GeekCo\CommerceJson\Http\Client\Exceptions\RateLimitException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ConnectionException;

try {
    $order = CommerceJson::orders()->createOrder($data);
} catch (AuthenticationException $e) {
    // 401, 403 - Неверные учётные данные
    Log::error('Ошибка авторизации: ' . $e->getMessage());
} catch (ValidationException $e) {
    // 400 - Ошибка валидации
    Log::error('Валидация: ' . $e->errorsAsString());
    foreach ($e->errors() as $error) {
        Log::error($error);
    }
} catch (BusinessException $e) {
    // 422, 429 - Бизнес-ошибка или rate limit
    Log::error('Бизнес-ошибка [' . $e->getCode() . ']: ' . $e->getMessage());
} catch (RateLimitException $e) {
    // 429 - Превышен лимит запросов
    $retryAfter = $e->retryAfter(); // секунд до повторной попытки
    $retryAt = $e->retryAt();       // DateTime для повторной попытки
    Log::warning("Превышен лимит запросов. Повтор через {$retryAfter}с");
} catch (ConnectionException $e) {
    // Ошибка соединения
    Log::error('Ошибка соединения: ' . $e->getMessage());
}
```

### Бизнес исключения

```php
use GeekCo\CommerceJson\Exceptions\SyncException;

try {
    CommerceJson::syncFull();
} catch (SyncException $e) {
    Log::error('Синхронизация [' . $e->getSyncType() . ']: ' . $e->getMessage());
    $lastSync = $e->getLastSyncTime(); // DateTime последней успешной синхронизации
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
