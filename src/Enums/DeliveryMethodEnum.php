<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum DeliveryMethodEnum: string implements JsonSerializable
{
    case Pickup = 'pickup';
    case Courier = 'courier';
    case Post = 'post';
    case TransportCompany = 'transport_company';

    private const array NAMES_RU = [
        'pickup' => 'Самовывоз',
        'courier' => 'Курьер',
        'post' => 'Почта',
        'transport_company' => 'Транспортная компания',
    ];

    private const array NAMES_EN = [
        'pickup' => 'Pickup',
        'courier' => 'Courier',
        'post' => 'Postal',
        'transport_company' => 'Transport Company',
    ];

    /** Согласно OpenAPI: courier, post, transport_company требуют обязательного address */
    public function requiresAddress(): bool
    {
        return in_array($this, [
            self::Courier, self::Post, self::TransportCompany,
        ], true);
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
