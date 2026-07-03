<?php

declare(strict_types=1);

use GeekCo\CommerceJson\Commands\UpsertCounterpartyCommand;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Enums\ContactTypeEnum;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Handlers\Commands\UpsertCounterpartyCommandHandler;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Document;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;
use GeekCo\CommerceJson\Repositories\DocumentRepository;
use Illuminate\Support\Facades\Storage;

function pdfContent(string $text): string
{
    return base64_encode("%PDF-1.4\n{$text}");
}

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

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
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));

        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('counterparties', ['id' => $id, 'name' => 'John Null']);
        expect(Counterparty::find($id)->contacts()->count())->toBe(0);
        expect(Counterparty::find($id)->representatives()->count())->toBe(0);
        expect(Counterparty::find($id)->bankAccounts()->count())->toBe(0);
        expect(Counterparty::find($id)->customAttributes()->count())->toBe(0);
    });

    it('creates documents from CounterpartyDocumentData', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Doc Corp',
            'documents' => [
                [
                    'external_id' => 'doc-001',
                    'type' => 'contract',
                    'name' => 'Main Contract',
                    'file_name' => 'contract.pdf',
                    'file_content' => pdfContent('fake-pdf-content'),
                    'description' => 'The main contract',
                ],
                [
                    'external_id' => 'doc-002',
                    'type' => 'invoice',
                    'name' => 'Invoice 001',
                    'file_name' => 'invoice.pdf',
                    'file_content' => pdfContent('fake-invoice-content'),
                ],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('documents', [
            'documentable_id' => $id,
            'external_id' => 'doc-001',
            'type' => 'contract',
            'name' => 'Main Contract',
            'file_name' => 'contract.pdf',
            'description' => 'The main contract',
        ]);

        test()->assertDatabaseHas('documents', [
            'documentable_id' => $id,
            'external_id' => 'doc-002',
            'type' => 'invoice',
            'name' => 'Invoice 001',
            'file_name' => 'invoice.pdf',
        ]);

        expect(Document::where('documentable_id', $id)->count())->toBe(2);
    });

    it('updates existing document on re-import with file_content', function () {
        Storage::fake('public');

        $counterparty = Counterparty::factory()->create();
        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'doc-001',
            'name' => 'Old Name',
            'file_path' => 'commercejson/documents/old/contract.pdf',
            'disk' => 'public',
        ]);

        Storage::disk('public')->put('commercejson/documents/old/contract.pdf', 'old-content');

        $data = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => $counterparty->name,
            'documents' => [
                [
                    'external_id' => 'doc-001',
                    'type' => 'contract',
                    'name' => 'Updated Name',
                    'file_name' => 'contract.pdf',
                    'file_content' => pdfContent('updated-content'),
                ],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('documents', [
            'documentable_id' => $counterparty->id,
            'external_id' => 'doc-001',
            'name' => 'Updated Name',
        ]);

        expect(Document::where('documentable_id', $counterparty->id)->count())->toBe(1);
        Storage::disk('public')->assertMissing('commercejson/documents/old/contract.pdf');
    });

    it('does not update existing document when file_content is null', function () {
        $counterparty = Counterparty::factory()->create();
        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'doc-keep',
            'name' => 'Keep Name',
        ]);

        $data = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => $counterparty->name,
            'documents' => [
                [
                    'external_id' => 'doc-keep',
                ],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('documents', [
            'documentable_id' => $counterparty->id,
            'external_id' => 'doc-keep',
            'name' => 'Keep Name',
        ]);
    });

    it('removes documents when empty array is passed', function () {
        $counterparty = Counterparty::factory()->create();
        Document::factory(2)->create([
            'documentable_id' => $counterparty->id,
        ]);

        $data = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => $counterparty->name,
            'documents' => [],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        expect(Document::where('documentable_id', $counterparty->id)->count())->toBe(0);
    });

    it('does not touch documents when null is passed', function () {
        $counterparty = Counterparty::factory()->create();
        Document::factory(2)->create([
            'documentable_id' => $counterparty->id,
        ]);

        $data = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => $counterparty->name,
            'documents' => null,
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        expect(Document::where('documentable_id', $counterparty->id)->count())->toBe(2);
    });

    it('deduplicates documents with same external_id using last occurrence', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Dedup Corp',
            'documents' => [
                [
                    'external_id' => 'doc-001',
                    'type' => 'contract',
                    'name' => 'First Name',
                    'file_name' => 'first.pdf',
                    'file_content' => pdfContent('first'),
                ],
                [
                    'external_id' => 'doc-001',
                    'type' => 'contract',
                    'name' => 'Second Name',
                    'file_name' => 'second.pdf',
                    'file_content' => pdfContent('second'),
                ],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        $docs = Document::where('documentable_id', $id)
            ->where('external_id', 'doc-001')
            ->get();

        expect($docs)->toHaveCount(1);
        expect($docs->first()->name)->toBe('Second Name');
    });

    it('deletes documents not in the new list and keeps existing ones', function () {
        $counterparty = Counterparty::factory()->create();
        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'keep-01',
        ]);
        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'delete-01',
        ]);

        $data = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => $counterparty->name,
            'documents' => [
                [
                    'external_id' => 'keep-01',
                ],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        test()->assertDatabaseHas('documents', ['external_id' => 'keep-01']);
        expect(Document::where('external_id', 'delete-01')->withTrashed()->first()->deleted_at)->not->toBeNull();
    });

    it('ignores invalid base64 and continues with other documents', function () {
        $id = test()->createTestUuid();

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Base64 Corp',
            'documents' => [
                [
                    'external_id' => 'doc-invalid',
                    'type' => 'contract',
                    'name' => 'Bad File',
                    'file_name' => 'bad.pdf',
                    'file_content' => '!!!not-valid-base64!!!',
                ],
                [
                    'external_id' => 'doc-valid',
                    'type' => 'invoice',
                    'name' => 'Good File',
                    'file_name' => 'good.pdf',
                    'file_content' => pdfContent('valid-content'),
                ],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        $docs = Document::where('documentable_id', $id)->get();
        expect($docs)->toHaveCount(1);
        expect($docs->first()->external_id)->toBe('doc-valid');
    });

    it('rejects oversized file content', function () {
        $id = test()->createTestUuid();
        $oversized = str_repeat('A', 11 * 1024 * 1024);

        $data = CounterpartyData::from([
            'id' => $id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => 'Oversize Corp',
            'documents' => [
                [
                    'external_id' => 'doc-big',
                    'type' => 'contract',
                    'name' => 'Big File',
                    'file_name' => 'big.pdf',
                    'file_content' => base64_encode($oversized),
                ],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        expect(Document::where('documentable_id', $id)->count())->toBe(0);
    });

    it('restores soft-deleted document on re-import with same external_id', function () {
        $counterparty = Counterparty::factory()->create();
        Document::factory()->create([
            'documentable_id' => $counterparty->id,
            'external_id' => 'doc-restore',
            'name' => 'To Restore',
            'file_path' => 'commercejson/documents/restore/contract.pdf',
            'disk' => 'public',
        ]);

        Document::where('documentable_id', $counterparty->id)
            ->where('external_id', 'doc-restore')
            ->delete();

        expect(Document::where('documentable_id', $counterparty->id)->count())->toBe(0, 'Document should be soft-deleted');

        $data = CounterpartyData::from([
            'id' => $counterparty->id,
            'type' => CounterpartyTypeEnum::LegalEntity,
            'name' => $counterparty->name,
            'documents' => [
                [
                    'external_id' => 'doc-restore',
                    'name' => 'Restored',
                ],
            ],
        ]);

        $repository = new CounterpartyRepository(new Counterparty);
        $handler = new UpsertCounterpartyCommandHandler($repository, new DocumentRepository(new Document));
        $handler->handle(new UpsertCounterpartyCommand($data));

        $doc = Document::where('documentable_id', $counterparty->id)
            ->where('external_id', 'doc-restore')
            ->first();

        expect($doc)->not->toBeNull('Document should be restored and visible');
        expect($doc->name)->toBe('Restored');
        expect($doc->deleted_at)->toBeNull('Document should not be soft-deleted');
    });
});
