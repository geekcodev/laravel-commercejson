<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetOffersQuery extends Query
{
    public function __construct(
        public int $perPage = 15,
        public ?string $price_type_id = null,
        public ?string $warehouse_id = null,
        public ?string $updated_after = null,
        public ?bool $include_deleted = false,
    ) {}
}
