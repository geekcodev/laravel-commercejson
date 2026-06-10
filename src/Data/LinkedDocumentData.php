<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\DocumentTypeEnum;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class LinkedDocumentData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, Enum(DocumentTypeEnum::class)]
        public DocumentTypeEnum $type,
        #[Nullable, StringType]
        public ?string $external_id = null,
    ) {}
}
