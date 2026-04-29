<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

/**
 * Ошибка авторизации в CommerceJSON API
 */
class AuthenticationException extends CommerceJsonException
{
    public function __construct(
        string $message = 'Authentication failed',
        int $code = 401,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
