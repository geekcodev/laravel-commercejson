<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetOrdersQuery extends Query
{
    public function __construct(
        public int $perPage = 15,
        public ?string $status = null,
        public ?string $document_type = null,
        public ?string $updated_after = null,
        public bool $include_deleted = false,
    ) {}
}
