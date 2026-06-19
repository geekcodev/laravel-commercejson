<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum PropertyTypeEnum: string implements JsonSerializable
{
    case String = 'string';
    case Number = 'number';
    case Boolean = 'boolean';
    case Enum = 'enum';
    case Multiselect = 'multiselect';
    case Color = 'color';
    case Datetime = 'datetime';

    private const array NAMES_RU = [
        'string' => 'Строка',
        'number' => 'Число',
        'boolean' => 'Да/Нет',
        'enum' => 'Одно значение',
        'multiselect' => 'Несколько значений',
        'color' => 'Цвет',
        'datetime' => 'Дата/Время',
    ];

    private const array NAMES_EN = [
        'string' => 'String',
        'number' => 'Number',
        'boolean' => 'Boolean',
        'enum' => 'Single value',
        'multiselect' => 'Multiple values',
        'color' => 'Color',
        'datetime' => 'Date/Time',
    ];

    public function isMultiple(): bool
    {
        return $this === self::Multiselect;
    }

    public function requiresEnumValues(): bool
    {
        return in_array($this, [self::Enum, self::Multiselect], true);
    }

    public function getValidationRule(): string
    {
        return match ($this) {
            self::Number => 'numeric',
            self::Boolean => 'boolean',
            self::Color => 'string|regex:/^#[0-9A-Fa-f]{3,8}$/',
            self::Datetime => 'date',
            self::Enum => 'string',
            self::Multiselect => 'array',
            default => 'string',
        };
    }

    public function getLocalizedName(string $locale = 'ru'): string
    {
        return match ($locale) {
            'ru' => self::NAMES_RU[$this->value] ?? $this->value,
            'en' => self::NAMES_EN[$this->value] ?? $this->value,
            default => throw new InvalidArgumentException("Unsupported locale: {$locale}"),
        };
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
