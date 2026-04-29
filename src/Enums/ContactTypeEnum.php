<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum ContactTypeEnum: string implements JsonSerializable
{
    case Phone = 'phone';
    case Mobile = 'mobile';
    case InternalPhone = 'internal_phone';
    case HomePhone = 'home_phone';
    case Email = 'email';
    case Fax = 'fax';
    case Web = 'web';
    case Pager = 'pager';
    case Icq = 'icq';
    case MapCoordinates = 'map_coordinates';
    case Other = 'other';

    private const array NAMES_RU = [
        'phone' => 'Телефон рабочий',
        'mobile' => 'Телефон мобильный',
        'internal_phone' => 'Телефон внутренний',
        'home_phone' => 'Телефон домашний',
        'email' => 'Электронная почта',
        'fax' => 'Факс',
        'web' => 'Веб-сайт',
        'pager' => 'Пейджер',
        'icq' => 'ICQ',
        'map_coordinates' => 'Координаты',
        'other' => 'Прочее',
    ];

    private const array NAMES_EN = [
        'phone' => 'Work phone',
        'mobile' => 'Mobile phone',
        'internal_phone' => 'Internal phone',
        'home_phone' => 'Home phone',
        'email' => 'Email',
        'fax' => 'Fax',
        'web' => 'Website',
        'pager' => 'Pager',
        'icq' => 'ICQ',
        'map_coordinates' => 'Map coordinates',
        'other' => 'Other',
    ];

    public function isPhone(): bool
    {
        return in_array($this, [self::Phone, self::Mobile, self::InternalPhone, self::HomePhone], true);
    }

    public function getValidationRule(): string
    {
        return match ($this) {
            self::Email => 'email',
            self::Web => 'url',
            self::MapCoordinates => 'regex:/^\-?\d+(\.\d+)?,\s*\-?\d+(\.\d+)?$/',
            self::Phone, self::Mobile, self::InternalPhone, self::HomePhone => 'string|regex:/^\+?\d{7,15}$/',
            default => 'string',
        };
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
