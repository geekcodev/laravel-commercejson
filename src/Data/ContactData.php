<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\ContactTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
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
