<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CategoryData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Nullable, StringType, Uuid]
        public ?string $parentId,
        #[Required, StringType, Max(255)]
        public string $name,
        #[Nullable, StringType]
        public ?string $code = null,
        #[Nullable, IntegerType]
        public ?int $sort = null,
        #[Nullable, BooleanType]
        public ?bool $isActive = false,
        #[Nullable, StringType, Url]
        public ?string $imageUrl = null,
        #[Nullable]
        public ?SeoMetaData $seo = null,
        #[Nullable, StringType]
        public ?Carbon $createdAt = null,
        #[Nullable, StringType]
        public ?Carbon $updatedAt = null,
    ) {}
}
