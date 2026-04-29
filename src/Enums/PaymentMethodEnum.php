<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum PaymentMethodEnum: string implements JsonSerializable
{
    case Cash = 'cash';
    case Card = 'card';
    case Online = 'online';
    case Invoice = 'invoice';
    case Credit = 'credit';

    private const array NAMES_RU = [
        'cash' => 'Наличные',
        'card' => 'Банковская карта',
        'online' => 'Онлайн-оплата',
        'invoice' => 'Счёт на оплату',
        'credit' => 'Рассрочка / Кредит',
    ];

    private const array NAMES_EN = [
        'cash' => 'Cash',
        'card' => 'Card',
        'online' => 'Online',
        'invoice' => 'Invoice',
        'credit' => 'Credit',
    ];

    /** Требует интеграции с эквайрингом/платёжным шлюзом */
    public function requiresGateway(): bool
    {
        return $this === self::Online;
    }

    /** Требует банковских реквизитов для выставления счёта */
    public function requiresBankDetails(): bool
    {
        return $this === self::Invoice;
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
