<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

/**
 * Бизнес-ошибка CommerceJSON API
 *
 * Запрос синтаксически корректен, но нарушает бизнес-правила
 */
class BusinessException extends CommerceJsonException
{
    protected string $businessCode;

    public function __construct(
        string $message = 'Business logic error',
        string $businessCode = 'BUSINESS_ERROR',
        int $httpCode = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
        $this->businessCode = $businessCode;
    }

    /**
     * Получить код бизнес-ошибки
     */
    public function getBusinessCode(): string
    {
        return $this->businessCode;
    }

    /**
     * Проверить тип бизнес-ошибки
     */
    public function isBusinessCode(string $code): bool
    {
        return $this->businessCode === $code;
    }
}
