<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class RepresentativeData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $name,
        #[Required, StringType]
        public string $relation,
        public ?string $id = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $position = null,
    ) {}
}
