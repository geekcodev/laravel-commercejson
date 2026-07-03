<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Data;

use GeekCo\CommerceJson\Data\CounterpartyDocumentData;
use GeekCo\CommerceJson\Tests\TestCase;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Exceptions\CannotCreateData;

class CounterpartyDocumentDataTest extends TestCase
{
    public function test_can_create_with_only_external_id(): void
    {
        $dto = CounterpartyDocumentData::from([
            'external_id' => 'doc-001',
        ]);

        $this->assertInstanceOf(CounterpartyDocumentData::class, $dto);
        $this->assertSame('doc-001', $dto->external_id);
        $this->assertNull($dto->type);
        $this->assertNull($dto->name);
        $this->assertNull($dto->file_name);
        $this->assertNull($dto->file_content);
    }

    public function test_can_create_with_full_data(): void
    {
        $dto = CounterpartyDocumentData::from([
            'external_id' => 'doc-001',
            'type' => 'contract',
            'name' => 'Contract 2024',
            'file_name' => 'contract.pdf',
            'file_content' => base64_encode('fake-pdf-content'),
            'description' => 'The main contract',
        ]);

        $this->assertSame('doc-001', $dto->external_id);
        $this->assertSame('contract', $dto->type?->value);
        $this->assertSame('Contract 2024', $dto->name);
        $this->assertSame('contract.pdf', $dto->file_name);
        $this->assertSame(base64_encode('fake-pdf-content'), $dto->file_content);
        $this->assertSame('The main contract', $dto->description);
    }

    public function test_validation_passes_with_file_content_and_required_fields(): void
    {
        $data = [
            'external_id' => 'doc-001',
            'file_content' => base64_encode('content'),
            'type' => 'act',
            'name' => 'Act of work',
            'file_name' => 'act.pdf',
        ];

        $validated = CounterpartyDocumentData::validate($data);

        $this->assertIsArray($validated);
        $this->assertSame('doc-001', $validated['external_id']);
    }

    public function test_validation_fails_when_file_content_without_type(): void
    {
        $data = [
            'external_id' => 'doc-001',
            'file_content' => base64_encode('content'),
            'name' => 'Act',
            'file_name' => 'act.pdf',
        ];

        try {
            CounterpartyDocumentData::validate($data);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('type', $e->errors());
        }
    }

    public function test_validation_fails_when_file_content_without_name(): void
    {
        $data = [
            'external_id' => 'doc-001',
            'file_content' => base64_encode('content'),
            'type' => 'act',
            'file_name' => 'act.pdf',
        ];

        try {
            CounterpartyDocumentData::validate($data);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }
    }

    public function test_validation_fails_when_file_content_without_file_name(): void
    {
        $data = [
            'external_id' => 'doc-001',
            'file_content' => base64_encode('content'),
            'type' => 'act',
            'name' => 'Act of work',
        ];

        try {
            CounterpartyDocumentData::validate($data);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file_name', $e->errors());
        }
    }

    public function test_validation_fails_with_all_missing_fields_when_file_content_provided(): void
    {
        $data = [
            'external_id' => 'doc-001',
            'file_content' => base64_encode('content'),
        ];

        try {
            CounterpartyDocumentData::validate($data);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('type', $e->errors());
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertArrayHasKey('file_name', $e->errors());
        }
    }

    public function test_validation_passes_without_file_content_even_when_other_fields_null(): void
    {
        $data = [
            'external_id' => 'doc-002',
        ];

        $validated = CounterpartyDocumentData::validate($data);

        $this->assertIsArray($validated);
    }

    public function test_validation_passes_when_file_content_is_null_and_fields_present(): void
    {
        $data = [
            'external_id' => 'doc-003',
            'type' => 'invoice',
            'name' => 'Invoice 001',
            'file_name' => null,
        ];

        $validated = CounterpartyDocumentData::validate($data);

        $this->assertIsArray($validated);
    }

    public function test_external_id_is_always_required(): void
    {
        $this->expectException(CannotCreateData::class);

        CounterpartyDocumentData::from([]);
    }
}
