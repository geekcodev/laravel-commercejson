<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client\Exceptions;

/**
 * Исключение для ошибок валидации (400, 422)
 */
class ValidationException extends HttpClientException
{
    public function __construct(
        string $message = 'Validation failed',
        int $statusCode = 400,
        array $errors = [],
        ?string $errorCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $errorCode, $errors, $previous);
    }

    /**
     * Получить ошибки валидации
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Получить первую ошибку
     */
    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Получить ошибки как строку
     */
    public function errorsAsString(): string
    {
        return implode('; ', $this->errors);
    }
}
