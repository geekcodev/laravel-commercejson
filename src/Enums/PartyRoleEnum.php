<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use JsonSerializable;

enum PartyRoleEnum: string implements JsonSerializable
{
    case Seller = 'seller';
    case Buyer = 'buyer';
    case Payer = 'payer';
    case Receiver = 'receiver';
    case Consignor = 'consignor';
    case Consignee = 'consignee';
    case Licensor = 'licensor';
    case Licensee = 'licensee';

    private const array NAMES_RU = [
        'seller' => 'Продавец',
        'buyer' => 'Покупатель',
        'payer' => 'Плательщик',
        'receiver' => 'Получатель',
        'consignor' => 'Комитент',
        'consignee' => 'Комиссионер',
        'licensor' => 'Лицензиар',
        'licensee' => 'Лицензиат',
    ];

    private const array NAMES_EN = [
        'seller' => 'Seller',
        'buyer' => 'Buyer',
        'payer' => 'Payer',
        'receiver' => 'Receiver',
        'consignor' => 'Consignor',
        'consignee' => 'Consignee',
        'licensor' => 'Licensor',
        'licensee' => 'Licensee',
    ];

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
