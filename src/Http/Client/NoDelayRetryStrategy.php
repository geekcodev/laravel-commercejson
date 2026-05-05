<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client;

use Throwable;

/**
 * Стратегия без задержки (для тестов)
 *
 * Используется в тестах, чтобы не ждать реальные задержки
 */
class NoDelayRetryStrategy implements RetryStrategyInterface
{
    public function __construct(
        protected int $maxAttempts = 3,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * {@inheritDoc}
     */
    public function getDelayMs(int $attempt, ?Throwable $exception = null): int
    {
        return 0;
    }
}
