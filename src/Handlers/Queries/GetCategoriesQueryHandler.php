<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetCategoriesQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\CategoryRepository;

class GetCategoriesQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetCategoriesQuery);

        $qb = $this->categoryRepository->newQuery();

        if ($query->updated_after !== null) {
            $qb->where('updated_at', '>', $query->updated_after);
        }

        return $qb->get();
    }
}
