<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Enums;

use GeekCo\CommerceJson\Enums\CounterpartyBusinessRoleEnum;
use GeekCo\CommerceJson\Tests\TestCase;

class CounterpartyBusinessRoleEnumTest extends TestCase
{
    public function test_has_customer(): void
    {
        $this->assertSame('customer', CounterpartyBusinessRoleEnum::Customer->value);
    }

    public function test_has_supplier(): void
    {
        $this->assertSame('supplier', CounterpartyBusinessRoleEnum::Supplier->value);
    }

    public function test_has_partner(): void
    {
        $this->assertSame('partner', CounterpartyBusinessRoleEnum::Partner->value);
    }

    public function test_has_carrier(): void
    {
        $this->assertSame('carrier', CounterpartyBusinessRoleEnum::Carrier->value);
    }

    public function test_has_customer_supplier(): void
    {
        $this->assertSame('customer_supplier', CounterpartyBusinessRoleEnum::CustomerSupplier->value);
    }

    public function test_has_other(): void
    {
        $this->assertSame('other', CounterpartyBusinessRoleEnum::Other->value);
    }

    public function test_get_localized_name_russian(): void
    {
        $this->assertSame('Покупатель', CounterpartyBusinessRoleEnum::Customer->getLocalizedName('ru'));
        $this->assertSame('Поставщик', CounterpartyBusinessRoleEnum::Supplier->getLocalizedName('ru'));
        $this->assertSame('Партнёр', CounterpartyBusinessRoleEnum::Partner->getLocalizedName('ru'));
        $this->assertSame('Перевозчик', CounterpartyBusinessRoleEnum::Carrier->getLocalizedName('ru'));
        $this->assertSame('Покупатель и поставщик', CounterpartyBusinessRoleEnum::CustomerSupplier->getLocalizedName('ru'));
        $this->assertSame('Прочее', CounterpartyBusinessRoleEnum::Other->getLocalizedName('ru'));
    }

    public function test_get_localized_name_english(): void
    {
        $this->assertSame('Customer', CounterpartyBusinessRoleEnum::Customer->getLocalizedName('en'));
        $this->assertSame('Supplier', CounterpartyBusinessRoleEnum::Supplier->getLocalizedName('en'));
        $this->assertSame('Partner', CounterpartyBusinessRoleEnum::Partner->getLocalizedName('en'));
        $this->assertSame('Carrier', CounterpartyBusinessRoleEnum::Carrier->getLocalizedName('en'));
        $this->assertSame('Customer and supplier', CounterpartyBusinessRoleEnum::CustomerSupplier->getLocalizedName('en'));
        $this->assertSame('Other', CounterpartyBusinessRoleEnum::Other->getLocalizedName('en'));
    }

    public function test_get_localized_name_throws_on_unsupported_locale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CounterpartyBusinessRoleEnum::Customer->getLocalizedName('de');
    }

    public function test_json_serialize_returns_value(): void
    {
        $this->assertSame('customer', CounterpartyBusinessRoleEnum::Customer->jsonSerialize());
        $this->assertSame('supplier', CounterpartyBusinessRoleEnum::Supplier->jsonSerialize());
    }

    public function test_all_cases_have_unique_values(): void
    {
        $values = array_map(fn (CounterpartyBusinessRoleEnum $case) => $case->value, CounterpartyBusinessRoleEnum::cases());
        $this->assertCount(count($values), array_unique($values));
    }
}
