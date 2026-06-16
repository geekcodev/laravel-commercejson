<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetOrdersQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\OrderRepository;

class GetOrdersQueryHandler implements QueryHandlerInterface
{
    private OrderRepository $repository;

    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetOrdersQuery);

        $qb = $this->repository->newQuery();

        if ($query->status !== null) {
            $qb->where('status', $query->status);
        }

        if ($query->document_type !== null) {
            $qb->where('document_type', $query->document_type);
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
