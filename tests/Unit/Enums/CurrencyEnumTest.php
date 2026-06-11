<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Enums;

use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Tests\TestCase;

class CurrencyEnumTest extends TestCase
{
    public function test_has_rub(): void
    {
        $this->assertSame('RUB', CurrencyEnum::RUB->value);
    }

    public function test_has_usd(): void
    {
        $this->assertSame('USD', CurrencyEnum::USD->value);
    }

    public function test_has_eur(): void
    {
        $this->assertSame('EUR', CurrencyEnum::EUR->value);
    }

    public function test_get_numeric_code(): void
    {
        $this->assertSame('643', CurrencyEnum::RUB->getNumericCode());
        $this->assertSame('840', CurrencyEnum::USD->getNumericCode());
        $this->assertSame('978', CurrencyEnum::EUR->getNumericCode());
    }

    public function test_get_localized_name_russian(): void
    {
        $this->assertSame('Российский рубль', CurrencyEnum::RUB->getLocalizedName('ru'));
        $this->assertSame('Доллар США', CurrencyEnum::USD->getLocalizedName('ru'));
        $this->assertSame('Евро', CurrencyEnum::EUR->getLocalizedName('ru'));
    }

    public function test_get_localized_name_english(): void
    {
        $this->assertSame('Russian Ruble', CurrencyEnum::RUB->getLocalizedName('en'));
        $this->assertSame('United States Dollar', CurrencyEnum::USD->getLocalizedName('en'));
        $this->assertSame('Euro', CurrencyEnum::EUR->getLocalizedName('en'));
    }

    public function test_get_localized_name_throws_on_unsupported_locale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CurrencyEnum::RUB->getLocalizedName('de');
    }

    public function test_json_serialize_returns_value(): void
    {
        $this->assertSame('RUB', CurrencyEnum::RUB->jsonSerialize());
        $this->assertSame('USD', CurrencyEnum::USD->jsonSerialize());
    }

    public function test_all_currencies_have_numeric_codes(): void
    {
        foreach (CurrencyEnum::cases() as $currency) {
            $numeric = $currency->getNumericCode();
            $this->assertNotEmpty($numeric, "Missing numeric code for {$currency->value}");
            $this->assertMatchesRegularExpression('/^\d{3}$/', $numeric, "Invalid numeric code for {$currency->value}");
        }
    }

    public function test_all_currencies_have_russian_name(): void
    {
        foreach (CurrencyEnum::cases() as $currency) {
            $name = $currency->getLocalizedName('ru');
            $this->assertNotEmpty($name, "Missing Russian name for {$currency->value}");
        }
    }
}
