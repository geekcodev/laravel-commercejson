<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetWarehousesQuery extends Query
{
    public function __construct(
        public bool $include_deleted = false
    ) {}
}
