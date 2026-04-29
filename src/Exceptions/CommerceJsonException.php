<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

use Exception;

/**
 * Базовое исключение для CommerceJSON package
 */
class CommerceJsonException extends Exception
{
    protected array $context = [];

    public function context(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
