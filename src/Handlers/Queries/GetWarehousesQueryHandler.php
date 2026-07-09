<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetWarehousesQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\WarehouseRepository;

class GetWarehousesQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly WarehouseRepository $warehouseRepository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetWarehousesQuery);

        if ($query->include_deleted) {
            return $this->warehouseRepository->allWithTrashed();
        }

        return $this->warehouseRepository->all();
    }
}
