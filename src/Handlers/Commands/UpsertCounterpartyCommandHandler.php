<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertCounterpartyCommand;
use GeekCo\CommerceJson\Data\BankAccountData;
use GeekCo\CommerceJson\Data\ContactData;
use GeekCo\CommerceJson\Data\CounterpartyDocumentData;
use GeekCo\CommerceJson\Data\CustomAttributeData;
use GeekCo\CommerceJson\Data\RepresentativeData;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;
use GeekCo\CommerceJson\Repositories\DocumentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpsertCounterpartyCommandHandler implements CommandHandlerInterface
{
    private const ADDRESS_PREFIXES = ['legal_address', 'actual_address'];

    private const MONEY_FIELDS = [
        'credit_limit',
        'credit_limit_remaining',
        'outstanding_debt',
    ];

    private const EXCLUDED = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    private const ADDRESS_KEYS = [
        'country', 'region', 'district', 'city', 'street',
        'house', 'building', 'apartment', 'postal_code', 'full',
    ];

    private CounterpartyRepository $counterpartyRepository;

    private DocumentRepository $documentRepository;

    public function __construct(
        CounterpartyRepository $counterpartyRepository,
        DocumentRepository $documentRepository,
    ) {
        $this->counterpartyRepository = $counterpartyRepository;
        $this->documentRepository = $documentRepository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertCounterpartyCommand);

        $data = $command->counterpartyData;

        return DB::transaction(function () use ($data) {
            $dbData = $this->toDatabaseArray($data->toArray());

            $counterparty = $this->counterpartyRepository->updateOrCreate(
                ['id' => $dbData['id']],
                $dbData
            );

            assert($counterparty instanceof Counterparty);

            $this->syncContacts($counterparty, $data->contacts);
            $this->syncRepresentatives($counterparty, $data->representatives);
            $this->syncBankAccounts($counterparty, $data->bank_accounts);
            $this->syncCustomAttributes($counterparty, $data->custom_attributes);
            $this->syncDocuments($counterparty, $data->documents);

            return $counterparty;
        });
    }

    private function toDatabaseArray(array $dto): array
    {
        $result = [];

        foreach ($dto as $key => $value) {
            if (in_array($key, self::EXCLUDED, true)) {
                continue;
            }

            if (in_array($key, self::ADDRESS_PREFIXES, true)) {
                foreach (self::ADDRESS_KEYS as $sub) {
                    $result[$key.'_'.$sub] = is_array($value) ? ($value[$sub] ?? null) : null;
                }

                continue;
            }

            if (in_array($key, self::MONEY_FIELDS, true)) {
                $result[$key.'_amount'] = is_array($value) ? ($value['amount'] ?? null) : null;
                $result[$key.'_currency'] = is_array($value) ? ($value['currency'] ?? null) : null;

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function syncContacts(Counterparty $counterparty, ?array $contactsData): void
    {
        if ($contactsData === null) {
            return;
        }

        $existingIds = $counterparty->contacts()->pluck('id')->toArray();
        $incomingIds = [];

        /** @var ContactData $contact */
        foreach ($contactsData as $contact) {
            $model = $counterparty->contacts()->updateOrCreate(
                ['id' => $contact->id],
                [
                    'counterparty_id' => $counterparty->id,
                    'type' => $contact->type->value,
                    'value' => $contact->value,
                    'comment' => $contact->comment,
                ],
            );
            $incomingIds[] = $model->id;
        }

        $toDelete = array_diff($existingIds, $incomingIds);
        if ($toDelete !== []) {
            $counterparty->contacts()->whereIn('id', $toDelete)->delete();
        }
    }

    private function syncRepresentatives(Counterparty $counterparty, ?array $representativesData): void
    {
        if ($representativesData === null) {
            return;
        }

        $existingIds = $counterparty->representatives()->pluck('id')->toArray();
        $incomingIds = [];

        /** @var RepresentativeData $rep */
        foreach ($representativesData as $rep) {
            $model = $counterparty->representatives()->updateOrCreate(
                ['id' => $rep->id],
                [
                    'counterparty_id' => $counterparty->id,
                    'name' => $rep->name,
                    'relation' => $rep->relation,
                    'phone' => $rep->phone,
                    'email' => $rep->email,
                    'position' => $rep->position,
                ],
            );
            $incomingIds[] = $model->id;
        }

        $toDelete = array_diff($existingIds, $incomingIds);
        if ($toDelete !== []) {
            $counterparty->representatives()->whereIn('id', $toDelete)->delete();
        }
    }

    private function syncBankAccounts(Counterparty $counterparty, ?array $bankAccountsData): void
    {
        if ($bankAccountsData === null) {
            return;
        }

        $existingIds = $counterparty->bankAccounts()->pluck('id')->toArray();
        $incomingIds = [];

        /** @var BankAccountData $ba */
        foreach ($bankAccountsData as $ba) {
            $model = $counterparty->bankAccounts()->updateOrCreate(
                ['id' => $ba->id],
                [
                    'counterparty_id' => $counterparty->id,
                    'bank_name' => $ba->bank_name,
                    'bik' => $ba->bik,
                    'account' => $ba->account,
                    'corr_account' => $ba->corr_account,
                    'swift' => $ba->swift,
                    'is_default' => $ba->is_default ?? false,
                ],
            );
            $incomingIds[] = $model->id;
        }

        $toDelete = array_diff($existingIds, $incomingIds);
        if ($toDelete !== []) {
            $counterparty->bankAccounts()->whereIn('id', $toDelete)->delete();
        }
    }

    private function syncCustomAttributes(Counterparty $counterparty, ?array $customAttributesData): void
    {
        if ($customAttributesData === null) {
            return;
        }

        $existingKeys = $counterparty->customAttributes()->pluck('key')->toArray();
        $incomingKeys = [];

        /** @var CustomAttributeData $attr */
        foreach ($customAttributesData as $attr) {
            $counterparty->customAttributes()->updateOrCreate(
                [
                    'attributable_type' => $counterparty->getMorphClass(),
                    'attributable_id' => $counterparty->id,
                    'key' => $attr->key,
                ],
                $this->resolveValue($attr->value),
            );
            $incomingKeys[] = $attr->key;
        }

        $toDelete = array_diff($existingKeys, $incomingKeys);
        if ($toDelete !== []) {
            $counterparty->customAttributes()->whereIn('key', $toDelete)->delete();
        }
    }

    private function resolveValue(mixed $value): array
    {
        if (is_string($value)) {
            return ['value_string' => $value, 'value_number' => null, 'value_boolean' => null, 'value_json' => null];
        }

        if (is_int($value) || is_float($value)) {
            return ['value_string' => null, 'value_number' => $value, 'value_boolean' => null, 'value_json' => null];
        }

        if (is_bool($value)) {
            return ['value_string' => null, 'value_boolean' => $value, 'value_number' => null, 'value_json' => null];
        }

        if (is_array($value)) {
            return ['value_string' => null, 'value_number' => null, 'value_boolean' => null, 'value_json' => $value];
        }

        return ['value_string' => (string) $value, 'value_number' => null, 'value_boolean' => null, 'value_json' => null];
    }

    /**
     * @param  array<int, CounterpartyDocumentData>|null  $documentsData
     */
    private function syncDocuments(Counterparty $counterparty, ?array $documentsData): void
    {
        if ($documentsData === null) {
            return;
        }

        $disk = config('commercejson.documents.disk', 'public');
        $basePath = config('commercejson.documents.path', 'commercejson/documents');

        $morphType = $counterparty->getMorphClass();
        $incomingExternalIds = [];

        foreach ($documentsData as $docData) {
            $existing = $this->documentRepository->findByExternalId(
                $morphType,
                $counterparty->id,
                $docData->external_id,
            );

            if ($existing?->trashed()) {
                $existing->restore();
            }

            $incomingExternalIds[] = $docData->external_id;

            $filePath = null;
            $mimeType = null;
            $fileSize = null;

            if ($docData->file_content !== null) {
                $decoded = base64_decode($docData->file_content, true);
                if ($decoded === false) {
                    Log::warning('Invalid base64 in document', [
                        'external_id' => $docData->external_id,
                        'counterparty_id' => $counterparty->id,
                    ]);

                    continue;
                }

                $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($decoded);

                $allowedMimeTypes = config('commercejson.documents.allowed_mime_types', []);
                if (! empty($allowedMimeTypes) && ! in_array($detectedMime, $allowedMimeTypes, true)) {
                    Log::warning('Document MIME type not allowed', [
                        'external_id' => $docData->external_id,
                        'mime_type' => $detectedMime,
                        'counterparty_id' => $counterparty->id,
                    ]);

                    continue;
                }

                $maxFileSize = (int) config('commercejson.documents.max_file_size', 10 * 1024 * 1024);
                if (strlen($decoded) > $maxFileSize) {
                    Log::warning('Document file size exceeds maximum', [
                        'external_id' => $docData->external_id,
                        'file_size' => strlen($decoded),
                        'max_size' => $maxFileSize,
                        'counterparty_id' => $counterparty->id,
                    ]);

                    continue;
                }

                $extension = $docData->file_name ? pathinfo($docData->file_name, PATHINFO_EXTENSION) : 'bin';
                $fileId = $existing ? $existing->id : (string) Str::uuid();
                $filePath = $basePath.'/'.$counterparty->id.'/'.$fileId.'.'.$extension;

                Storage::disk($disk)->put($filePath, $decoded);

                $mimeType = $detectedMime;
                $fileSize = strlen($decoded);
            }

            $hasMetadata = $docData->type !== null
                || $docData->name !== null
                || $docData->file_name !== null
                || $docData->description !== null;

            if ($existing) {
                if ($filePath !== null || $hasMetadata) {
                    $update = [];
                    if ($filePath !== null) {
                        if ($existing->file_path !== null) {
                            Storage::disk($existing->disk ?? $disk)->delete($existing->file_path);
                        }
                        $update['file_path'] = $filePath;
                        $update['disk'] = $disk;
                        $update['mime_type'] = $mimeType;
                        $update['file_size'] = $fileSize;
                    }
                    if ($hasMetadata) {
                        if ($docData->type !== null) {
                            $update['type'] = $docData->type->value;
                        }
                        if ($docData->name !== null) {
                            $update['name'] = $docData->name;
                        }
                        if ($docData->file_name !== null) {
                            $update['file_name'] = $docData->file_name;
                        }
                        if ($docData->description !== null) {
                            $update['description'] = $docData->description;
                        }
                    }
                    $this->documentRepository->update($existing, $update);
                }
            } else {
                $values = [
                    'id' => (string) Str::uuid(),
                    'documentable_type' => $morphType,
                    'documentable_id' => $counterparty->id,
                    'external_id' => $docData->external_id,
                ];

                if ($docData->type !== null) {
                    $values['type'] = $docData->type->value;
                }
                if ($docData->name !== null) {
                    $values['name'] = $docData->name;
                }
                if ($docData->file_name !== null) {
                    $values['file_name'] = $docData->file_name;
                }
                if ($docData->description !== null) {
                    $values['description'] = $docData->description;
                }
                if ($filePath !== null) {
                    $values['file_path'] = $filePath;
                    $values['disk'] = $disk;
                    $values['mime_type'] = $mimeType;
                    $values['file_size'] = $fileSize;
                }

                $this->documentRepository->create($values);
            }
        }

        $this->documentRepository->deleteMissingExternalIds(
            $morphType,
            $counterparty->id,
            $incomingExternalIds,
        );
    }
}
