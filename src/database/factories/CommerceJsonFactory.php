<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Базовая фабрика для CommerceJSON моделей
 */
abstract class CommerceJsonFactory extends Factory
{
    /**
     * Generate a random UUID
     */
    protected static function uuid(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Generate a random external ID
     */
    protected static function externalId(): string
    {
        return 'EXT-'.Str::upper(Str::random(10));
    }

    /**
     * Generate a random barcode (14 digits)
     */
    protected static function barcode(): string
    {
        return static::numerify('############');
    }

    /**
     * Generate a random INN (10 or 12 digits)
     */
    protected static function inn(bool $isLegalEntity = true): string
    {
        return $isLegalEntity
            ? static::numerify('##########')  // 10 digits for legal entities
            : static::numerify('############'); // 12 digits for individuals
    }

    /**
     * Generate a random KPP (9 digits)
     */
    protected static function kpp(): string
    {
        return static::numerify('#########');
    }

    /**
     * Generate a random OGRN (13 or 15 digits)
     */
    protected static function ogrn(bool $isLegalEntity = true): string
    {
        return $isLegalEntity
            ? static::numerify('#############')  // 13 digits for legal entities
            : static::numerify('###############'); // 15 digits for individuals
    }

    /**
     * Generate a random phone number
     */
    protected static function phone(): string
    {
        return '+7 ('.static::numerify('###').') '.static::numerify('###-##-##');
    }

    /**
     * Generate a random email
     */
    protected static function email(): string
    {
        return static::safeEmail();
    }

    /**
     * Generate a random URL
     */
    protected static function url(): string
    {
        return static::imageUrl();
    }

    /**
     * Generate a random decimal amount
     */
    protected static function amount(int $decimals = 2): string
    {
        return number_format(static::randomFloat($decimals, 1, 10000), $decimals, '.', '');
    }

    /**
     * Generate a random quantity
     */
    protected static function quantity(): string
    {
        return number_format(static::randomFloat(3, 0.001, 1000), 3, '.', '');
    }

    /**
     * Generate a random address
     */
    protected static function address(array $options = []): array
    {
        return [
            'country' => $options['country'] ?? 'RU',
            'region' => $options['region'] ?? static::city().'ская область',
            'district' => $options['district'] ?? null,
            'city' => $options['city'] ?? static::city(),
            'street' => $options['street'] ?? static::streetName(),
            'house' => $options['house'] ?? static::buildingNumber(),
            'building' => $options['building'] ?? null,
            'apartment' => $options['apartment'] ?? null,
            'postal_code' => $options['postal_code'] ?? static::postcode(),
            'full' => $options['full'] ?? null,
        ];
    }

    /**
     * Generate a random localized string
     */
    protected static function localizedString(string $value): array
    {
        return [
            'ru' => $value,
            'en' => $value, // Can be customized
        ];
    }

    /**
     * Generate a random date-time
     */
    protected static function dateTime(?string $format = null): string
    {
        $date = static::dateTimeBetween('-1 year', 'now');

        return $format ? $date->format($format) : $date->toIso8601String();
    }

    /**
     * Generate a random date
     */
    protected static function date(): string
    {
        return static::dateTimeBetween('-1 year', 'now')->format('Y-m-d');
    }
}
