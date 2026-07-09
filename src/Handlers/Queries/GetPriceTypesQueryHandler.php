<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetPriceTypesQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\PriceTypeRepository;

class GetPriceTypesQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PriceTypeRepository $priceTypeRepository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetPriceTypesQuery);

        $qb = $this->priceTypeRepository->newQuery();

        if ($query->updated_after !== null) {
            $qb->where('updated_at', '>', $query->updated_after);
        }

        return $qb->get();
    }
}
