<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Enums;

use InvalidArgumentException;
use JsonSerializable;

enum CounterpartyTypeEnum: string implements JsonSerializable
{
    case LegalEntity = 'legal_entity';
    case Individual = 'individual';
    case IndividualEntrepreneur = 'individual_entrepreneur';

    private const array NAMES_RU = [
        'legal_entity' => 'Юридическое лицо',
        'individual' => 'Физическое лицо',
        'individual_entrepreneur' => 'Индивидуальный предприниматель',
    ];

    private const array NAMES_EN = [
        'legal_entity' => 'Legal entity',
        'individual' => 'Individual',
        'individual_entrepreneur' => 'Individual entrepreneur',
    ];

    public function requiresKpp(): bool
    {
        return $this === self::LegalEntity;
    }

    public function innLength(): int
    {
        return $this === self::LegalEntity ? 10 : 12;
    }

    public function innPattern(): string
    {
        return $this === self::LegalEntity ? '^\d{10}$' : '^\d{12}$';
    }

    public function ogrnPattern(): string
    {
        return $this === self::LegalEntity ? '^\d{13}$' : '^\d{15}$';
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
