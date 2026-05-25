<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetCategoryQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\CategoryRepository;

class GetCategoryQueryHandler implements QueryHandlerInterface
{
    private CategoryRepository $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetCategoryQuery);

        return $this->repository->findOrFail($query->id);
    }
}
