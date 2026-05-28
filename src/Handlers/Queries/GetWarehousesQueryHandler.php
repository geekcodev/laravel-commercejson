<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetWarehousesQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\WarehouseRepository;

class GetWarehousesQueryHandler implements QueryHandlerInterface
{
    private WarehouseRepository $repository;

    public function __construct(WarehouseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetWarehousesQuery);

        if ($query->includeDeleted) {
            return $this->repository->allWithTrashed();
        }

        return $this->repository->all();
    }
}
