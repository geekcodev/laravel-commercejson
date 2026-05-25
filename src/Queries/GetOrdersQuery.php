<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetOrdersQuery extends Query
{
    public function __construct(
        public int $perPage = 15
    ) {}
}
