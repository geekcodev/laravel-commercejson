<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, StringType, Max(255)]
        public string $name,
        #[Nullable, StringType, Uuid]
        public ?string $parent_id = null,
        #[Nullable, StringType]
        public ?string $code = null,
        #[Nullable, IntegerType]
        public ?int $sort = null,
        #[Nullable, BooleanType]
        public ?bool $is_active = true,
        #[Nullable, StringType, Url]
        public ?string $image_url = null,
        #[Nullable]
        public ?SeoMetaData $seo = null,
        #[Nullable]
        public ?Carbon $created_at = null,
        #[Nullable]
        public ?Carbon $updated_at = null,
    ) {}
}
