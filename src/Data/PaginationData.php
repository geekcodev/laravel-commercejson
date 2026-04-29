<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class PaginationData extends Data
{
    public function __construct(
        #[Required, Numeric, Min(1)]
        public int $page,
        #[Required, Numeric, Min(1)]
        public int $limit,
        #[Required, Numeric, Min(0)]
        public int $total,
        #[Required, BooleanType]
        public bool $hasNext
    ) {}
}
