<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetOrderQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\OrderRepository;

class GetOrderQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetOrderQuery);

        return $this->orderRepository->newQuery()
            ->with(['items', 'linkedDocuments'])
            ->findOrFail($query->id);
    }
}
