<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client;

use Throwable;

/**
 * Интерфейс стратегии повторных попыток
 */
interface RetryStrategyInterface
{
    /**
     * Получить максимальное количество попыток
     */
    public function getMaxAttempts(): int;

    /**
     * Получить задержку в миллисекундах перед следующей попыткой
     *
     * @param  int  $attempt  Номер текущей попытки (начиная с 1)
     */
    public function getDelayMs(int $attempt, ?Throwable $exception = null): int;
}
