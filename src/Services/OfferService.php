<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use DateTimeInterface;
use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Commands\UpsertOfferCommand;
use GeekCo\CommerceJson\Commands\UpsertOfferPriceCommand;
use GeekCo\CommerceJson\Commands\UpsertStockCommand;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Data\OfferImportData;
use GeekCo\CommerceJson\Data\OfferListData;
use GeekCo\CommerceJson\Data\OfferPriceData;
use GeekCo\CommerceJson\Data\StockData;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Offer;

/**
 * Сервис для работы с предложениями (цены и остатки)
 */
class OfferService implements ServiceInterface
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
     * Получить список предложений с пагинацией
     */
    public function getOffers(
        int $page = 1,
        int $limit = 100,
        ?string $priceTypeId = null,
        ?string $warehouseId = null,
        ?DateTimeInterface $updatedAfter = null
    ): OfferListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'price_type_id' => $priceTypeId,
            'warehouse_id' => $warehouseId,
            'updated_after' => $updatedAfter?->format(DateTimeInterface::ATOM),
        ]);

        $response = $this->http->get('/offers', $query);

        return OfferListData::from($response->data);
    }

    /**
     * Импортировать предложения (цены и остатки)
     */
    public function importOffers(OfferImportData $importData, ?string $idempotencyKey = null): ImportResultData
    {
        $response = $this->http->post(
            '/offers',
            $importData->toArray(),
            $idempotencyKey
        );

        return ImportResultData::from($response->data);
    }

    /**
     * Получить справочник типов цен
     *
     * @return array<int, array{id: string, name: string, currency: string, description: ?string, is_default: bool}>
     */
    public function getPriceTypes(): array
    {
        $response = $this->http->get('/offers/price-types');

        return $response->data['price_types'];
    }

    /**
     * Получить справочник складов
     *
     * @return array<int, array{id: string, name: string, code: ?string, address: array, is_active: bool, is_default: bool}>
     */
    public function getWarehouses(): array
    {
        $response = $this->http->get('/warehouses');

        return $response->data['warehouses'];
    }

    /**
     * Получить предложения для конкретного товара
     *
     * @return array<int, OfferData>
     */
    public function getProductOffers(string $productId): array
    {
        $offers = [];
        $page = 1;

        do {
            $result = $this->getOffers(page: $page, limit: 100);

            foreach ($result->offers as $offer) {
                if ($offer->productId === $productId) {
                    $offers[] = $offer;
                }
            }

            $page++;
        } while ($result->pagination->hasNext);

        return $offers;
    }

    /**
     * Синхронизировать предложение с локальной БД
     */
    public function syncOffer(OfferData $offerData): Offer
    {
        $command = new UpsertOfferCommand($offerData);

        return $this->commandBus->dispatch($command);
    }

    /**
     * Синхронизировать цены предложения через CommandBus
     *
     * @param  array<int, OfferPriceData>  $pricesData
     * @return int количество синхронизированных цен
     */
    public function syncOfferPrices(Offer $offer, array $pricesData): int
    {
        $count = 0;

        foreach ($pricesData as $priceData) {
            $command = new UpsertOfferPriceCommand($offer->id, OfferPriceData::from($priceData));
            $this->commandBus->dispatch($command);
            $count++;
        }

        return $count;
    }

    /**
     * Синхронизировать остатки предложения через CommandBus
     *
     * @param  array<int, StockData>  $stocksData
     * @return int количество синхронизированных остатков
     */
    public function syncStocks(Offer $offer, array $stocksData): int
    {
        $count = 0;

        foreach ($stocksData as $stockData) {
            $command = new UpsertStockCommand($offer->id, StockData::from($stockData));
            $this->commandBus->dispatch($command);
            $count++;
        }

        return $count;
    }
}
