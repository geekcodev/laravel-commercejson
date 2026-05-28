# Аудит безопасности и качества кода Laravel CommerceJSON Package

## Общая информация

- **Пакет:** geekcodev/laravel-commercejson
- **Версия:** 1.0.0
- **Дата аудита:** 2026-04-28 (актуализирован: 2026-05-27)
- **Статус:** Работа продолжается

## Статистика проекта

| Компонент        | Данные аудита | Актуально |
|------------------|---------------|-----------|
| PHP-файлов       | 184           | 297       |
| Модели           | 23            | 23 ✅      |
| Data-классы      | 49            | 49 ✅      |
| Enum             | 11            | 11 ✅      |
| Миграции         | 24            | 24 ✅      |
| Фабрики          | 17            | 17 ✅      |
| Сидеры           | 7             | 8         |
| Сервисы          | 6             | 6 ✅       |
| Jobs             | 7             | 7 ✅       |
| Console Commands | 7             | 7 ✅       |
| Events           | 11            | 11 ✅      |
| Exceptions       | 6             | 1         |
| **Тестов**       | **82**        | **37**    |

> **Примечание:** Количество тестов снизилось с 82 до 37 после миграции со старых PHPUnit-тестов на Pest PHP (Сессия 1).
> 37 тестов, 180 assertions — все зелёные. 1 warning (code coverage), 1 deprecation (PHPUnit).

---

## Проверка безопасности

### ✅ Критические проверки

#### 1. SQL-инъекции

**Статус:** Защищено

- Все запросы используют Eloquent ORM с параметризованными запросами
- UUID генерируются через `Str::uuid()`
- Foreign keys с правильными ограничениями

#### 2. XSS-атаки

**Статус:** Защищено

- Данные из API проходят валидацию через Data-классы
- Blade-шаблоны автоматически экранируют вывод
- HTML не сохраняется в базу данных

#### 3. CSRF-атаки

**Статус:** Не применимо

- Package не использует веб-формы
- API-запросы используют Bearer/Basic авторизацию
- Рекомендуется использовать Laravel CSRF protection для форм приложения

#### 4. Авторизация и аутентификация

**Статус:** Реализовано корректно

- Токены хранятся в .env (не в коде)
- Поддержка Bearer, Basic, Session токенов
- Автоматическая повторная авторизация при 401 ошибке
- `/handshake` — единственный эндпоинт без auth middleware (по спецификации)

#### 5. Чувствительные данные

**Статус:** Защищено

- API-токены не логируются по умолчанию
- Метод `sanitizeHeaders()` скрывает Authorization заголовки
- Passwords не сохраняются в базе

### ⚠️ Проверка кода

#### 1. Валидация входных данных

**Статус:** Реализовано

```php
// Data-классы с атрибутами валидации
#[Required, Uuid, MaxLength(255)]
public string $id;

#[Required, Decimal]
public string $amount;
```

#### 2. Обработка ошибок

**Статус:** Частично

Единый класс `SyncException` — всё. Остальные исключения (`AuthenticationException`, `ValidationException`,
`BusinessException`, `RateLimitException`) не существуют как отдельные классы. Обработка ошибок встроена в HTTP-клиент.

**TODO:** Создать иерархию исключений:

```php
class CommerceJsonException extends \RuntimeException {}
class AuthenticationException extends CommerceJsonException {}
class ValidationException extends CommerceJsonException {}
class BusinessException extends CommerceJsonException {}
class RateLimitException extends CommerceJsonException {}
```

#### 3. Логирование

**Статус:** Реализовано

- Отдельный канал логирования
- Чувствительные данные скрываются
- Настройка уровня логирования через config

#### 4. Идемпотентность

**Статус:** Реализована **частично** (только на стороне HTTP-клиента)

- Исходящие запросы к ERP поддерживают заголовок `X-Idempotency-Key`
- **Серверное кеширование** идемпотентных ключей — **не реализовано** (TODO)
- При повторном `POST` с тем же ключом дубликаты НЕ предотвращаются

**TODO:** Реализовать серверное кеширование `X-Idempotency-Key`:

```php
// Middleware или Handler
$cacheKey = 'idempotency:' . $request->header('X-Idempotency-Key');
if ($cached = Cache::get($cacheKey)) {
    return $cached;
}
// ... process request ...
Cache::put($cacheKey, $response, now()->addHours(24));
```

#### 5. Retry логика

**Статус:** Реализовано

- Экспоненциальная задержка между попытками
- Максимум 3 попытки (настраивается)
- Только для 5xx и 429 ошибок

### ⚠️ Рекомендации по безопасности

#### 1. Rate Limiting

**Текущее состояние:** Не реализован

**Рекомендация:** Добавить локальный rate limiting для входящих и исходящих запросов:

```php
// В config/commercejson.php
'rate_limit' => [
    'requests_per_minute' => 60,
    'burst_limit' => 10,
],
```

#### 2. Шифрование чувствительных данных

**Текущее состояние:** Данные не шифруются

**Рекомендация:** Для production рассмотреть шифрование:

```php
// В модели Counterparty
protected $casts = [
    'inn' => 'encrypted',
    'kpp' => 'encrypted',
];
```

#### 3. Аудит действий

**Текущее состояние:** Базовое логирование

**Рекомендация:** Добавить аудит критических операций:

```php
event(new OrderAudit('created', $order, auth()->user()));
```

---

## Проверка качества кода

### ✅ Архитектура

#### 1. Разделение ответственности

**Статус:** Соответствует SOLID

- Сервисы для бизнес-логики
- Models для работы с БД
- Data-классы для DTO
- Jobs для очередей
- **CQRS** — Commands через Laravel Bus, Queries через QueryBus

**TODO:** Написать unit-тесты на CommandHandler и QueryHandler напрямую (без моков bus)

#### 2. Внедрение зависимостей

**Статус:** Реализовано

```php
class ProductService
{
    public function __construct(
        protected CommerceJsonConnector $connector,
        protected ProductMapper $mapper
    ) {}
}
```

#### 3. Интерфейсы

**Статус:** Отсутствуют

**Рекомендация:** Создать интерфейсы для сервисов:

```php
interface ProductServiceInterface
{
    public function getProducts(): ProductListData;
    public function getProduct(string $id): ProductData;
}
```

### ⚠️ Тестирование

#### 1. Покрытие тестами

**Статус:** ~45% (снижение с 82 тестов после миграции на Pest)

| Слой             | Покрытие                                                   | Статус |
|------------------|------------------------------------------------------------|--------|
| Controllers      | 100% (через моки bus)                                      | ✅      |
| HTTP Client      | 1 unit-тест                                                | ⚠️     |
| **Handlers**     | **0%** — нет тестов ни на один CommandHandler/QueryHandler | ❌      |
| **Queries**      | **0%** — нет тестов                                        | ❌      |
| **Repositories** | **0%** — нет тестов                                        | ❌      |
| **Services**     | **0%** — нет unit-тестов                                   | ❌      |
| **Exchange**     | **0%** — нет тестов импортёров/экспортёров                 | ❌      |
| **Jobs**         | **0%** — нет тестов                                        | ❌      |

**TODO:** Добавить тесты:

- `tests/Unit/Handlers/` — `CreateProductCommandHandlerTest`, `UpdateOrderCommandHandlerTest`, etc.
- `tests/Unit/Repositories/` — `ProductRepositoryTest`, `OrderRepositoryTest`, etc.
- `tests/Unit/Queries/` — `GetProductsQueryTest`, `GetOrdersQueryTest`, etc.
- `tests/Feature/Http/Controllers/` — расширить существующие (тесты ошибок 400, 404, 422)

#### 2. CI/CD

**Статус:** Реализовано

- GitHub Actions для тестов (`.github/workflows/run-tests.yml`)
- PHPStan статический анализ (`.github/workflows/phpstan.yml`)
- Laravel Pint код стайл (`.github/workflows/pint.yml`)

### ✅ Документация

#### 1. README

**Статус:** Полный

- Установка
- Использование
- Конфигурация
- Примеры кода

#### 2. CHANGELOG

**Статус:** Ведётся

- Версионирование по SemVer
- Описание изменений

#### 3. CONTRIBUTING

**Статус:** Создан

- Правила для контрибьюторов
- Процесс pull request

---

## Найденные проблемы

### Критические (0)

Критических проблем не обнаружено.

### Средние (2)

#### 1. State machine для заказов не реализована

**Приоритет:** Высокий

**Проблема:** `PATCH /orders/{id}` не валидирует переходы статусов. Любой переход возможен:
`new → shipped`, `delivered → new`, `canceled → processing`.

**Требование спецификации:** Статусы заказов — `OrderStatus` enum с валидными переходами:
`new → processing → shipped → delivered`, с возможностью `canceled` из `new` и `processing`.

**Решение:** Добавить валидацию:

```php
class OrderStatusMachine
{
    private array $transitions = [
        'new' => ['processing', 'canceled'],
        'processing' => ['shipped', 'canceled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'canceled' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, $this->transitions[$from] ?? []);
    }
}
```

**Тесты:** `tests/Unit/OrderStatusMachineTest.php`, `tests/Feature/Http/Controllers/OrderTest.php` (422 BUSINESS_ERROR)

#### 2. Bulk-эндпоинты не соответствуют спецификации

**Приоритет:** Средний

**Проблема:** `POST /catalog/products`, `POST /offers`, `POST /counterparties` принимают **один** объект и возвращают
201.
Спецификация требует **массив** объектов и ответ 200 + `ImportResult`.

**Затронутые файлы:**

- `ProductImportData` — существует, но **не используется** контроллером
- `OfferImportData` — существует, но **не используется** контроллером
- `CounterpartyListData` — **не существует**

**Решение:** Переписать `store()` в `ProductController`, `OfferController`, `CounterpartyController` на bulk-формат.

### Низкие (5)

#### 1. Fulltext индексы закомментированы

**Файл:** `src/database/migrations/2026_01_01_000009_create_products_table.php`

**Проблема:** Fulltext поиск не работает на SQLite

**Решение:** Раскомментировать для MySQL в production:

```php
$table->fullText(['name', 'short_description'], 'products_name_description_fulltext');
```

#### 2. Missing interface types

**Файл:** Сервисы

**Проблема:** Нет интерфейсов для сервисов

**Решение:** Создать интерфейсы для моков в тестах

#### 3. Жёстко закодированные значения

**Файл:** Различные

**Проблема:** Некоторые значения захардкожены

**Пример:**

```php
'tax_rate' => 20.00, // Хардкод
```

**Решение:** Вынести в конфигурацию

#### 4. Pagination не соответствует спецификации

**Проблема:** Все list-эндпоинты возвращают `{data, meta}`, спецификация требует `{entity_name, pagination}`

**Решение:**

```php
// Было:
['data' => $items->values()->toArray(), 'meta' => ['current_page' => ..., 'per_page' => ..., 'total' => ...]]
// Стало:
['products' => $items->values()->toArray(), 'pagination' => ['page' => ..., 'limit' => ..., 'total' => ..., 'has_next' => ...]]
```

#### 5. Delta-sync не реализован

**Проблема:** `GET`-эндпоинты не поддерживают `updated_after`. Только `/warehouses` поддерживает `include_deleted`.

**Решение:** Добавить параметры `updated_after` и `include_deleted` во все Query и репозитории.

---

## План развития (TODO)

### Фаза 1 — Безопасность и стабильность

- [ ] Создать иерархию исключений (`AuthenticationException`, `ValidationException`, `BusinessException`,
  `RateLimitException`)
- [ ] Реализовать серверное кеширование `X-Idempotency-Key`
- [ ] Добавить rate limiting middleware
- [ ] Вынести хардкод в конфигурацию

### Фаза 2 — State machine и тесты Handler'ов

- [ ] Реализовать `OrderStatusMachine` с валидацией переходов
- [ ] Тесты: `tests/Unit/OrderStatusMachineTest.php`
- [ ] Тесты: `tests/Unit/Handlers/CreateProductCommandHandlerTest.php`
- [ ] Тесты: `tests/Unit/Handlers/UpdateOrderCommandHandlerTest.php`
- [ ] Тесты: `tests/Unit/Handlers/UpsertOrderCommandHandlerTest.php`
- [ ] Тесты: `tests/Unit/Handlers/DeleteProductCommandHandlerTest.php`

### Фаза 3 — Bulk-эндпоинты и pagination

- [ ] Переписать `POST /catalog/products` на bulk (использовать `ProductImportData`)
- [ ] Переписать `POST /offers` на bulk (использовать `OfferImportData`)
- [ ] Переписать `POST /counterparties` на bulk (создать `CounterpartyListData`)
- [ ] Перевести все list-эндпоинты на spec-формат pagination (`{entity, pagination}` вместо `{data, meta}`)
- [ ] Удалить мёртвый код: `CategoryController`, `OfferController::show()`, неиспользуемые команды/квери

### Фаза 4 — Delta-sync и покрытие репозиториев

- [ ] Добавить `updated_after` в `GetProductsQuery`, `GetOffersQuery`, `GetOrdersQuery`, `GetCounterpartiesQuery`
- [ ] Добавить `include_deleted` во все list-запросы (кроме warehouses — уже есть)
- [ ] Тесты: `tests/Unit/Repositories/ProductRepositoryTest.php`
- [ ] Тесты: `tests/Unit/Repositories/OrderRepositoryTest.php`
- [ ] Тесты: `tests/Unit/Repositories/CounterpartyRepositoryTest.php`
- [ ] Тесты: `tests/Unit/Queries/GetProductsQueryTest.php`

### Фаза 5 — Интеграционные тесты и полнота

- [ ] Тесты: `tests/Feature/Exchange/OrderImporterTest.php`
- [ ] Тесты: `tests/Feature/Exchange/ProductImporterTest.php`
- [ ] Тесты: `tests/Feature/Jobs/ImportProductsJobTest.php`
- [ ] Тесты: сервисы (`ProductServiceTest`, `OrderServiceTest`)
- [ ] Создать интерфейсы для сервисов (`ProductServiceInterface`, `OrderServiceInterface`)

---

## Итоговая оценка

| Категория     | Оценка | Статус                                                |
|---------------|--------|-------------------------------------------------------|
| Безопасность  | 90%    | ⚠️ Хорошо (идемпотентность не полная)                 |
| Качество кода | 75%    | ⚠️ Удовлетворительно (мёртвый код, нет state machine) |
| Тестирование  | 40%    | ❌ Требует работы (нет handler/repository тестов)      |
| Документация  | 95%    | ✅ Отлично                                             |
| Архитектура   | 80%    | ⚠️ Хорошо (CQRS, bulk не везде)                       |

**Статус:** Работа продолжается. Приоритет — state machine, тесты handler'ов и репозиториев, bulk-эндпоинты.

---

## Ключевые файлы для доработки

```
src/
├── Http/Controllers/
│   ├── ProductController.php       # → bulk-формат
│   ├── OfferController.php         # → bulk-формат
│   ├── CounterpartyController.php  # → bulk-формат
│   └── OrderController.php         # → state machine
├── Exceptions/
│   └── SyncException.php           # → иерархия исключений
├── Data/
│   ├── ProductImportData.php       # существует, не используется
│   ├── OfferImportData.php         # существует, не используется
│   └── CounterpartyListData.php    # не существует
├── Exchange/
│   └── *                           # нет тестов
└── database/migrations/
    └── *fulltext.php               # закомментировано
tests/
├── Unit/
│   ├── Handlers/                   # не существует
│   ├── Repositories/               # не существует
│   └── Queries/                    # не существует
└── Feature/
    └── Exchange/                   # не существует
```

---

## Приложения

### A. Ссылки

- [OpenAPI spec](openapi-v1.0.8.yaml) — единственный источник истины
- [Тестовая спецификация](TEST_SUITE_PHP_LARAVEL.md) — тест-план по OpenAPI (требует актуализации под код)
- [AGENTS.md](AGENTS.md) — документация архитектуры
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security](https://laravel.com/docs/security)
