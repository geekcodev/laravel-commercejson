<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum PaymentStatusEnum: string implements JsonSerializable
{
    case Pending = 'pending';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Refunded = 'refunded';

    private const array NAMES_RU = [
        'pending' => 'Ожидает оплаты',
        'paid' => 'Оплачен',
        'partially_paid' => 'Частично оплачен',
        'refunded' => 'Возврат произведён',
    ];

    private const array NAMES_EN = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'partially_paid' => 'Partially paid',
        'refunded' => 'Refunded',
    ];

    public function isSuccess(): bool
    {
        return $this === self::Paid;
    }

    public function isFinal(): bool
    {
        return $this === self::Paid || $this === self::Refunded;
    }

    public function getLocalizedName(string $locale = 'ru'): string
    {
        return match ($locale) {
            'ru' => self::NAMES_RU[$this->value] ?? $this->name,
            'en' => self::NAMES_EN[$this->value] ?? $this->name,
            default => throw new InvalidArgumentException("Unsupported locale: {$locale}"),
        };
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
