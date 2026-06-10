<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Queries\GetProductsQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GetProductsQueryHandler implements QueryHandlerInterface
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetProductsQuery);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->repository->paginate($query->perPage);

        /** @var Collection<int, Product> $collection */
        $collection = $paginator->getCollection();
        $collection->transform(function (Product $product) {
            $product->setRelationForApi();

            return $product;
        });

        return $paginator;
    }
}
