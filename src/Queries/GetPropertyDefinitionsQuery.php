<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Queries;

class GetPropertyDefinitionsQuery extends Query
{
    public function __construct(
        public ?string $updated_after = null,
    ) {}
}
