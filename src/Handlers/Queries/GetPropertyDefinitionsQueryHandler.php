<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetPropertyDefinitionsQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\PropertyDefinitionRepository;

class GetPropertyDefinitionsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PropertyDefinitionRepository $propertyDefinitionRepository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetPropertyDefinitionsQuery);

        $qb = $this->propertyDefinitionRepository->newQuery();

        if ($query->updated_after !== null) {
            $qb->where('updated_at', '>', $query->updated_after);
        }

        return $qb->get();
    }
}
