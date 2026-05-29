# CommerceJSON Laravel Package — AGENTS.md

## Обзор пакета

- **Пакет:** `geekcodev/laravel-commercejson`
- **Описание:** Интеграция CommerceJSON v1.0.8 для Laravel 13
- **Тип:** Laravel-пакет (library)
- **Стандарт:** OpenAPI 3.1 — `spec.yaml` (2682 строки, 40+ схем)
- **Назначение:** Двусторонний обмен коммерческими данными между ERP (1С) и сайтом
- **Namespace:** `GeekCo\CommerceJson\`
- **Провайдер:** `CommerceJsonServiceProvider` (autodiscovery через `composer.json`)
- **Архитектурные принципы:** SOLID, DRY, KISS, CQRS, инкапсуляция данных через Repository/Query

> **Единственный источник истины по API:** `spec.yaml`. Любые изменения роутов или схем должны сверяться с
> ним.

---

## Архитектурные принципы

### Доступ к данным — только через Repository или Query

Запрещено прямое обращение к Eloquent-моделям (`Model::where(...)`, `Model::find(...)`, `DB::table(...)`) вне
слоя Repository. Доступ к данным осуществляется только через:

- **Query** — для read-операций (через QueryBus)
- **Repository** — для read и write-операций (инкапсулирует Eloquent)

```php
// ✅ Правильно
$products = $this->productRepository->findMany($ids);
$order = $this->queryBus->ask(new GetOrderQuery($id));

// ❌ Неправильно
$products = Product::whereIn('id', $ids)->get();
$order = Order::find($id);
```

Исключение — прямое обращение к связанным моделям через отношения (`$order->items()->create(...)`) внутри
Handler'а, если это не нарушает инкапсуляцию и не дублирует логику репозитория.

### Инъекция репозиториев

Репозитории всегда инжектятся через конструктор. Имена свойств — в camelCase по имени репозитория:

```php
// ✅ Правильно
private readonly OrderRepository $orderRepository;
private readonly ProductRepository $productRepository;
private readonly WarehouseRepository $warehouseRepository;

// ❌ Неправильно
private readonly OrderRepository $repo;
private readonly ProductRepository $productRepo;
```

Laravel auto-resolves типизированные зависимости из контейнера (репозитории зарегистрированы как singletons
в `CommerceJsonServiceProvider::register()`).

### SOLID

| Принцип                       | Как соблюдается                                                                                    |
|-------------------------------|----------------------------------------------------------------------------------------------------|
| **S** — Single Responsibility | Контроллер — только валидация и диспетчеризация; Handler — бизнес-логика; Repository — работа с БД |
| **O** — Open/Closed           | Command/Query открыты для расширения (новые хендлеры), закрыты для модификации                     |
| **L** — Liskov Substitution   | `CommandInterface`/`QueryInterface` гарантируют контракт; все хендлеры взаимозаменяемы             |
| **I** — Interface Segregation | `RepositoryInterface` — минимальный набор методов; QueryBus отделён от CommandBus                  |
| **D** — Dependency Inversion  | Хендлеры зависят от абстракций (интерфейсов репозиториев), не от конкретных классов                |

### DRY — Don't Repeat Yourself

- Общая логика работы с БД — в `BaseRepository`
- Обработка ошибок (ForeignKeyViolationException, ModelNotFoundException) — в контроллерах, не дублируется в
  хендлерах
- Конфигурация — в `config/commercejson.php`, не хардкодится
- Валютные значения — только через `CurrencyEnum`, запрещены строки `'RUB'`, `'USD'`

### KISS — Keep It Simple, Stupid

- Команды — плоские DTO с публичными полями, без методов
- Хендлеры — один метод `handle()`, без лишней абстракции
- Контроллеры — только диспетчеризация, без бизнес-логики
- Если решение становится сложным — разбить на шаги или пересмотреть архитектуру

---

## Архитектура (CQRS)

```
HTTP Request
    ↓
Controller (только валидация + диспетчеризация)
    ↓
Bus::dispatch(Command) / QueryBus::ask(Query)
    ↓
CommandHandler / QueryHandler
    ↓
Repository (инкапсуляция Eloquent)
    ↓
Eloquent Model (слой данных)
```

### CommandBus — Laravel Bus (`Illuminate\Contracts\Bus\Dispatcher`)

Write-операции используют встроенный Laravel Bus с `Bus::map()` в `CommerceJsonServiceProvider::boot()`:

```php
Bus::map([
    CreateProductCommand::class => CreateProductCommandHandler::class,
    // ...
]);
```

- **Command** — DTO с публичными полями (данные для операции)
- **CommandHandler** — не требует интерфейса, содержит бизнес-логику в `DB::transaction()`
- **Laravel Bus** — auto-resolution из контейнера, middleware pipeline, `ShouldQueue`

### QueryBus — Самописный In-Memory Registry (`QueryBusInterface`)

Read-операции используют лёгкий кастомный bus, зарегистрированный в `CommerceJsonServiceProvider::register()`:

```php
$queryBus->register(GetProductsQuery::class, function ($query) use ($app) {
    return $app->make(GetProductsQueryHandler::class)->handle($query);
});
$products = $this->queryBus->ask(new GetProductsQuery(...));
```

- **Query** — DTO с параметрами фильтрации (например `perPage`, `includeDeleted`)
- **QueryHandler** — принимает `QueryInterface`, делегирует в репозиторий

### Правила

1. Command/Query — простой класс с конструктором и `public` полями
2. Handler **не содержит HTTP-зависимостей** (нет `Request`, `Response`)
3. Handler использует `assert($query instanceof XxxQuery)` для type-safety
4. Для команд достаточно типизации в конструкторе хендлера

---

## Слой DTO — Spatie Laravel Data v4

Все DTO используют `spatie/laravel-data` v4. **Без `#[MapName(SnakeCaseMapper::class)]`** — PHP-свойства названы в
`snake_case` чтобы совпадать с JSON-ключами API напрямую.

```php
class ProductData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid] public string $id,
        #[Required, StringType, Max(255)] public string $name,
        public ?string $code = null,
    ) {}
}
```

### Конвенции

- Nullable поля через `?string $field = null`
- Вложенные массивы объектов через `#[DataCollectionOf(PriceTypeData::class)]`
- Модель → DTO: `ProductData::from($model)`
- Коллекция → DTO collection: `ProductData::collect($collection, DataCollection::class)`

### Файлы DTO (49 в `src/Data/`)

| Категория | Файлы                                                                                                                                                                           |
|-----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Core      | `ClassifierData`, `CategoryData`, `PropertyDefinitionData`, `PriceTypeData`                                                                                                     |
| Products  | `ProductData`, `ProductVariantData`, `ProductImageData`, `ProductListData`, `ProductImportData`                                                                                 |
| Offers    | `OfferData`, `OfferImportData`, `OfferListData`, `OfferPriceData`, `StockData`                                                                                                  |
| Orders    | `OrderData`, `OrderCreateData`, `OrderPatchData`, `OrderImportData`, `OrderListData`, `OrderItemData`, `OrderItemCreateData`, `OrderItemUpdateData`, `OrderBulkUpdateItemData`  |
| Customers | `OrderCustomerData`, `OrderDeliveryData`, `OrderPaymentData`, `OrderTotalsData`, `CounterpartyData`, `CounterpartyListData`, `ContactData`, `BankAccountData`                   |
| Warehouse | `WarehouseData`                                                                                                                                                                 |
| Common    | `MoneyData`, `AddressData`, `SeoMetaData`, `DimensionsData`, `UnitData`, `ManufacturerData`, `SignatoryData`, `CustomAttributeData`, `LocalizedStringData`, `PropertyValueData` |
| Handshake | `HandshakeResponseData`, `CapabilitiesData`                                                                                                                                     |
| Response  | `PaginationData`, `ImportResultData`, `ImportErrorData`, `ErrorResponseData`                                                                                                    |

---

## Routes (соответствие OpenAPI)

- **Файл:** `src/routes/api.php`
- **Префикс:** `config('commercejson.api_routes.prefix')` (по умолч. `api/commercejson`)
- **Порядок важен:** `POST /orders/bulk` ДО `GET /orders/{id}`

```
Без auth:
  GET  /handshake  →  HandshakeController (__invoke)

С auth middleware:
  ┌──────────────────────────┬───────────────────────────────┐
  │ Route                    │ Controller                    │
  ├──────────────────────────┼───────────────────────────────┤
  │ GET  /catalog/classifier │ ClassifierController@index    │
  │ POST /catalog/classifier │ ClassifierController@store    │
  ├──────────────────────────┼───────────────────────────────┤
  │ GET  /catalog/products   │ ProductController@index       │
  │ POST /catalog/products   │ ProductController@store       │
  │ GET  /catalog/products/{id}│ ProductController@show      │
  │ DELETE /catalog/products/{id}│ ProductController@destroy │
  ├──────────────────────────┼───────────────────────────────┤
  │ GET  /offers             │ OfferController@index         │
  │ POST /offers             │ OfferController@store         │
  │ GET  /offers/price-types │ OfferController@priceTypes    │
  ├──────────────────────────┼───────────────────────────────┤
  │ GET  /orders             │ OrderController@index         │
  │ POST /orders             │ OrderController@store         │
  │ POST /orders/bulk        │ OrderController@bulkUpdate    │
  │ GET  /orders/{id}        │ OrderController@show          │
  │ PATCH /orders/{id}       │ OrderController@update        │
  ├──────────────────────────┼───────────────────────────────┤
  │ GET  /counterparties     │ CounterpartyController@index  │
  │ POST /counterparties     │ CounterpartyController@store  │
  │ GET  /counterparties/{id}│ CounterpartyController@show   │
  ├──────────────────────────┼───────────────────────────────┤
  │ GET  /warehouses         │ WarehouseController@index     │
  │ POST /warehouses         │ WarehouseController@store     │
  └──────────────────────────┴───────────────────────────────┘
```

### HTTP-методы по спецификации

| Сущность       | List | One | Create | Bulk | PATCH | DELETE |
|----------------|------|-----|--------|------|-------|--------|
| Classifier     | ✓    | —   | ✓      | —    | —     | —      |
| Products       | ✓    | ✓   | ✓      | —    | —     | ✓      |
| Offers         | ✓    | —   | ✓      | —    | —     | —      |
| PriceTypes     | ✓    | —   | —      | —    | —     | —      |
| Orders         | ✓    | ✓   | ✓      | ✓    | ✓     | —      |
| Counterparties | ✓    | ✓   | ✓      | —    | —     | —      |
| Warehouses     | ✓    | —   | ✓      | —    | —     | —      |

---

## Контроллеры

Тонкий слой: валидация Request → создание Command/Query → отправка в Bus → Response.

| Controller                   | Index                              | Show          | Store                             | Update | Destroy     | Bulk               |
|------------------------------|------------------------------------|---------------|-----------------------------------|--------|-------------|--------------------|
| `ClassifierController`       | `ClassifierData` через репозитории | —             | `ImportResultData` (batch upsert) | —      | —           | —                  |
| `ProductController`          | пагинированный список              | `ProductData` | `ProductData` (201)               | —      | soft-delete | —                  |
| `OfferController`            | пагинированный список              | —             | `OfferData` (201)                 | —      | —           | —                  |
| `OfferController@priceTypes` | `{price_types: [...]}`             | —             | —                                 | —      | —           | —                  |
| `OrderController`            | пагинированный список              | `OrderData`   | `OrderData` (201)                 | PATCH  | —           | `ImportResultData` |
| `CounterpartyController`     | пагинированный список              | одна запись   | 201                               | —      | —           | —                  |
| `WarehouseController`        | `WarehouseData[]`                  | —             | `ImportResultData`                | —      | —           | —                  |
| `HandshakeController`        | `HandshakeResponse` (без auth)     | —             | —                                 | —      | —           | —                  |

---

## Репозитории

### Интерфейс

```php
interface RepositoryInterface
{
    public function all(array $columns = ['*']): Collection;
    public function find(string $id): ?Model;
    public function findOrFail(string $id): Model;
    public function create(array $data): Model;
    public function update(Model $model, array $data): Model;
    public function delete(Model $model): bool;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function updateOrCreate(array $attributes, array $values = []): Model;
}
```

`BaseRepository` — абстрактная реализация через Eloquent. Специализированные:

- `ProductRepository` (+ `findByCategory()`, `findMany()`)
- `CategoryRepository` (+ `findByParent()`)
- `WarehouseRepository` (+ `allWithTrashed()`)
- `PriceTypeRepository`, `PropertyDefinitionRepository`, `OfferRepository`, `OrderRepository`, `CounterpartyRepository`

---

## Модели (23 Eloquent-модели в `src/Models/`)

| Модель             | SoftDeletes | HasUuids | Ключевые связи                        |
|--------------------|-------------|----------|---------------------------------------|
| Category           | —           | ✓        | parent, children, products            |
| Product            | ✓           | ✓        | category, variants, images, offers    |
| ProductVariant     | —           | —        | product, propertyValues               |
| ProductImage       | —           | —        | product                               |
| ProductAnalogue    | —           | —        | pivot (product_id, analogue_id)       |
| ProductComponent   | —           | —        | pivot (product_id, component_id, qty) |
| Offer              | ✓           | ✓        | product, variant, prices, stocks      |
| OfferPrice         | —           | —        | offer, priceType                      |
| PriceType          | —           | ✓        | offerPrices, counterparties           |
| Stock              | —           | —        | offer, warehouse                      |
| Warehouse          | ✓           | ✓        | stocks, orders                        |
| Order              | ✓           | ✓        | items, statusHistory, counterparty    |
| OrderItem          | —           | —        | order, product, variant, taxes        |
| Counterparty       | ✓           | ✓        | contacts, bankAccounts, reps          |
| PropertyDefinition | —           | —        | —                                     |
| PropertyValue      | —           | —        | —                                     |

---

## Services — HTTP-клиенты

Пакет также выступает **клиентом** для ERP. Сервисы реализуют `ServiceInterface` и используют `HttpClientInterface` (
Guzzle-обёртка):

- `ProductService`, `OfferService`, `OrderService`
- `ClassifierService`, `CounterpartyService`, `WarehouseService`

---

## Exchange — координация синхронизации

`src/Exchange/`:

```
ExchangeManager → ProductImporter / OrderImporter / ClassifierImporter / OrderExporter
                  ↓
              ImportJobs / ExportJobs (очередь)
```

**Jobs:** `ImportClassifierJob`, `ImportProductsJob`, `ImportOffersJob`, `ImportOrdersJob`, `ExportOrdersJob`,
`SyncFullJob`, `SyncIncrementalJob`

**Console commands:** `handshake`, `import:classifier`, `import:products`, `import:offers`, `import:orders`,
`export:orders`, `sync`

---

## Соглашения

1. **Production-ready код** — любое решение должно быть профессиональным, следовать best-practices (нет хардкода, нет
   заглушек, значения из конфига/запроса, типизация через enum, корректные расчёты). Если требуется заглушка — она явно
   документируется в коде с пояснением `// TODO` или `// stub`.

2. **Формат пагинации** в ответе: `{data: [...], meta: {current_page, last_page, per_page, total}}`  
   TODO: привести к spec — `{products: [...], pagination: {page, limit, total, has_next}}`

3. **Soft delete** — `DELETE` проставляет `is_active=false` + `deleted_at`, не удаляет запись

4. **Snake-case DTO** — без `#[MapName(SnakeCaseMapper::class)]`. PHP-свойства названы в `snake_case` как в JSON API

5. **UUID** — все сущности используют UUIDv4 как primary key (trait `HasUuids`)

6. **Транзакции** — CommandHandler оборачивает запись в `DB::transaction()`

7. **Валюта** — все денежные значения без исключения должны быть типизированы через `CurrencyEnum`. Запрещены
   хардкодные строки `'RUB'`, `'USD'` и т.п. в любом коде, включая миграции и любые другие файлы.

8. **Ответ с ошибкой** — `{error: {code, message, details?}}`

9. **Идемпотентность** — поддерживается через `X-Idempotency-Key` (TODO: реализовать кеширование)

---

## Известные проблемы (несоответствия spec и мёртвый код)

| Проблема                    | Детали                                                                      |
|-----------------------------|-----------------------------------------------------------------------------|
| `CategoryController`        | Мёртвый — нет роутов; категории доступны только через `/catalog/classifier` |
| `OfferController::show()`   | Мёртвый — нет роута `GET /offers/{id}`                                      |
| `UpdateProductCommand`      | Зарегистрирован в `Bus::map()` но никогда не диспатчится                    |
| `UpdateCounterpartyCommand` | То же                                                                       |
| `DeleteOrderCommand`        | То же                                                                       |
| `DeleteOfferCommand`        | То же                                                                       |
| `GetOfferQuery`             | Зарегистрирован в QueryBus но не используется                               |
| `GetCategoryQuery`          | То же                                                                       |
| `GetCategoriesQuery`        | То же                                                                       |

---

## Тестирование

```bash
docker compose exec app vendor/bin/pest           # Запуск всех тестов (Pest v3.8, 39 тестов, 186 assertions)
docker compose exec app vendor/bin/pest --parallel# Параллельный запуск
docker compose exec app composer analyse          # PHPStan статический анализ
docker compose exec app composer format           # Laravel Pint code style
```

> PHP запускается внутри Docker-контейнера: `docker compose exec app <command>`

- **Фреймворк:** Pest PHP v3.8 на движке PHPUnit 11.5.50
- **Bootstrap:** `tests/TestCase.php` наследует `Orchestra\Testbench\TestCase`, загружает `LaravelDataServiceProvider` +
  `CommerceJsonServiceProvider`
- **Хелперы (глобальные в `tests/Pest.php`):** `mockCommandBus()` и `mockQueryBus()` используют `app()->instance()` (не
  `test()->instance()`) для совместимости с IDE
- **Конфиг:** `config('data.structure_caching.reflection_discovery.enabled') = false` — отключено для стабильности
- **PHPUnit:** методы тестов должны иметь префикс `test_` или атрибут `#[Test]`

### После внесения изменений

Обязательно проверить:

1. `vendor/bin/pest` — все тесты зелёные
2. `composer analyse` — нет ошибок PHPStan
3. `composer format` — PSR-12 compliant

---

## История сессий

### Сессия 1 — Переписывание роутов, миграция CommandBus

- Роуты переписаны под OpenAPI spec (удалён `apiResource('categories')`, добавлены classifier, price-types, bulk,
  warehouses)
- Новые: `ClassifierController`, `WarehouseController`, `GetWarehousesQuery`, `GetPriceTypesQuery`
- Кастомный `CommandBus` singleton → Laravel `Bus::map()` (7 контроллеров, 6 сервисов, OrderImporter, 10 тестов)
- `RepositoryInterface`/`BaseRepository`: добавлены `all()` и `updateOrCreate()`

### Сессия 2 — DTO snake-case миграция, стабилизация Pest

- Убран `#[MapName(SnakeCaseMapper::class)]` из всех 49 DTO
- Переименованы 105+ PHP-свойств из camelCase в snake_case (34 DTO-файла + 20+ зависимых)
- Исправлен конфиг `spatie/laravel-data` (`structure_caching` выключен, normalizers настроены)
- Исправлены тесты: моки моделей → `Factory::make()`, возврат DTO вместо Eloquent-моделей
- Багфиксы: `SeoMetaData` nullable types, `CategoryData::is_active` default (false→true), `ImportResultData`
  `?array $warnings`
- Исправлен `CommerceJsonHttpClientTest` — добавлен префикс `test_` к 12 методам
- Исправлен `test()->instance()` → `app()->instance()` для совместимости с IDE
- Все 37 тестов проходят (180 assertions), 1 warning (code coverage), 1 deprecation

### Сессия 3 — POST /orders/bulk delivery tracking, date format fix

- Исправлена ошибка `CannotCreateData: constructor requires 8 parameters, 7 given (missing: type)`:
  `OrderBulkUpdateItemData` теперь использует `OrderDeliveryTrackData` (3 nullable поля вместо `OrderDeliveryData` с
  required `type`)
- `OrderDeliveryTrackData` — новый DTO для частичного обновления доставки в bulk (spec: `tracking_number`,
  `shipped_at`, `estimated_date`; 3 поля, все nullable, без `type`/`address`/`cost`)
- `OrderDeliveryData` — возвращён required `type` (spec для create/patch, не для bulk)
- Исправлена пропавшая `use Spatie\LaravelData\Data;` + `use Spatie\LaravelData\Attributes\Validation\StringType;`
  в `OrderDeliveryData.php`
- `UpsertOrderCommand` — добавлено поле `?OrderDeliveryTrackData $deliveryTrack` для передачи данных
  доставки отдельно от `OrderData`
- `UpsertOrderCommandHandler` — обновляет `delivery_tracking_number`, `delivery_shipped_at`,
  `delivery_estimated_date` напрямую через `$order->update()` если `deliveryTrack` передан
- `OrderController::bulkUpdate()` — передаёт `$bulkItem->delivery` в `UpsertOrderCommand`
- Исправлен `date_format` в `tests/TestCase.php`: заменён `DATE_ATOM` на массив форматов,
  поддерживающий `Z`-суффикс (`2026-05-28T19:30:12.949Z`), дробные секунды и дату без времени (`Y-m-d`)
- `OrderDeliveryTrackData` — убран `#[StringType]` с Carbon-полей (конфликт с DateTimeInterfaceCast)
- PHPStan: исправлены ошибки (nullsafe, missing imports)
- Добавлен тест `it handles delivery tracking in bulk import` с точными данными из spec
- Все 39 тестов проходят (186 assertions), PHPStan clean

### Сессия 4 — BulkUpsertOrderCommand (независимый от OrderData)

- Создан `BulkUpsertOrderCommand` — независимый от `OrderData`, принимает `id`, `external_id`, `status`,
  `comment`, `custom_attributes`, `items` (`?array`), `deliveryTrack`
- Создан `BulkUpsertOrderCommandHandler` с раздельными путями create/update:
    - **Create:** генерирует `number`, `document_type`, статус; опционально создаёт items и рассчитывает
      `totals_subtotal/total_amount/currency`; опционально применяет deliveryTrack
    - **Update:** обновляет только переданные поля (`external_id`, `status`, `comment`);
      если `items` !== null — удаляет старые и создаёт новые позиции (spec: "полностью заменяет позиции");
      если `items === null` — не трогает позиции (spec: "если поле отсутствует — не изменяются");
      опционально применяет deliveryTrack
- `OrderController::bulkUpdate()` переписан: больше не билдит `$orderArray` → `OrderData` → `UpsertOrderCommand`,
  вместо этого передаёт сырые данные в `BulkUpsertOrderCommand` напрямую
- Убран мёртвый код из контроллера: `DocumentTypeEnum` import, `UpsertOrderCommand` import
- PHPStan clean (0 errors), 39 тестов (186 assertions)

---

## Ключевые файлы

| Файл                                                      | Назначение                                                       |
|-----------------------------------------------------------|------------------------------------------------------------------|
| `spec.yaml`                                               | OpenAPI 3.1 спецификация (единственный источник истины)          |
| `src/routes/api.php`                                      | Определения роутов                                               |
| `src/CommerceJsonServiceProvider.php`                     | Service provider с Bus::map() и QueryBus                         |
| `src/config/commercejson.php`                             | Конфигурация пакета                                              |
| `src/Http/Controllers/`                                   | 7 контроллеров + HandshakeController                             |
| `src/Data/`                                               | 49 DTO (Spatie Laravel Data v4)                                  |
| `src/Models/`                                             | 23 Eloquent-модели                                               |
| `src/Repositories/`                                       | RepositoryInterface + 8 реализаций                               |
| `src/Exchange/`                                           | Координация синхронизации (импортёры, экспортёры, jobs, команды) |
| `tests/TestCase.php`                                      | Testbench bootstrap                                              |
| `tests/Pest.php`                                          | Глобальные хелперы и конфигурация                                |
| `src/Commands/BulkUpsertOrderCommand.php`                 | Bulk-команда для `POST /orders/bulk` (не зависит от `OrderData`) |
| `src/Handlers/Commands/BulkUpsertOrderCommandHandler.php` | Handler с раздельными create/update путями и опциональными items |
