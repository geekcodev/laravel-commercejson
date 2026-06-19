<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetPriceTypesQuery extends Query
{
    public function __construct(
        public ?string $updated_after = null,
    ) {}
}
