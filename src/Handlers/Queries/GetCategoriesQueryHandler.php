<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetCategoriesQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\CategoryRepository;

class GetCategoriesQueryHandler implements QueryHandlerInterface
{
    private CategoryRepository $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetCategoriesQuery);

        return $this->repository->paginate($query->perPage);
    }
}
