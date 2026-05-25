<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetProductsQuery extends Query
{
    public function __construct(
        public int $perPage = 15
    ) {}
}
