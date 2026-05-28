# Журнал изменений

Все значимые изменения в `laravel-commercejson` будут задокументированы в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/),
проект следует [Семантическому версионированию](https://semver.org/lang/ru/).

## [Невыпущенное]

### Изменено
- Роуты переписаны под OpenAPI спецификацию v1.0.8
- CommandBus заменён с самописного на Laravel `Bus::map()`
- Все DTO переведены на snake_case (убраны `#[MapName(SnakeCaseMapper::class)]`)
- Тесты мигрированы с PHPUnit на Pest PHP 3.8
- QueryBusInterface вынесен из `src/Bus/` в `src/Bus/` (удалён CommandBus)
- PHPStan конфигурация: добавлены в анализ Data/Handlers/Queries/Repositories/Commands

### Добавлено
- `ClassifierController` — GET + POST `/catalog/classifier`
- `WarehouseController` — GET + POST `/warehouses`
- `GetWarehousesQuery`, `GetPriceTypesQuery` + handlers
- `RepositoryInterface::all()` и `RepositoryInterface::updateOrCreate()`
- 37 Pest-тестов (180 assertions) — Feature-тесты на все контроллеры + Unit-тест HTTP-клиента

### Исправлено
- `SeoMetaData` — nullable типы (приводили к TypeError)
- `CategoryData::is_active` — default `false` → `true` (по спецификации)
- `ImportResultData::warnings` — `array` → `?array` (TypeError при null)
- `CommerceJsonHttpClientTest` — 12 методов без префикса `test_` (PHPUnit 11 не находил)
- `test()->instance()` → `app()->instance()` для совместимости с IDE
- `ProductService::getProducts()` — удалён мёртвый `@param $filters` из docblock

### Удалено
- Старые PHPUnit-тесты (82 теста → 37 Pest-тестов)
- `src/Bus/CommandBus.php`, `src/Bus/CommandBusInterface.php` (заменён на Laravel Bus)
- `src/Bus/QueryBusInterface.php`, `src/Bus/QueryBus.php` — восстановлены (живой QueryBus)
- Мёртвый код: `UpdateProductCommand`, `UpdateCounterpartyCommand`, `DeleteOrderCommand`, `DeleteOfferCommand`
- `CategoryController` (нет роутов; категории через `/catalog/classifier`)
- `OfferController::show()` (нет роута `GET /offers/{id}`)

## [1.0.0] — 2026-04-24

### Добавлено

#### HTTP-клиент
- CommerceJsonConnector с логикой повторных запросов
- Поддержка идемпотентности (X-Idempotency-Key)
- Поддержка пагинации
- Логирование запросов
- Аутентификация (Bearer, Basic, Session)

#### Модели (23)
- Category, PriceType, Warehouse
- PropertyDefinition, PropertyValue
- Counterparty, Contact, BankAccount, Representative
- Product, ProductVariant, ProductImage
- Offer, OfferPrice, Stock
- Order, OrderItem, OrderItemTax
- StatusHistoryEntry
- CustomAttribute, Signatory
- ProductAnalogue, ProductComponent

#### Data-классы (49)
- Полные Data-классы Spatie для всех сущностей API
- Атрибуты валидации
- Приведение типов

#### Перечисления (11)
- OrderStatusEnum, PaymentStatusEnum, PaymentMethodEnum
- DeliveryMethodEnum, DocumentTypeEnum, PartyRoleEnum
- CounterpartyTypeEnum, ContactTypeEnum
- CurrencyEnum, PropertyTypeEnum, OkeiEnum

#### Сервисы (6)
- ProductService — CRUD и синхронизация товаров
- OrderService — управление заказами
- OfferService — цены и остатки
- ClassifierService — категории и свойства
- WarehouseService — управление складами
- CounterpartyService — управление контрагентами

#### Очереди заданий (7)
- ImportProductsJob, ImportOffersJob, ImportOrdersJob
- ImportClassifierJob
- ExportOrdersJob
- SyncFullJob, SyncIncrementalJob

#### Console-команды (7)
- commercejson:handshake
- commercejson:import-classifier
- commercejson:import-products
- commercejson:import-offers
- commercejson:import-orders
- commercejson:export-orders
- commercejson:sync

#### События (11)
- ClassifierImported, ProductsImported, OffersImported
- OrderImported, OrderCreated, OrderUpdated, OrderExported
- ProductDeactivated
- SyncStarted, SyncCompleted, SyncFailed

#### База данных
- 24 оптимизированных миграции с индексами
- 17 фабрик для тестирования
- 7 сидеров для тестовых данных

#### Тестирование
- 82 unit и feature теста
- Тестовые фикстуры и хелперы
- PHPUnit конфигурация

#### Документация
- README.md
- CONTRIBUTING.md
- CHANGELOG.md

### Безопасность
- Валидация входных данных через Data-классы
- Защита от SQL-инъекций через Eloquent
- Защита от XSS через экранирование

### Производительность
- Оптимизированные индексы базы данных
- Композитные индексы для частых запросов
- Поддержка ленивой загрузки
- Очереди заданий для асинхронных операций

## Ссылки

- [Packagist](https://packagist.org/packages/geekcodev/laravel-commercejson)
- [GitHub](https://github.com/geekcodev/laravel-commercejson)
