<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Queries\GetProductQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\ProductRepository;

class GetProductQueryHandler implements QueryHandlerInterface
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetProductQuery);

        /** @var Product $product */
        $product = $this->repository->findOrFail($query->id)
            ->load(['images', 'propertyValues', 'variants.propertyValues', 'customAttributes', 'analogues', 'components']);

        $product->setRelationForApi();

        return $product;
    }
}
