# Отчёты о покрытии кода (Code Coverage)

## Генерация отчётов

### HTML отчёт

```bash
composer test:coverage
```

Отчёт будет сохранён в директорию `coverage/`.

Откройте `coverage/index.html` в браузере для просмотра.

### Текстовый отчёт

```bash
composer test:coverage-text
```

Вывод в терминал с процентом покрытия по каждому файлу.

### Clover XML

```bash
composer test:coverage
```

Файл `coverage.xml` будет создан для интеграции с CI/CD (Codecov, Coveralls).

---

## Настройка покрытия

### phpunit.xml

```xml
<coverage includeUncoveredFiles="true"
          pathCoverage="false"
          ignoreDeprecatedCodeUnits="true"
          disableCodeCoverageIgnore="true">
    <report>
        <html outputDirectory="coverage" lowUpperBound="50" highLowerBound="80"/>
        <clover outputFile="coverage.xml"/>
        <text outputFile="php://stdout" showUncoveredFiles="true"/>
    </report>
</coverage>
```

### Исключения из покрытия

Не покрываются тестами:

- `src/Data/` — DTO классы (автоматическая генерация)
- `src/Enums/` — Перечисления (нет логики)
- `src/Support/` — Вспомогательные классы

---

## Требования к покрытию

| Тип кода | Минимальное покрытие |
|----------|---------------------|
| Services | 85% |
| Models | 80% |
| Jobs | 80% |
| Commands | 75% |
| HTTP Client | 85% |
| **Общее** | **80%** |

---

## Интерпретация отчёта

### Цвета в HTML отчёте

- 🟢 **Зелёный** (80-100%) — Хорошее покрытие
- 🟡 **Жёлтый** (50-79%) — Требуется улучшение
- 🔴 **Красный** (0-49%) — Критически низкое покрытие

### Метрики

- **Lines** — Покрытие строк кода
- **Methods** — Покрытие методов
- **Classes** — Покрытие классов

---

## Пример отчёта

```
Code Coverage Report
  Generated: 2026-04-28

Summary:
  Classes:  82.35% (28/34)
  Methods:  81.42% (142/174)
  Lines:    80.95% (1842/2275)

Coverage by Component:
  Http/Client:           85.2%
  Services:              82.1%
  Models:                78.5%
  Jobs:                  80.3%
  Console/Commands:      75.8%
  Events:                90.0%
  Exceptions:            95.0%
```

---

## Интеграция с CI/CD

### GitHub Actions

```yaml
- name: Generate Code Coverage
  run: composer test:coverage

- name: Upload to Codecov
  uses: codecov/codecov-action@v4
  with:
    file: ./coverage.xml
    fail_ci_if_error: false
```

### Codecov

После загрузки `coverage.xml` на [Codecov](https://codecov.io), вы сможете:

- Отслеживать покрытие по коммитам
- Получать уведомления о снижении покрытия
- Просматривать coverage в pull requests

---

## Требования Xdebug

Для генерации coverage требуется Xdebug:

```bash
# Проверка установки
php -m | grep xdebug

# Установка (если не установлен)
pecl install xdebug
docker-compose exec php docker-php-ext-enable xdebug
```

### Настройка Xdebug

```ini
; xdebug.ini
xdebug.mode=coverage
xdebug.start_with_request=yes
xdebug.output_dir=/tmp/xdebug
```

---

## Полезные команды

```bash
# Покрытие для конкретного теста
php vendor/bin/phpunit --coverage-html=coverage tests/Unit/Http/CommerceJsonConnectorTest.php

# Покрытие без кэша
php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html=coverage

# Только coverage для Services
php vendor/bin/phpunit --coverage-html=coverage --filter=Service
```

---

## Анализ покрытия

### Низкое покрытие (< 50%)

**Проблемы:**
- Критические части кода не тестируются
- Риск регрессии при изменениях

**Решение:**
- Написать тесты для непокрытых методов
- Рефакторинг сложного кода

### Среднее покрытие (50-79%)

**Проблемы:**
- Некоторые сценарии не покрыты
- Возможны пропущенные edge cases

**Решение:**
- Добавить тесты для граничных случаев
- Покрыть обработку ошибок

### Высокое покрытие (80-100%)

**Статус:** ✅ Отлично

**Рекомендации:**
- Поддерживать текущий уровень
- Следить за новыми файлами

---

## Ссылки

- [PHPUnit Code Coverage](https://phpunit.de/manual/current/en/code-coverage.html)
- [Xdebug Documentation](https://xdebug.org/docs/code_coverage)
- [Codecov](https://codecov.io/)
