<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetCounterpartiesQuery extends Query
{
    public function __construct(
        public int $perPage = 15
    ) {}
}
