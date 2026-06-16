<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetCounterpartiesQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;

class GetCounterpartiesQueryHandler implements QueryHandlerInterface
{
    private CounterpartyRepository $repository;

    public function __construct(CounterpartyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetCounterpartiesQuery);

        $qb = $this->repository->newQuery();

        if ($query->type !== null) {
            $qb->where('type', $query->type);
        }

        if ($query->updated_after !== null) {
            $qb->where('updated_at', '>', $query->updated_after);
        }

        if ($query->include_deleted) {
            $qb->withTrashed();
        }

        return $qb->paginate($query->perPage);
    }
}
