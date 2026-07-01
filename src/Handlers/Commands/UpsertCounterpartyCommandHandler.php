<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertCounterpartyCommand;
use GeekCo\CommerceJson\Data\BankAccountData;
use GeekCo\CommerceJson\Data\ContactData;
use GeekCo\CommerceJson\Data\CustomAttributeData;
use GeekCo\CommerceJson\Data\RepresentativeData;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;
use Illuminate\Support\Facades\DB;

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

    public function __construct(CounterpartyRepository $counterpartyRepository)
    {
        $this->counterpartyRepository = $counterpartyRepository;
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
            return ['value_string' => null, 'value_number' => null, 'value_boolean' => $value, 'value_json' => null];
        }

        if (is_array($value)) {
            return ['value_string' => null, 'value_number' => null, 'value_boolean' => null, 'value_json' => $value];
        }

        return ['value_string' => (string) $value, 'value_number' => null, 'value_boolean' => null, 'value_json' => null];
    }
}
