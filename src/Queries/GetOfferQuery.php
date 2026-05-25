<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetOfferQuery extends Query
{
    public function __construct(
        public string $id
    ) {}
}
