<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Data\CounterpartyListData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Models\Counterparty;

/**
 * Сервис для работы с контрагентами
 */
class CounterpartyService
{
    public function __construct(
        protected CommerceJsonConnector $connector
    ) {}

    /**
     * Получить список контрагентов с пагинацией
     */
    public function getCounterparties(
        int $page = 1,
        int $limit = 100,
        ?string $type = null,
        ?\DateTime $updatedAfter = null,
        bool $includeDeleted = false
    ): CounterpartyListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'type' => $type,
            'updated_after' => $updatedAfter?->format(\DateTime::ATOM),
            'include_deleted' => $includeDeleted ? 'true' : 'false',
        ]);

        $response = $this->connector->get('/counterparties', $query);

        return CounterpartyListData::from(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Получить контрагента по ID
     */
    public function getCounterparty(string $id): CounterpartyData
    {
        $response = $this->connector->get("/counterparties/{$id}");

        return CounterpartyData::from(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Импортировать контрагентов
     *
     * @param  array<int, array>  $counterparties
     */
    public function importCounterparties(array $counterparties, ?string $idempotencyKey = null): ImportResultData
    {
        $response = $this->connector->post(
            '/counterparties',
            ['counterparties' => $counterparties],
            $idempotencyKey
        );

        return ImportResultData::from(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Синхронизировать контрагента с локальной БД
     */
    public function syncCounterparty(CounterpartyData $counterpartyData): Counterparty
    {
        return Counterparty::updateOrCreate(
            ['id' => $counterpartyData->id],
            [
                'external_id' => $counterpartyData->externalId ?? null,
                'type' => $counterpartyData->type->value,
                'name' => $counterpartyData->name,
                'short_name' => $counterpartyData->shortName ?? null,
                'inn' => $counterpartyData->inn ?? null,
                'kpp' => $counterpartyData->kpp ?? null,
                'ogrn' => $counterpartyData->ogrn ?? null,
                'okved' => $counterpartyData->okved ?? null,
                'okpo' => $counterpartyData->okpo ?? null,
                'okopf' => $counterpartyData->okopf ?? null,
                'okfs' => $counterpartyData->okfs ?? null,
                'registration_date' => $counterpartyData->registrationDate ?? null,
                'legal_address_full' => $counterpartyData->legalAddress->full ?? null,
                'actual_address_full' => $counterpartyData->actualAddress->full ?? null,
                'price_type_id' => $counterpartyData->priceTypeId ?? null,
                'credit_limit_amount' => $counterpartyData->creditLimit?->amount ?? null,
                'credit_limit_currency' => $counterpartyData->creditLimit?->currency ?? null,
                'is_active' => $counterpartyData->isActive ?? true,
            ]
        );
    }
}
