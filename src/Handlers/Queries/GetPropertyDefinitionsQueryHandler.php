<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetPropertyDefinitionsQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\PropertyDefinitionRepository;

class GetPropertyDefinitionsQueryHandler implements QueryHandlerInterface
{
    private PropertyDefinitionRepository $repository;

    public function __construct(PropertyDefinitionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetPropertyDefinitionsQuery);

        return $this->repository->all();
    }
}
