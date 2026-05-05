<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client\Exceptions;

/**
 * Исключение для бизнес-ошибок (422)
 */
class BusinessException extends HttpClientException
{
    public function __construct(
        string $message = 'Business error',
        int $statusCode = 422,
        public readonly ?string $businessCode = null,
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $businessCode, $errors, $previous);
    }

    /**
     * Получить код бизнес-ошибки
     */
    public function getBusinessCode(): ?string
    {
        return $this->businessCode;
    }
}
