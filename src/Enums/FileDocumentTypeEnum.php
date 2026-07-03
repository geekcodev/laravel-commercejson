<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum FileDocumentTypeEnum: string implements JsonSerializable
{
    case Contract = 'contract';
    case Invoice = 'invoice';
    case Act = 'act';
    case Receipt = 'receipt';
    case Statement = 'statement';
    case Waybill = 'waybill';
    case Certificate = 'certificate';
    case Other = 'other';

    private const array NAMES_RU = [
        'contract' => 'Договор',
        'invoice' => 'Счёт',
        'act' => 'Акт',
        'receipt' => 'Квитанция',
        'statement' => 'Выписка',
        'waybill' => 'Товарная накладная',
        'certificate' => 'Сертификат',
        'other' => 'Прочее',
    ];

    public function getLocalizedName(string $locale = 'ru'): string
    {
        return match ($locale) {
            'ru' => self::NAMES_RU[$this->value] ?? $this->value,
            default => throw new InvalidArgumentException("Unsupported locale: {$locale}"),
        };
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
