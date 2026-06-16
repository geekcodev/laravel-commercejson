<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetCounterpartiesQuery extends Query
{
    public function __construct(
        public int $perPage = 15,
        public ?string $type = null,
        public ?string $updated_after = null,
        public ?bool $include_deleted = false,
    ) {}
}
