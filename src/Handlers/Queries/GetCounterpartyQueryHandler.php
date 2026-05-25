<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetCounterpartyQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;

class GetCounterpartyQueryHandler implements QueryHandlerInterface
{
    private CounterpartyRepository $repository;

    public function __construct(CounterpartyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetCounterpartyQuery);

        return $this->repository->findOrFail($query->id);
    }
}
