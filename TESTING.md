## Testing & load data

Этот документ описывает, как быстро получить **большой объём тестовых данных** (категории/товары/офферы/цены/остатки/свойства) для:

- нагрузочного тестирования
- анализа SQL-запросов и индексов (EXPLAIN, slow query log, профилирование)
- ручной проверки UI/фильтров/поиска

### Предпосылки (Docker Compose)

В репозитории используется сервис `php` (см. `docker-compose.yml`). Все команды ниже запускаются **через контейнер**:

```bash
docker compose exec php php -v
docker compose exec php composer -V
```

### Запуск миграций

```bash
docker compose exec php php artisan migrate
```

### Сидинг (обычный)

Запускает сидеры с небольшим объёмом данных:

```bash
docker compose exec php php artisan db:seed --class="GeekCo\\CommerceJson\\Database\\Seeders\\DatabaseSeeder"
```

Важно для PostgreSQL: базовые сидеры используют **валидные UUID** (вместо строковых "псевдо-id"), поэтому работают одинаково на MySQL/SQLite/PostgreSQL.

### Сидинг (нагрузочный профиль)

В пакете есть “load test” профиль. Значения читаются через `config('commercejson.seeding.*')`
(то есть `env()` используется только в конфиге, что корректно для `config:cache`).

```bash
docker compose exec \
  -e COMMERCEJSON_SEED_PROFILE=load \
  php php artisan db:seed --class="GeekCo\\CommerceJson\\Database\\Seeders\\DatabaseSeeder"
```

Нагрузочный профиль генерирует (в больших объёмах):

- `categories` (иерархия)
- `products`
- `product_variants` (для части товаров)
- `offers` (на товар и на каждый вариант)
- `offer_prices` (несколько типов цен и price tiers по min_quantity)
- `stocks` (остатки по складам)
- `product_images`
- `property_definitions` + `property_values` (на товары и варианты)

### Переменные окружения (load profile)

Все переменные опциональны — указаны значения по умолчанию.

- **COMMERCEJSON_SEED_PROFILE**: `load` чтобы включить нагрузочный сидинг (см. `config/commercejson.php`)
- **COMMERCEJSON_SEED_SEED**: `1234` — seed для Faker (детерминирует данные)
- **COMMERCEJSON_SEED_RUN_KEY**: по умолчанию вычисляется из seed — префикс, чтобы удобно пере-запускать сидер без конфликтов уникальности (попадает в `code`/`external_id`)
- **COMMERCEJSON_SEED_CHUNK**: `1000` — размер пачек для bulk insert

#### Объёмы

- **COMMERCEJSON_SEED_PRODUCTS**: `20000` — количество товаров
- **COMMERCEJSON_SEED_EXTRA_CATEGORIES**: `5000` — дополнительные категории (к существующим)
- **COMMERCEJSON_SEED_EXTRA_COUNTERPARTIES**: `200` — доп. контрагенты (используются как производители/владельцы бренда)

#### Варианты/офферы

- **COMMERCEJSON_SEED_VARIANTS_RATIO**: `0.35` — доля товаров, у которых будут варианты
- **COMMERCEJSON_SEED_VARIANTS_MIN**: `2` — минимум вариантов на товар
- **COMMERCEJSON_SEED_VARIANTS_MAX**: `5` — максимум вариантов на товар

#### Цены/остатки

- **COMMERCEJSON_SEED_PRICE_TIERS**: `2`
  - `1` — только `min_quantity=1`
  - `2` — добавляет tier с `min_quantity=10`
- **COMMERCEJSON_SEED_STOCKS_PER_OFFER**: `1`
  - `0` — не создавать остатки
  - `1` — остаток только на складе по умолчанию
  - `N>1` — остатки на нескольких складах

#### Свойства

- **COMMERCEJSON_SEED_PROPERTIES**: `40` — количество `property_definitions`
- **COMMERCEJSON_SEED_PRODUCT_PROPERTIES**: `6` — сколько свойств назначать на товар
- **COMMERCEJSON_SEED_VARIANT_PROPERTIES**: `3` — сколько свойств назначать на вариант

### Примеры пресетов

#### Быстрый smoke (проверить что всё работает)

```bash
docker compose exec \
  -e COMMERCEJSON_SEED_PROFILE=load \
  -e COMMERCEJSON_SEED_PRODUCTS=2000 \
  -e COMMERCEJSON_SEED_EXTRA_CATEGORIES=500 \
  -e COMMERCEJSON_SEED_PROPERTIES=20 \
  -e COMMERCEJSON_SEED_CHUNK=500 \
  php php artisan db:seed --class="GeekCo\\CommerceJson\\Database\\Seeders\\DatabaseSeeder"
```

#### Средний (для EXPLAIN/индексов)

```bash
docker compose exec \
  -e COMMERCEJSON_SEED_PROFILE=load \
  -e COMMERCEJSON_SEED_PRODUCTS=50000 \
  -e COMMERCEJSON_SEED_EXTRA_CATEGORIES=10000 \
  -e COMMERCEJSON_SEED_VARIANTS_RATIO=0.4 \
  -e COMMERCEJSON_SEED_PRICE_TIERS=2 \
  -e COMMERCEJSON_SEED_STOCKS_PER_OFFER=2 \
  -e COMMERCEJSON_SEED_CHUNK=2000 \
  php php artisan db:seed --class="GeekCo\\CommerceJson\\Database\\Seeders\\DatabaseSeeder"
```

#### Большой (нагрузочное тестирование)

```bash
docker compose exec \
  -e COMMERCEJSON_SEED_PROFILE=load \
  -e COMMERCEJSON_SEED_PRODUCTS=150000 \
  -e COMMERCEJSON_SEED_EXTRA_CATEGORIES=30000 \
  -e COMMERCEJSON_SEED_PROPERTIES=80 \
  -e COMMERCEJSON_SEED_PRODUCT_PROPERTIES=8 \
  -e COMMERCEJSON_SEED_VARIANT_PROPERTIES=4 \
  -e COMMERCEJSON_SEED_VARIANTS_RATIO=0.5 \
  -e COMMERCEJSON_SEED_STOCKS_PER_OFFER=3 \
  -e COMMERCEJSON_SEED_CHUNK=3000 \
  php php artisan db:seed --class="GeekCo\\CommerceJson\\Database\\Seeders\\DatabaseSeeder"
```

### Примечания по производительности

- Чем больше `COMMERCEJSON_SEED_CHUNK`, тем меньше запросов к БД, но выше потребление памяти.
- Для больших объёмов рекомендуется запускать сидер на “чистой” БД (truncate/refresh), чтобы не копить данные бесконечно.
- Если вы хотите пере-генерировать данные поверх существующих, используйте другой `COMMERCEJSON_SEED_RUN_KEY` (или другой `COMMERCEJSON_SEED_SEED`).

