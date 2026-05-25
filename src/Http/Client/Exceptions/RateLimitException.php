<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client\Exceptions;

/**
 * Исключение для rate limit ошибок (429)
 */
class RateLimitException extends HttpClientException
{
    public function __construct(
        string $message,
        public readonly int $retryAfter = 60,
        int $statusCode = 429,
        ?string $errorCode = null,
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorCode, $errors, $previous);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
