<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Базовое исключение HTTP клиента
 */
class HttpClientException extends RuntimeException
{
    public function __construct(
        string $message,
        int $statusCode = 0,
        public readonly ?string $errorCode = null,
        public readonly array $errors = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
