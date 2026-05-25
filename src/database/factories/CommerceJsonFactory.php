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
        return fake()->numerify('############');
    }

    /**
     * Generate a random INN (10 or 12 digits)
     */
    protected static function inn(bool $isLegalEntity = true): string
    {
        return $isLegalEntity
            ? fake()->numerify('##########')  // 10 digits for legal entities
            : fake()->numerify('############'); // 12 digits for individuals
    }

    /**
     * Generate a random KPP (9 digits)
     */
    protected static function kpp(): string
    {
        return fake()->numerify('#########');
    }

    /**
     * Generate a random OGRN (13 or 15 digits)
     */
    protected static function ogrn(bool $isLegalEntity = true): string
    {
        return $isLegalEntity
            ? fake()->numerify('#############')  // 13 digits for legal entities
            : fake()->numerify('###############'); // 15 digits for individuals
    }

    /**
     * Generate a random phone number
     */
    protected static function generatePhone(): string
    {
        return '+7 ('.fake()->numerify('###').') '.fake()->numerify('###-##-##');
    }

    /**
     * Generate a random email
     */
    protected static function generateEmail(): string
    {
        return fake()->safeEmail();
    }

    /**
     * Generate a random URL
     */
    protected static function generateUrl(): string
    {
        return fake()->imageUrl();
    }

    /**
     * Generate a random decimal amount
     */
    protected static function amount(int $decimals = 2): string
    {
        return number_format(fake()->randomFloat($decimals, 1, 10000), $decimals, '.', '');
    }

    /**
     * Generate a random quantity
     */
    protected static function quantity(): string
    {
        return number_format(fake()->randomFloat(3, 0.001, 1000), 3, '.', '');
    }

    /**
     * Generate a random address
     */
    protected static function address(array $options = []): array
    {
        return [
            'country' => $options['country'] ?? 'RU',
            'region' => $options['region'] ?? fake()->city().'ская область',
            'district' => $options['district'] ?? null,
            'city' => $options['city'] ?? fake()->city(),
            'street' => $options['street'] ?? fake()->streetName(),
            'house' => $options['house'] ?? fake()->buildingNumber(),
            'building' => $options['building'] ?? null,
            'apartment' => $options['apartment'] ?? null,
            'postal_code' => $options['postal_code'] ?? fake()->postcode(),
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
        $date = fake()->dateTimeBetween('-1 year');

        return $format ? $date->format($format) : $date->format(\DateTimeInterface::ATOM);
    }

    /**
     * Generate a random date
     */
    protected static function date(): string
    {
        return fake()->dateTimeBetween('-1 year')->format('Y-m-d');
    }
}
