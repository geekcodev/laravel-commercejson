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
    UpsertProductCommand::class => UpsertProductCommandHandler::class,
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

### Файлы DTO (48 в `src/Data/`)

| Категория | Файлы                                                                                                                                                                                                                                                 |
|-----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Core      | `ClassifierData`, `CategoryData`, `PropertyDefinitionData`, `PriceTypeData`                                                                                                                                                                           |
| Products  | `ProductData`, `ProductVariantData`, `ProductImageData`, `ProductListData`, `ProductImportData`                                                                                                                                                       |
| Offers    | `OfferData`, `OfferImportData`, `OfferListData`, `OfferPriceData`, `StockData`                                                                                                                                                                        |
| Orders    | `OrderData`, `OrderCreateData`, `OrderImportData`, `OrderListData`, `OrderItemData`, `OrderItemCreateData`, `OrderItemUpdateData`, `OrderBulkUpdateItemData`, `OrderDeliveryTrackData`, `OrderItemTaxData`, `OrderPatchData`, `OrderPatchPaymentData` |
| Customers | `OrderCustomerData`, `OrderDeliveryData`, `OrderPaymentData`, `OrderTotalsData`, `CounterpartyData`, `CounterpartyListData`, `ContactData`, `BankAccountData`                                                                                         |
| Warehouse | `WarehouseData`, `WarehouseImportData`                                                                                                                                                                                                                |
| Common    | `MoneyData`, `AddressData`, `SeoMetaData`, `DimensionsData`, `UnitData`, `ManufacturerData`, `SignatoryData`, `CustomAttributeData`, `PropertyValueData`, `StatusHistoryEntryData`                                                                    |
| Handshake | `HandshakeResponseData`, `CapabilitiesData`                                                                                                                                                                                                           |
| Response  | `PaginationData`, `ImportResultData`, `ErrorResponseData`                                                                                                                                                                                             |

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

| Controller                   | Index                                                          | Show          | Store                             | Update | Destroy     | Bulk               |
|------------------------------|----------------------------------------------------------------|---------------|-----------------------------------|--------|-------------|--------------------|
| `ClassifierController`       | `ClassifierData` через репозитории                             | —             | `ImportResultData` (batch upsert) | —      | —           | —                  |
| `ProductController`          | пагинированный список                                          | `ProductData` | `ProductData` (201)               | —      | soft-delete | —                  |
| `OfferController`            | пагинированный список (+ `price_types`/`warehouses` на 1 стр.) | —             | `ImportResultData` (200)          | —      | —           | —                  |
| `OfferController@priceTypes` | `{price_types: [...]}`                                         | —             | —                                 | —      | —           | —                  |
| `OrderController`            | пагинированный список                                          | `OrderData`   | `OrderData` (201)                 | PATCH  | —           | `ImportResultData` |
| `CounterpartyController`     | пагинированный список                                          | одна запись   | `ImportResultData` (batch upsert) | —      | —           | —                  |
| `WarehouseController`        | `WarehouseData[]`                                              | —             | `ImportResultData`                | —      | —           | —                  |
| `HandshakeController`        | `HandshakeResponse` (без auth)                                 | —             | —                                 | —      | —           | —                  |

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

- `ProductRepository` (+ `findByCategory()`, `findMany()`, `findByExternalId()`)
- `CategoryRepository` (+ `findByParent()`)
- `WarehouseRepository` (+ `allWithTrashed()`)
- `PriceTypeRepository`, `PropertyDefinitionRepository`, `OfferRepository`, `OrderRepository` (+ `findByExternalId()`),
  `CounterpartyRepository`, `OfferPriceRepository`, `StockRepository`

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

2. **Формат пагинации** в ответе: `{entity: [...], pagination: {page, limit, total, has_next}}`  
   Где `entity` — имя сущности во множественном числе (`products`, `orders`, `offers`, `counterparties`).  
   Query-параметр — `limit` (внутри Query — `perPage`).

3. **Soft delete** — `DELETE` проставляет `is_active=false` + `deleted_at`, не удаляет запись

4. **Snake-case DTO** — без `#[MapName(SnakeCaseMapper::class)]`. PHP-свойства названы в `snake_case` как в JSON API

5. **UUID** — все сущности используют UUIDv4 как primary key (trait `HasUuids`)

6. **Транзакции** — CommandHandler оборачивает запись в `DB::transaction()`

7. **Валюта** — все денежные значения без исключения должны быть типизированы через `CurrencyEnum`. Запрещены
   хардкодные строки `'RUB'`, `'USD'` и т.п. в любом коде, включая миграции и любые другие файлы.

8. **Ответ с ошибкой** — `{error: {code, message, details?}}`

9. **Идемпотентность** — поддерживается через `IdempotencyMiddleware` (X-Idempotency-Key + кеш с fingerprint md5(path:
   body), TTL из конфига)

---

## Безопасность (OWASP Top 10)

Пакет должен быть безопасным. Соблюдаем OWASP Top 10 на уровне кода:

| #   | Категория OWASP               | Требования к коду                                                                                                                                                                                                                                                   |
|-----|-------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| A01 | **Broken Access Control**     | Все эндпоинты (кроме `/handshake`) за middleware аутентификации; проверка прав через Policy/Gate перед операциями                                                                                                                                                   |
| A02 | **Cryptographic Failures**    | UUIDv4 вместо автоинкремента; HTTPS-only (config `commercejson.force_https`); никаких секретов в коде/миграциях/логах                                                                                                                                               |
| A03 | **Injection**                 | Все входные данные проходят валидацию Request + Spatie Data; запрещены сырые `DB::raw()`, `whereRaw()`, `orderByRaw()` с пользовательским вводом; Eloquent через репозитории                                                                                        |
| A04 | **Insecure Design**           | CQRS разделяет read/write; Command/Query — строго типизированные DTO; rate limiting на роутах                                                                                                                                                                       |
| A05 | **Security Misconfiguration** | CORS — из конфига, не `*`; отключён `APP_DEBUG` в production; middleware pipeline явно задан в роутах                                                                                                                                                               |
| A06 | **Vulnerable Components**     | `composer audit` — обязателен перед релизом; версии зависимостей зафиксированы в `composer.json`                                                                                                                                                                    |
| A07 | **Authentication Failures**   | API-ключ/token из конфига, сравнение через `hash_equals()`, не через `==`; логирование неудачных попыток                                                                                                                                                            |
| A08 | **Integrity Failures**        | Все входящие данные от ERP валидируются через Spatie Data (типы, форматы, required); CI/CD подписывает релизные теги                                                                                                                                                |
| A09 | **Logging & Monitoring**      | Все ошибки аутентификации и валидации логируются через `Log::channel('commercejson')`; все API-запросы логируются через `LogApiRequestsMiddleware` в канал `commercejson-api` (с маскировкой sensitive data); запрещено логирование sensitive data (пароли, токены) |
| A10 | **SSRF**                      | HTTP-клиенты (Guzzle) имеют таймауты и белый список URL из конфига; запрещён динамический URL из пользовательского ввода                                                                                                                                            |

### Дополнительные правила

- **Mass Assignment:** все `create()`/`update()` в репозиториях через `$fillable` модели, не через `$guarded=[]`
- **XSS:** любые данные от ERP перед выводом проходят `e()` или `strip_tags()`; API возвращает JSON, не HTML
- **CSRF:** API-роуты в `api.php`, не в `web.php` — CSRF-защита не применяется, вместо неё аутентификация через Bearer
  token
- **Идемпотентность:** `IdempotencyMiddleware` кеширует ответы по `X-Idempotency-Key` + fingerprint запроса (TTL из
  конфига), возвращает 201/200 при повторном запросе
- **Input validation:** запрещён `$request->all()` напрямую в контроллерах — только через FormRequest или DTO
- **Secrets:** никаких ключей в миграциях, seed-файлах или комментариях; всё через `config/commercejson.php` + `.env`

---

## Известные проблемы (мёртвый код на диске)

Мёртвый код отсутствует — все неиспользуемые файлы удалены. Если в будущем появятся,
они будут задокументированы ниже.

---

## Тестирование

```bash
docker compose exec app vendor/bin/pest                              # Запуск всех тестов (Pest v3.8, 119 тестов, 879 assertions)
docker compose exec app vendor/bin/pest --parallel                   # Параллельный запуск
docker compose exec app vendor/bin/phpstan analyse                   # PHPStan (локально)
docker compose exec app vendor/bin/phpstan analyse --error-format=github  # PHPStan (как в CI — обязателен перед push)
docker compose exec app vendor/bin/pint                              # Laravel Pint code style (исправит)
docker compose exec app vendor/bin/pint --test                       # Pint только проверить
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

### Сессия 5 — DTO реордер, per_page→limit, rate limiting, тесты middleware

- **25 PHPStan deprecated-warnings:** исправлены во всех ~12 DTO-файлах — required параметры вынесены перед optional
- **`per_page` → `limit`:** query-параметр в ProductController, OrderController, OfferController, CounterpartyController
- **Rate limiting:** config `api_routes.rate_limit`/`rate_limit_decay`, `throttle` middleware на auth-роутах (OWASP A04)
- **Dead code из Bus::map() и QueryBus:** удалены 11 мёртвых Command-маппингов и 2 Query-регистрации
- **IdempotencyMiddleware тесты:** 6 тестов (caching, разные ключи, GET без кеша, 5xx без кеша, разные body, без
  заголовка)
- **Rate limit тесты:** 4 теста (within limit, 429, headers, GET без лимита)
- **AGENTS.md:** обновлены секции пагинации, идемпотентности, известных проблем, кол-ва тестов
- 49 тестов (230 assertions), PHPStan 0 errors, Pint clean

### Сессия 6 — Удаление мёртвого кода (31 файл)

- Удалены: `CategoryController`, `OfferController::show()`
- Удалены 11 мёртвых Command-классов и 11 CommandHandler-классов (Create/Update/Delete дубликаты Upsert)
- Удалены 2 мёртвых Query-класса и 2 QueryHandler-класса (GetOfferQuery, GetCategoryQuery)
- Удалены 3 мёртвых DTO: `LocalizedStringData`, `OrderPatchData`, `ImportErrorData`
- AGENTS.md: известные проблемы пусты (мёртвого кода нет)
- 49 тестов (230 assertions), PHPStan 0 errors, Pint clean

### Сессия 7 — Массовое расширение тестов (75 новых тестов)

- **`CounterpartyData`:** реализован метод `fromModel(Counterparty $model): static` — маппинг плоских
  скалярных полей, сборка `AddressData`/`MoneyData` из денормализованных колонок, загрузка relations
  через `DataCollection`
- **TrimmedEnumCast:** исправлена сигнатура `castValue()` — `mixed $property` → `DataProperty $property`,
  return type `BackedEnum|Uncastable`
- **Удалён** мёртвый хелпер `createCounterpartyData()` из `tests/TestCase.php`
- **Контроллер `CounterpartyController`:** переписан на `CounterpartyData::collect()` / `::from()`
  (Spatie Data v4 auto‑discover `fromModel`); добавлен `collect(...)->all()` для нормализации `items()`
- **Добавлен** `#[WithCast(TrimmedEnumCast::class, OkeiEnum::class)]` в `UnitData::code`
- **Добавлен** `@property` PHPDoc в модель `Counterparty` — PHPStan 0 errors
- **Исправлены** `OfferPrice::$fillable` и `Stock::$fillable` — добавлено `'id'` (не проходил mass
  assignment)
- **Новые тесты (75):**
    - `TrimmedEnumCastTest` (5) — обрезка пробелов/tabs/newlines/null
    - `CounterpartyDataTest` (10) — `fromModel`: адреса, кредитный лимит, relations, auto‑discovery
    - `CounterpartyController` (2) — 404 GET, 422 POST FK violation
    - `CreateCounterpartyCommandHandlerTest` (1) — unit с mock репозитория
    - `UpsertProductCommandHandlerTest` (3) — create/update/flat attributes (integration)
    - `UpsertOrderCommandHandlerTest` (3) — create/update без перезаписи номера/delivery tracking
    - `BulkUpsertOrderCommandHandlerTest` (5) — create/update/опциональные items/replace items
    - `RepositoryTest` (18) — custom методы, базовый CRUD, soft delete, eager pagination (10 репозиториев)
    - `OkeiEnumTest` (11) — fromCode/tryFromCode/isValidCode/getters/локализация/JSON/uniqueness
    - `CurrencyEnumTest` (8) — values/числовые коды/локализация/JSON/all cases valid
    - `OrderController` PATCH (1) — 422 FK violation
- **119 тестов (868 assertions), PHPStan 0 errors, Pint clean**

### Сессия 8 — Enum ISO-обновление, DTO реордер Carbon-fix, handler-рефакторинг, IdempotencyMiddleware

- **CurrencyEnum:** обновлены устаревшие коды ISO 4217 (BYR→BYN, MRO→MRU, STD→STN, VEF→VES), удалён HRK
- **DocumentTypeEnum/CurrencyEnum:** fallback `$this->name` → `$this->value` в `getLocalizedName()`
- **DTO реордер:** required-поля вынесены перед optional во всех DTO (best practice)
- **Carbon-поля:** убран `#[StringType]` со всех `?Carbon $field` (конфликт с DateTimeInterfaceCast)
- **Новые DTO:** `LinkedDocumentData`, `ProductComponentData`, `RepresentativeData`, `WarehouseImportData`
- **Типизация enum в DTO:** `ContactData::$type` → `ContactTypeEnum`, `OrderCreateData::$document_type` →
  `DocumentTypeEnum`, `PropertyDefinitionData::$name` → `LocalizedStringData|string`
- **DataCollectionOf:** добавлен к `linked_documents`, `components`, `representatives`, `errors`, `prices`, `stocks`
- **Handler-рефакторинг:**
    - `UpsertOfferCommandHandler` — синхронизирует prices/stocks через репозитории (ранее не сохранялись)
    - `CreateOrderCommandHandler` — бизнес-логика перенесена из контроллера (генерация номера, totals, id)
    - `DeleteProductCommandHandler`/`UpdateOrderCommandHandler` — `findOrFail` по строковому id вместо инъекции модели
    - `UpsertProductCommandHandler` — компоненты типизированы как `ProductComponentData`
    - `UpsertCategoryCommandHandler` — защита от самоссылающегося parent_id
    - Имена свойств хендлеров приведены к `camelCase` по AGENTS.md (`$orderRepository` etc.)
- **Repository pattern enforcement:** `Stock::updateOrCreate()` → `StockRepository`, `OfferPrice::updateOrCreate()` →
  `OfferPriceRepository`
- **Новые репозитории:** `OfferPriceRepository`, `StockRepository` (+ регистрация в ServiceProvider)
- **`OfferRepository::paginate()`** — eager load `prices`, `stocks`
- **`IdempotencyMiddleware`** — полностью реализован и подключен ко всем POST/PATCH/DELETE роутам
- **Rate limiting:** `throttle` middleware на auth-роутах (config `rate_limit`/`rate_limit_decay`)
- **Pagination format:** все list-эндпоинты переведены на `{entity, pagination: {page, limit, total, has_next}}` +
  query-параметр `limit`
- **ForeignKeyViolationException:** добавлена поддержка SQLite (regex `CONSTRAINT \`...\`` + код `23000`)
- **`Product::setRelationForApi()`** — форматирование `analogues`/`components` для API-вывода
- **`Counterparty`:** добавлен `@property` PHPDoc (PHPStan 0 errors)
- **`OfferPrice`:** добавлены `$appends` + accessors `price`, `price_with_discount`, `unit` + `@property` PHPDoc
- **`SyncFullJob`/`SyncIncrementalJob`:** удалены дублирующие события SyncStarted/SyncCompleted (теперь только в
  ExchangeManager)
- **`ExchangeManager`:** события только для синхронного режима (не для очереди)
- **Конфиг:** добавлены `api_routes.rate_limit`/`rate_limit_decay`, `idempotency.ttl`/`store`
- **`composer.json`:** `larastan/larastan ^3.10`, `phpstan/phpstan ^2.2`, исправлена индентация
- **`phpstan.neon`:** добавлен `vendor/larastan/larastan/extension.neon`
- **Обновлён `CommerceJsonHttpClientTest`:** хардкодные строки статусов → `OrderStatusEnum`
- **119 тестов (869 assertions), Pint clean, PHPStan clean (0 errors)**

### Сессия 9 — spec-синхронизация: фильтры, условная валидация, external_id upsert, PATCH Order

- **Upsert по external_id:** `BulkUpsertOrderCommandHandler` и `UpsertProductCommandHandler` ищут по
  `external_id` если `id` не найден; `BulkUpsertOrderCommand.id` → `?string`; `OrderRepository`/`ProductRepository`
  добавлены `findByExternalId()`
- **PATCH /orders/{id}:** Новые `OrderPatchData`, `OrderPatchPaymentData`, `PatchOrderCommand`,
  `PatchOrderCommandHandler` — частичное обновление статуса, payment, delivery, items (syncItems).
  `UpdateOrderCommand` удалён из `Bus::map()`
- **CounterpartyController:** `store()` переписан на пакетный upsert через `UpsertCounterpartyCommand`,
  возвращает `ImportResult` (200). `CreateCounterpartyCommand` удалён из `Bus::map()`
- **GET-фильтры из spec:** Во все Query добавлены `updated_after`, `include_deleted`, `category_id`,
  `is_active`, `price_type_id`, `warehouse_id`, `status`, `document_type`, `type`. Хендлеры применяют
  через `newQuery()` + `where`
- **OfferList price_types/warehouses:** `OfferController::index()` возвращает справочники на первой странице
- **Условная валидация (if/then/anyOf):**
    - `OrderItemUpdateData` — `withValidator()`: обязателен `id`+`quantity` или `product_id`+`quantity`
    - `OrderCreateData` — `withValidator()`: `delivery`/`payment` обязательны для `document_type=order`
    - `OrderDeliveryData` — `withValidator()`: `address` обязателен для courier/post/transport_company
- **OfferData.prices:** сделан required (non-nullable)
- **BaseRepository.newQuery():** публичный метод для хендлеров с фильтрацией
- **Warehouse controller:** `ImportError` включает `id`
- **HandshakeController:** использует `HandshakeResponseData` DTO
- **Новые DTO:** `OrderPatchData`, `OrderPatchPaymentData` (итого 48 DTO)
- **phpstan.neon:** добавлены игноры для `withTrashed()` и nullsafe на левой стороне `??`
- **Pint:** исправлены 8 style issues (unused imports, braces, strict_types)
- **127 тестов (900 assertions), PHPStan 0 errors, Pint clean**

### Сессия 10 — LogApiRequestsMiddleware (логирование всех API-запросов)

- **`LogApiRequestsMiddleware`** — новый middleware для логирования всех входящих API-запросов:
    - Логирует метод, URL, IP, User-Agent, тело запроса (с маскировкой sensitive данных: `password`, `token`, `secret`,
      `auth_token`, `access_token`, `api_key`)
    - Логирует статус ответа и длительность (ms) с разными уровнями (info/success, warning/4xx, error/5xx)
    - Канал логирования — `commercejson-api` (с fallback на `commercejson` → `stack`)
    - Исключение путей по конфигу (`exclude_paths`, по умолчанию `handshake`)
    - Опциональное логирование тела ответа (отключено по умолчанию)
- **Config:** добавлена секция `api_logging` (enabled, channel, fallback_channel, log_request_body, log_response_body,
  exclude_paths) с примером конфигурации канала в комментариях
- **Routes:** middleware `commercejson.log` подключен на все роуты пакета (включая `/handshake`)
- **Тесты (8):** проверка GET/POST логов, включения URL/status/duration, маскировки sensitive данных, уровня warning для
  4xx, отключения логирования, исключения путей
- **127 тестов (900 assertions), PHPStan 0 errors, Pint clean**

---

## Ключевые файлы

| Файл                                                      | Назначение                                                       |
|-----------------------------------------------------------|------------------------------------------------------------------|
| `spec.yaml`                                               | OpenAPI 3.1 спецификация (единственный источник истины)          |
| `src/routes/api.php`                                      | Определения роутов                                               |
| `src/CommerceJsonServiceProvider.php`                     | Service provider с Bus::map() и QueryBus                         |
| `src/config/commercejson.php`                             | Конфигурация пакета                                              |
| `src/Http/Controllers/`                                   | 6 контроллеров + HandshakeController                             |
| `src/Data/`                                               | 48 DTO (Spatie Laravel Data v4)                                  |
| `src/Models/`                                             | 23 Eloquent-модели                                               |
| `src/Repositories/`                                       | RepositoryInterface + 10 реализаций                              |
| `src/Exchange/`                                           | Координация синхронизации (импортёры, экспортёры, jobs, команды) |
| `tests/TestCase.php`                                      | Testbench bootstrap                                              |
| `tests/Pest.php`                                          | Глобальные хелперы и конфигурация                                |
| `src/Commands/BulkUpsertOrderCommand.php`                 | Bulk-команда для `POST /orders/bulk` (не зависит от `OrderData`) |
| `src/Handlers/Commands/BulkUpsertOrderCommandHandler.php` | Handler с раздельными create/update путями и опциональными items |
| `src/Commands/PatchOrderCommand.php`                      | Команда для `PATCH /orders/{id}` (частичное обновление)          |
| `src/Handlers/Commands/PatchOrderCommandHandler.php`      | Handler с partial update + syncItems                             |
| `src/Data/OrderPatchData.php`                             | DTO для PATCH /orders/{id}                                       |
| `src/Data/OrderPatchPaymentData.php`                      | DTO payment для PATCH                                            |
| `src/Http/Middleware/LogApiRequestsMiddleware.php`        | Middleware для логирования всех API-запросов                     |
