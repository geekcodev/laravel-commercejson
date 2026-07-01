<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\UpsertCounterpartyCommand;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Enums\ContactTypeEnum;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Handlers\Commands\UpsertCounterpartyCommandHandler;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;

describe('UpsertCounterpartyCommandHandler', function () {
    it('creates a new counterparty with all fields', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Test LLC',
            'external_id' => 'ERP-001',
            'short_name' => 'Test',
            'inn' => '1234567890',
            'kpp' => '123456789',
            'ogrn' => '1234567890123',
            'okved' => '62.01',
            'okpo' => '12345678',
            'is_active' => true,
            'credit_limit' => ['amount' => '500000.00', 'currency' => CurrencyEnum::RUB->value],
            'credit_limit_remaining' => ['amount' => '300000.00', 'currency' => CurrencyEnum::RUB->value],
            'payment_deferral_days' => 30,
            'outstanding_debt' => ['amount' => '150000.00', 'currency' => CurrencyEnum::RUB->value],
            'legal_address' => [
                'country' => 'RU',
                'city' => 'Moscow',
                'street' => 'Tverskaya',
                'house' => '10',
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $result = $handler->handle(new UpsertCounterpartyCommand($data));

        expect($result)->toBeInstanceOf(Counterparty::class);
        expect($result->id)->toBe($id);
        expect($result->name)->toBe('Test LLC');

        test()->assertDatabaseHas('counterparties', [
            'id' => $id,
            'name' => 'Test LLC',
            'external_id' => 'ERP-001',
            'inn' => '1234567890',
            'kpp' => '123456789',
            'credit_limit_amount' => '500000.00',
            'credit_limit_currency' => 'RUB',
            'credit_limit_remaining_amount' => '300000.00',
            'credit_limit_remaining_currency' => 'RUB',
            'payment_deferral_days' => 30,
            'outstanding_debt_amount' => '150000.00',
            'outstanding_debt_currency' => 'RUB',
            'legal_address_country' => 'RU',
            'legal_address_city' => 'Moscow',
            'legal_address_street' => 'Tverskaya',
            'legal_address_house' => '10',
        ]);
    });

    it('updates an existing counterparty', function () {
        $counterparty = Counterparty::factory()->create([
            'name' => 'Original Name',
        ]);

        $data = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Updated Name',
            'short_name' => 'Updated',
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $result = $handler->handle(new UpsertCounterpartyCommand($data));

        expect($result->name)->toBe('Updated Name');
        expect($result->short_name)->toBe('Updated');
    });

    it('handles null money and address fields', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::Individual,
            'name' => 'John Doe',
            'credit_limit' => null,
            'credit_limit_remaining' => null,
            'outstanding_debt' => null,
            'legal_address' => null,
            'actual_address' => null,
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $result = $handler->handle(new UpsertCounterpartyCommand($data));

        expect($result)->toBeInstanceOf(Counterparty::class);
        expect($result->credit_limit_amount)->toBeNull();
        expect($result->credit_limit_remaining_amount)->toBeNull();
        expect($result->outstanding_debt_amount)->toBeNull();
        expect($result->legal_address_country)->toBeNull();
        expect($result->actual_address_country)->toBeNull();
    });

    it('persists credit limit remaining and outstanding debt', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Credit Corp',
            'credit_limit' => ['amount' => '1000000.00', 'currency' => CurrencyEnum::RUB->value],
            'credit_limit_remaining' => ['amount' => '750000.00', 'currency' => CurrencyEnum::RUB->value],
            'outstanding_debt' => ['amount' => '200000.00', 'currency' => CurrencyEnum::USD->value],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('counterparties', [
            'id' => $id,
            'credit_limit_amount' => '1000000.00',
            'credit_limit_currency' => 'RUB',
            'credit_limit_remaining_amount' => '750000.00',
            'credit_limit_remaining_currency' => 'RUB',
            'outstanding_debt_amount' => '200000.00',
            'outstanding_debt_currency' => 'USD',
        ]);
    });

    it('syncs contacts on create', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Contact Corp',
            'contacts' => [
                ['type' => ContactTypeEnum::Email, 'value' => 'info@example.com'],
                ['type' => ContactTypeEnum::Phone, 'value' => '+71234567890', 'comment' => 'Office'],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('contacts', [
            'counterparty_id' => $id,
            'type' => 'email',
            'value' => 'info@example.com',
        ]);

        test()->assertDatabaseHas('contacts', [
            'counterparty_id' => $id,
            'type' => 'phone',
            'value' => '+71234567890',
            'comment' => 'Office',
        ]);
    });

    it('replaces contacts on update', function () {
        $counterparty = Counterparty::factory()->create();

        $initialData = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => $counterparty->name,
            'contacts' => [
                ['type' => ContactTypeEnum::Email, 'value' => 'old@example.com'],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $handler->handle(new UpsertCounterpartyCommand($initialData));

        $updatedData = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => $counterparty->name,
            'contacts' => [
                ['type' => ContactTypeEnum::Email, 'value' => 'new@example.com'],
            ],
        ]);

        $handler->handle(new UpsertCounterpartyCommand($updatedData));

        test()->assertDatabaseMissing('contacts', [
            'counterparty_id' => $counterparty->id,
            'value' => 'old@example.com',
        ]);

        test()->assertDatabaseHas('contacts', [
            'counterparty_id' => $counterparty->id,
            'value' => 'new@example.com',
        ]);
    });

    it('syncs bank accounts on create', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Bank Corp',
            'bank_accounts' => [
                ['bik' => '044525225', 'account' => '40702810123450000001', 'bank_name' => 'Sberbank', 'is_default' => true],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('bank_accounts', [
            'counterparty_id' => $id,
            'bik' => '044525225',
            'account' => '40702810123450000001',
            'bank_name' => 'Sberbank',
            'is_default' => true,
        ]);
    });

    it('syncs representatives on create', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Rep Corp',
            'representatives' => [
                ['name' => 'Ivan Ivanov', 'relation' => 'CEO', 'phone' => '+70000000001', 'email' => 'ivan@example.com'],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('representatives', [
            'counterparty_id' => $id,
            'name' => 'Ivan Ivanov',
            'relation' => 'CEO',
            'phone' => '+70000000001',
            'email' => 'ivan@example.com',
        ]);
    });

    it('syncs custom attributes on create', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Attr Corp',
            'custom_attributes' => [
                ['key' => 'source', 'value' => '1c'],
                ['key' => 'rating', 'value' => 5],
                ['key' => 'is_vip', 'value' => true],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('custom_attributes', [
            'attributable_id' => $id,
            'key' => 'source',
            'value_string' => '1c',
        ]);

        test()->assertDatabaseHas('custom_attributes', [
            'attributable_id' => $id,
            'key' => 'rating',
            'value_number' => 5,
        ]);

        test()->assertDatabaseHas('custom_attributes', [
            'attributable_id' => $id,
            'key' => 'is_vip',
            'value_boolean' => true,
        ]);
    });

    it('handles null relation fields gracefully', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::Individual,
            'name' => 'John Null',
            'contacts' => null,
            'representatives' => null,
            'bank_accounts' => null,
            'custom_attributes' => null,
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository);

        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('counterparties', ['id' => $id, 'name' => 'John Null']);
        expect(Counterparty::find($id)->contacts()->count())->toBe(0);
        expect(Counterparty::find($id)->representatives()->count())->toBe(0);
        expect(Counterparty::find($id)->bankAccounts()->count())->toBe(0);
        expect(Counterparty::find($id)->customAttributes()->count())->toBe(0);
    });
});
