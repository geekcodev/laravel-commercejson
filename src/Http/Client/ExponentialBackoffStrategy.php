<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client;

use Throwable;

/**
 * Стратегия экспоненциальной задержки (exponential backoff)
 *
 * Задержка рассчитывается по формуле: baseDelay * (2 ^ attempt)
 * Например: 2s, 4s, 8s, 16s...
 */
class ExponentialBackoffStrategy implements RetryStrategyInterface
{
    public function __construct(
        protected int $maxAttempts = 3,
        protected int $baseDelayMs = 2000, // 2 секунды
        protected int $maxDelayMs = 30000, // Максимум 30 секунд
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
        // Exponential backoff: baseDelay * (2 ^ attempt)
        $delay = $this->baseDelayMs * (2 ** $attempt);

        // Ограничиваем максимальную задержку
        return min($delay, $this->maxDelayMs);
    }
}
