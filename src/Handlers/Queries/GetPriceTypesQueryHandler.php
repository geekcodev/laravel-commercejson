<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetPriceTypesQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\PriceTypeRepository;

class GetPriceTypesQueryHandler implements QueryHandlerInterface
{
    private PriceTypeRepository $repository;

    public function __construct(PriceTypeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetPriceTypesQuery);

        return $this->repository->all();
    }
}
