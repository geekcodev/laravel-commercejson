<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Data\OfferListData;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Data\ProductListData;
use GeekCo\CommerceJson\Events\ProductDeactivated;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Support\Mappers\ProductMapper;
use Illuminate\Support\Collection;

/**
 * Сервис для работы с товарами
 */
class ProductService
{
    public function __construct(
        protected CommerceJsonConnector $connector,
        protected ProductMapper $mapper
    ) {}

    /**
     * Получить список товаров с пагинацией
     *
     * @param  array<string, mixed>  $filters
     */
    public function getProducts(
        int $page = 1,
        int $limit = 100,
        ?string $categoryId = null,
        ?bool $isActive = null,
        ?\DateTime $updatedAfter = null
    ): ProductListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'category_id' => $categoryId,
            'is_active' => $isActive,
            'updated_after' => $updatedAfter?->format(\DateTime::ATOM),
        ]);

        $response = $this->connector->get('/catalog/products', $query);

        return ProductListData::from($response->json());
    }

    /**
     * Получить товар по ID
     */
    public function getProduct(string $id): ProductData
    {
        $response = $this->connector->get("/catalog/products/{$id}");

        return ProductData::from($response->json());
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

        $response = $this->connector->post(
            '/catalog/products',
            ['products' => $productsArray],
            $idempotencyKey
        );

        return ImportResultData::from($response->json());
    }

    /**
     * Деактивировать товар (soft delete)
     */
    public function deactivateProduct(string $id): ProductData
    {
        $response = $this->connector->delete("/catalog/products/{$id}");

        // Dispatch event
        event(new ProductDeactivated($id));

        return ProductData::from($response->json());
    }

    /**
     * Получить все товары (ленивая генерация)
     *
     * @return \Generator<ProductData>
     */
    public function lazyGetProducts(?\DateTime $updatedAfter = null): \Generator
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
        } while ($productList->pagination->hasNext);
    }

    /**
     * Получить все товары как коллекцию
     *
     * @return Collection<int, ProductData>
     */
    public function getAllProducts(?\DateTime $updatedAfter = null): Collection
    {
        return Collection::make(iterator_to_array($this->lazyGetProducts($updatedAfter)));
    }

    /**
     * Синхронизировать товар с локальной БД
     */
    public function syncProduct(ProductData $productData): Product
    {
        return $this->mapper->toModel($productData);
    }

    /**
     * Получить предложения (для ProductImporter)
     */
    public function getOffers(
        int $page = 1,
        int $limit = 200,
        ?\DateTime $updatedAfter = null
    ): OfferListData {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'updated_after' => $updatedAfter?->format(\DateTime::ATOM),
        ]);

        $response = $this->connector->get('/offers', $query);

        return OfferListData::from($response->json());
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
            []
        );
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
