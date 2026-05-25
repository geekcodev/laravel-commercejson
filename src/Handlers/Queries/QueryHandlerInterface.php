<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\QueryInterface;

interface QueryHandlerInterface
{
    public function handle(QueryInterface $query): mixed;
}
