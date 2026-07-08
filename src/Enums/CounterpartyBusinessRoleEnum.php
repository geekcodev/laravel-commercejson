<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum CounterpartyBusinessRoleEnum: string implements JsonSerializable
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Partner = 'partner';
    case Carrier = 'carrier';
    case CustomerSupplier = 'customer_supplier';
    case Other = 'other';

    private const array NAMES_RU = [
        'customer' => 'Покупатель',
        'supplier' => 'Поставщик',
        'partner' => 'Партнёр',
        'carrier' => 'Перевозчик',
        'customer_supplier' => 'Покупатель и поставщик',
        'other' => 'Прочее',
    ];

    private const array NAMES_EN = [
        'customer' => 'Customer',
        'supplier' => 'Supplier',
        'partner' => 'Partner',
        'carrier' => 'Carrier',
        'customer_supplier' => 'Customer and supplier',
        'other' => 'Other',
    ];

    public function getLocalizedName(string $locale = 'ru'): string
    {
        return match ($locale) {
            'ru' => self::NAMES_RU[$this->value],
            'en' => self::NAMES_EN[$this->value],
            default => throw new InvalidArgumentException("Unsupported locale: {$locale}"),
        };
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
