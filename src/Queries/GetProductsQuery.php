<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetProductsQuery extends Query
{
    public function __construct(
        public int $perPage = 15,
        public ?string $category_id = null,
        public ?bool $is_active = null,
        public ?string $updated_after = null,
        public ?bool $include_deleted = false,
    ) {}
}
