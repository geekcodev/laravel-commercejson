<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetProductQuery extends Query
{
    public function __construct(
        public string $id
    ) {}
}
