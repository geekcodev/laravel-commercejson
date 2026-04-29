<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use JsonSerializable;

enum OrderStatusEnum: string implements JsonSerializable
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    private const array NAMES_RU = [
        'new' => 'Новый',
        'confirmed' => 'Подтверждён',
        'processing' => 'В обработке',
        'shipped' => 'Отгружен',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменён',
        'refunded' => 'Возврат средств',
    ];

    private const array NAMES_EN = [
        'new' => 'New',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ];

    public function canTransitionTo(self $new): bool
    {
        return match ($this) {
            self::New => in_array($new, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($new, [self::Processing, self::Cancelled], true),
            self::Processing => in_array($new, [self::Shipped, self::Cancelled], true),
            self::Shipped => $new === self::Delivered,
            self::Delivered, self::Cancelled => $new === self::Refunded,
            self::Refunded => false,
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Delivered || $this === self::Refunded;
    }

    public function getLocalizedName(string $locale = 'ru'): string
    {
        return match ($locale) {
            'ru' => self::NAMES_RU[$this->value] ?? $this->name,
            'en' => self::NAMES_EN[$this->value] ?? $this->name,
            default => throw new \InvalidArgumentException("Unsupported locale: {$locale}"),
        };
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
