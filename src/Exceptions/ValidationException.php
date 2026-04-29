<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

/**
 * Ошибка валидации данных запроса
 */
class ValidationException extends CommerceJsonException
{
    protected array $errors = [];

    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
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
