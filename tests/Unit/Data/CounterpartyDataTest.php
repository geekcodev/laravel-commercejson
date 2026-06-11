<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Data;

use GeekCo\CommerceJson\Data\AddressData;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Data\MoneyData;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Tests\TestCase;

class CounterpartyDataTest extends TestCase
{
    public function test_from_model_returns_valid_dto(): void
    {
        $model = Counterparty::factory()->make();

        $dto = CounterpartyData::fromModel($model);

        $this->assertInstanceOf(CounterpartyData::class, $dto);
        $this->assertSame($model->id, $dto->id);
        $this->assertInstanceOf(CounterpartyTypeEnum::class, $dto->type);
        $this->assertSame($model->name, $dto->name);
        $this->assertSame($model->inn, $dto->inn);
        $this->assertSame($model->is_active, $dto->is_active);
    }

    public function test_from_model_maps_legal_address(): void
    {
        $model = Counterparty::factory()->make([
            'legal_address_country' => 'RU',
            'legal_address_city' => 'Moscow',
            'legal_address_street' => 'Tverskaya',
            'legal_address_house' => '10',
            'legal_address_building' => '2',
            'legal_address_postal_code' => '101000',
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertInstanceOf(AddressData::class, $dto->legal_address);
        $this->assertSame('RU', $dto->legal_address->country);
        $this->assertSame('Moscow', $dto->legal_address->city);
        $this->assertSame('Tverskaya', $dto->legal_address->street);
        $this->assertSame('10', $dto->legal_address->house);
        $this->assertSame('2', $dto->legal_address->building);
        $this->assertSame('101000', $dto->legal_address->postal_code);
    }

    public function test_from_model_skips_legal_address_when_country_null(): void
    {
        $model = Counterparty::factory()->make(['legal_address_country' => null]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertNull($dto->legal_address);
    }

    public function test_from_model_maps_actual_address(): void
    {
        $model = Counterparty::factory()->make([
            'actual_address_country' => 'RU',
            'actual_address_city' => 'Saint Petersburg',
            'actual_address_street' => 'Nevsky',
            'actual_address_house' => '1',
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertInstanceOf(AddressData::class, $dto->actual_address);
        $this->assertSame('Saint Petersburg', $dto->actual_address->city);
    }

    public function test_from_model_skips_actual_address_when_country_null(): void
    {
        $model = Counterparty::factory()->make(['actual_address_country' => null]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertNull($dto->actual_address);
    }

    public function test_from_model_maps_credit_limit(): void
    {
        $model = Counterparty::factory()->make([
            'credit_limit_amount' => '500000.00',
            'credit_limit_currency' => CurrencyEnum::RUB->value,
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertInstanceOf(MoneyData::class, $dto->credit_limit);
        $this->assertSame('500000.00', $dto->credit_limit->amount);
        $this->assertSame(CurrencyEnum::RUB, $dto->credit_limit->currency);
    }

    public function test_from_model_skips_credit_limit_when_amount_null(): void
    {
        $model = Counterparty::factory()->make([
            'credit_limit_amount' => null,
            'credit_limit_currency' => CurrencyEnum::RUB->value,
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertNull($dto->credit_limit);
    }

    public function test_from_model_skips_credit_limit_when_currency_null(): void
    {
        $model = Counterparty::factory()->make([
            'credit_limit_amount' => '100.00',
            'credit_limit_currency' => null,
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertNull($dto->credit_limit);
    }

    public function test_from_model_maps_loaded_relations(): void
    {
        $model = Counterparty::factory()->make();
        $model->setRelation('contacts', collect([]));
        $model->setRelation('representatives', collect([]));
        $model->setRelation('bank_accounts', collect([]));
        $model->setRelation('custom_attributes', collect([]));

        $dto = CounterpartyData::fromModel($model);

        $this->assertIsArray($dto->contacts);
        $this->assertIsArray($dto->representatives);
        $this->assertIsArray($dto->bank_accounts);
        $this->assertIsArray($dto->custom_attributes);
        $this->assertEmpty($dto->contacts);
    }

    public function test_from_maps_model_automatically(): void
    {
        $model = Counterparty::factory()->make([
            'id' => $id = $this->createTestUuid(),
            'name' => 'Auto Mapped',
        ]);

        $dto = CounterpartyData::from($model);

        $this->assertInstanceOf(CounterpartyData::class, $dto);
        $this->assertSame($id, $dto->id);
        $this->assertSame('Auto Mapped', $dto->name);
    }
}
