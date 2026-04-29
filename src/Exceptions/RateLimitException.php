<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

/**
 * Превышен лимит запросов (Rate Limit)
 */
class RateLimitException extends CommerceJsonException
{
    protected int $retryAfter;

    public function __construct(
        string $message = 'Rate limit exceeded',
        int $retryAfter = 60,
        int $code = 429,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Получить количество секунд до повторной попытки
     */
    public function retryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Получить дату/время для повторной попытки
     */
    public function retryAt(): \DateTime
    {
        return (new \DateTime)->add(new \DateInterval("PT{$this->retryAfter}S"));
    }
}
