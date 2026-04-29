# Журнал изменений

Все значимые изменения в `laravel-commercejson` будут задокументированы в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/),
проект следует [Семантическому версионированию](https://semver.org/lang/ru/).

## [Невыпущенное]

### Добавлено
- Подготовка к начальному выпуску
- GitHub Actions workflows
- Полная документация

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
- TESTS_README.md
- PACKAGE_ARCHITECTURE.md
- DATABASE_OPTIMIZATION.md

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
