<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetOffersQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\OfferRepository;

class GetOffersQueryHandler implements QueryHandlerInterface
{
    private OfferRepository $repository;

    public function __construct(OfferRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetOffersQuery);

        return $this->repository->paginate($query->perPage);
    }
}
