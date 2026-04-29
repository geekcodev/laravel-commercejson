<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exchange\Import;

use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Support\Mappers\ProductMapper;

/**
 * Импортер товаров
 */
class ProductImporter
{
    public function __construct(
        protected ProductService $productService,
        protected ProductMapper $mapper
    ) {}

    /**
     * Импортировать товары
     *
     * @return array{imported: int, failed: int}
     */
    public function import(?\DateTime $updatedAfter = null): array
    {
        $stats = ['imported' => 0, 'failed' => 0];

        $page = 1;
        do {
            $productList = $this->productService->getProducts(
                page: $page,
                limit: 100,
                updatedAfter: $updatedAfter
            );

            foreach ($productList->products as $productData) {
                try {
                    $this->mapper->toModel($productData);
                    $stats['imported']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    logger()->error("Failed to import product {$productData->id}: ".$e->getMessage());
                }
            }

            $page++;
        } while ($productList->pagination->hasNext);

        return $stats;
    }

    /**
     * Импортировать предложения (цены и остатки)
     *
     * @return array{imported: int, failed: int}
     */
    public function importOffers(?\DateTime $updatedAfter = null): array
    {
        $stats = ['imported' => 0, 'failed' => 0];

        $page = 1;
        do {
            $offerList = $this->productService->getOffers(
                page: $page,
                limit: 200,
                updatedAfter: $updatedAfter
            );

            foreach ($offerList->offers as $offerData) {
                try {
                    // Синхронизация предложения
                    $offer = $this->productService->syncOffer($offerData);
                    $stats['imported']++;

                    // Синхронизация цен и остатков
                    // ... (детальная реализация)
                } catch (\Exception $e) {
                    $stats['failed']++;
                    logger()->error('Failed to import offer: '.$e->getMessage());
                }
            }

            $page++;
        } while ($offerList->pagination->hasNext);

        return $stats;
    }
}
