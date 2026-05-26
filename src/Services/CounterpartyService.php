<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use DateTime;
use DateTimeInterface;
use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Commands\UpsertCounterpartyCommand;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Data\CounterpartyListData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Counterparty;

/**
 * Сервис для работы с контрагентами
 */
class CounterpartyService implements ServiceInterface
{
    public function __construct(
        protected HttpClientInterface $http,
        protected CommandBusInterface $commandBus
    ) {}

    public function setHttpClient(HttpClientInterface $http): static
    {
        $this->http = $http;

        return $this;
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->http;
    }

    public function getCommandBus(): CommandBusInterface
    {
        return $this->commandBus;
    }

    /**
     * Получить список контрагентов с пагинацией
     */
    public function getCounterparties(
        int $page = 1,
        int $limit = 100,
        ?string $type = null,
        ?DateTime $updatedAfter = null,
        bool $includeDeleted = false
    ): CounterpartyListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'type' => $type,
            'updated_after' => $updatedAfter?->format(DateTimeInterface::ATOM),
            'include_deleted' => $includeDeleted ? 'true' : 'false',
        ]);

        $configPath = config('commercejson.external_api_endpoints.counterparties', '/counterparties');
        $response = $this->http->get($configPath, $query);

        return CounterpartyListData::from($response->data);
    }

    /**
     * Получить контрагента по ID
     */
    public function getCounterparty(string $id): CounterpartyData
    {
        $configPath = config('commercejson.external_api_endpoints.counterparties', '/counterparties');
        $response = $this->http->get("{$configPath}/{$id}");

        return CounterpartyData::from($response->data);
    }

    /**
     * Импортировать контрагентов
     *
     * @param  array<int, array>  $counterparties
     */
    public function importCounterparties(array $counterparties, ?string $idempotencyKey = null): ImportResultData
    {
        $configPath = config('commercejson.external_api_endpoints.counterparties', '/counterparties');
        $response = $this->http->post(
            $configPath,
            ['counterparties' => $counterparties],
            $idempotencyKey
        );

        return ImportResultData::from($response->data);
    }

    /**
     * Синхронизировать контрагента с локальной БД
     */
    public function syncCounterparty(CounterpartyData $counterpartyData): Counterparty
    {
        $command = new UpsertCounterpartyCommand($counterpartyData);

        return $this->commandBus->dispatch($command);
    }
}
