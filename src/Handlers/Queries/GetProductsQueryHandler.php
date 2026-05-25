<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetProductsQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\ProductRepository;

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

        return $this->repository->paginate($query->perPage);
    }
}
