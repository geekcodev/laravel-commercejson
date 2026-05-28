<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use DateTimeInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\UpsertOfferCommand;
use GeekCo\CommerceJson\Commands\UpsertProductCommand;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Data\OfferListData;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Data\ProductListData;
use GeekCo\CommerceJson\Events\ProductDeactivated;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\Product;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Collection;

/**
 * Сервис для работы с товарами
 */
class ProductService implements ServiceInterface
{
    public function __construct(
        protected HttpClientInterface $http,
        protected Dispatcher $commandBus,
        protected QueryBusInterface $queryBus
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

    public function getCommandBus(): Dispatcher
    {
        return $this->commandBus;
    }

    /**
     * Получить список товаров с пагинацией
     */
    public function getProducts(
        int $page = 1,
        int $limit = 100,
        ?string $categoryId = null,
        ?bool $isActive = null,
        ?DateTimeInterface $updatedAfter = null
    ): ProductListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'category_id' => $categoryId,
            'is_active' => $isActive,
            'updated_after' => $updatedAfter?->format(DateTimeInterface::ATOM),
        ]);

        $configPath = config('commercejson.external_api_endpoints.products', '/catalog/products');
        $response = $this->http->get($configPath, $query);

        return ProductListData::from($response->data);
    }

    /**
     * Получить товар по ID
     */
    public function getProduct(string $id): ProductData
    {
        $configPath = config('commercejson.external_api_endpoints.products', '/catalog/products');
        $response = $this->http->get("{$configPath}/{$id}");

        return ProductData::from($response->data);
    }

    /**
     * Импортировать товары (upsert)
     *
     * @param  array<ProductData|array>  $products
     */
    public function importProducts(array $products, ?string $idempotencyKey = null): ImportResultData
    {
        $productsArray = array_map(function ($product) {
            return $product instanceof ProductData
                ? $product->toArray()
                : $product;
        }, $products);

        $configPath = config('commercejson.external_api_endpoints.products', '/catalog/products');
        $response = $this->http->post(
            $configPath,
            ['products' => $productsArray],
            $idempotencyKey
        );

        return ImportResultData::from($response->data);
    }

    /**
     * Деактивировать товар (soft delete)
     */
    public function deactivateProduct(string $id): ProductData
    {
        $configPath = config('commercejson.external_api_endpoints.products', '/catalog/products');
        $response = $this->http->delete("{$configPath}/{$id}");

        // Dispatch event
        event(new ProductDeactivated($id));

        return ProductData::from($response->data);
    }

    /**
     * Получить все товары (ленивая генерация)
     *
     * @return \Generator<ProductData>
     */
    public function lazyGetProducts(?DateTimeInterface $updatedAfter = null): \Generator
    {
        $page = 1;
        $limit = 100;

        do {
            $productList = $this->getProducts(
                page: $page,
                limit: $limit,
                updatedAfter: $updatedAfter
            );

            foreach ($productList->products as $product) {
                yield $product;
            }

            $page++;
        } while ($productList->pagination->has_next);
    }

    /**
     * Получить все товары как коллекцию
     *
     * @return Collection<int, ProductData>
     */
    public function getAllProducts(?DateTimeInterface $updatedAfter = null): Collection
    {
        return Collection::make(iterator_to_array($this->lazyGetProducts($updatedAfter)));
    }

    /**
     * Синхронизировать товар с локальной БД
     */
    public function syncProduct(ProductData $productData): Product
    {
        $command = new UpsertProductCommand($productData);

        return $this->commandBus->dispatch($command);
    }

    /**
     * Получить предложения (для ProductImporter)
     */
    public function getOffers(
        int $page = 1,
        int $limit = 200,
        ?DateTimeInterface $updatedAfter = null
    ): OfferListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'updated_after' => $updatedAfter?->format(DateTimeInterface::ATOM),
        ]);

        $configPath = config('commercejson.external_api_endpoints.offers', '/offers');
        $response = $this->http->get($configPath, $query);

        return OfferListData::from($response->data);
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
     * Пакетная синхронизация товаров
     *
     * @param  array<ProductData>  $products
     * @return array{created: int, updated: int}
     */
    public function syncProducts(array $products): array
    {
        $stats = ['created' => 0, 'updated' => 0];

        foreach ($products as $productData) {
            $product = $this->syncProduct($productData);

            if ($product->wasRecentlyCreated) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }
        }

        return $stats;
    }
}
