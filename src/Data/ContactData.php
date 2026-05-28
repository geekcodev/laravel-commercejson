<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\ContactTypeEnum;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ContactData extends Data
{
    public function __construct(
        #[Required, StringType, Enum(ContactTypeEnum::class)]
        public string $type,
        #[Required, StringType]
        public string $value,
        #[Nullable, StringType]
        public ?string $comment = null,
    ) {}
}
