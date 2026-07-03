<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Data;

use GeekCo\CommerceJson\Data\AddressData;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Data\CounterpartyDocumentData;
use GeekCo\CommerceJson\Data\MoneyData;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Document;
use GeekCo\CommerceJson\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

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

    public function test_from_model_maps_credit_limit_remaining(): void
    {
        $model = Counterparty::factory()->make([
            'credit_limit_remaining_amount' => '300000.00',
            'credit_limit_remaining_currency' => CurrencyEnum::RUB->value,
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertInstanceOf(MoneyData::class, $dto->credit_limit_remaining);
        $this->assertSame('300000.00', $dto->credit_limit_remaining->amount);
        $this->assertSame(CurrencyEnum::RUB, $dto->credit_limit_remaining->currency);
    }

    public function test_from_model_skips_credit_limit_remaining_when_amount_null(): void
    {
        $model = Counterparty::factory()->make([
            'credit_limit_remaining_amount' => null,
            'credit_limit_remaining_currency' => CurrencyEnum::RUB->value,
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertNull($dto->credit_limit_remaining);
    }

    public function test_from_model_maps_payment_deferral_days(): void
    {
        $model = Counterparty::factory()->make([
            'payment_deferral_days' => 45,
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertSame(45, $dto->payment_deferral_days);
    }

    public function test_from_model_maps_outstanding_debt(): void
    {
        $model = Counterparty::factory()->make([
            'outstanding_debt_amount' => '15000.00',
            'outstanding_debt_currency' => CurrencyEnum::RUB->value,
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertInstanceOf(MoneyData::class, $dto->outstanding_debt);
        $this->assertSame('15000.00', $dto->outstanding_debt->amount);
        $this->assertSame(CurrencyEnum::RUB, $dto->outstanding_debt->currency);
    }

    public function test_from_model_skips_outstanding_debt_when_amount_null(): void
    {
        $model = Counterparty::factory()->make([
            'outstanding_debt_amount' => null,
            'outstanding_debt_currency' => CurrencyEnum::RUB->value,
        ]);

        $dto = CounterpartyData::fromModel($model);

        $this->assertNull($dto->outstanding_debt);
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

    public function test_from_model_maps_documents_with_download_url(): void
    {
        $model = Counterparty::factory()->make();
        $filePath = 'commercejson/documents/'.$model->id.'/test-file.pdf';
        $doc = new Document([
            'id' => $docId = $this->createTestUuid(),
            'external_id' => 'doc-001',
            'type' => 'contract',
            'name' => 'Main Contract',
            'file_name' => 'contract.pdf',
            'file_path' => $filePath,
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'description' => 'The contract',
        ]);
        $doc->setAttribute('created_at', $now = now());
        $model->setRelation('documents', collect([$doc]));

        $dto = CounterpartyData::fromModel($model);

        $this->assertIsArray($dto->documents);
        $this->assertCount(1, $dto->documents);
        $this->assertInstanceOf(CounterpartyDocumentData::class, $dto->documents[0]);
        $this->assertSame('doc-001', $dto->documents[0]->external_id);
        $this->assertSame('contract', $dto->documents[0]->type?->value);
        $this->assertSame('Main Contract', $dto->documents[0]->name);
        $this->assertSame('application/pdf', $dto->documents[0]->mime_type);
        $this->assertSame(2048, $dto->documents[0]->file_size);
        $this->assertSame(
            Storage::disk('public')->url($filePath),
            $dto->documents[0]->download_url
        );
        $this->assertSame($now->toIso8601String(), $dto->documents[0]->uploaded_at?->toIso8601String());
    }

    public function test_from_model_skips_documents_when_not_loaded(): void
    {
        $model = Counterparty::factory()->make();

        $dto = CounterpartyData::fromModel($model);

        $this->assertNull($dto->documents);
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
