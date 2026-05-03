<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Data\OfferImportData;
use GeekCo\CommerceJson\Data\OfferListData;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Models\Offer;

/**
 * Сервис для работы с предложениями (цены и остатки)
 */
class OfferService
{
    public function __construct(
        protected CommerceJsonConnector $connector
    ) {}

    /**
     * Получить список предложений с пагинацией
     */
    public function getOffers(
        int $page = 1,
        int $limit = 100,
        ?string $priceTypeId = null,
        ?string $warehouseId = null,
        ?\DateTime $updatedAfter = null
    ): OfferListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'price_type_id' => $priceTypeId,
            'warehouse_id' => $warehouseId,
            'updated_after' => $updatedAfter?->format(\DateTime::ATOM),
        ]);

        $response = $this->connector->get('/offers', $query);

        return OfferListData::from(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Импортировать предложения (цены и остатки)
     */
    public function importOffers(OfferImportData $importData, ?string $idempotencyKey = null): ImportResultData
    {
        $response = $this->connector->post(
            '/offers',
            $importData->toArray(),
            $idempotencyKey
        );

        return ImportResultData::from(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Получить справочник типов цен
     *
     * @return array<int, array{id: string, name: string, currency: string, description: ?string, is_default: bool}>
     */
    public function getPriceTypes(): array
    {
        $response = $this->connector->get('/offers/price-types');
        $responseData = json_decode($response->getBody()->getContents(), true);

        return $responseData['price_types'];
    }

    /**
     * Получить справочник складов
     *
     * @return array<int, array{id: string, name: string, code: ?string, address: array, is_active: bool, is_default: bool}>
     */
    public function getWarehouses(): array
    {
        $response = $this->connector->get('/warehouses');
        $responseData = json_decode($response->getBody()->getContents(), true);

        return $responseData['warehouses'];
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
        return Offer::updateOrCreate(
            [
                'product_id' => $offerData->productId,
                'variant_id' => $offerData->variantId,
            ],
            [
                'updated_at' => now(),
            ]
        );
    }
}
