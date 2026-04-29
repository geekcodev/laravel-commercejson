<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OrderBulkUpdateItemData extends Data
{
    public function __construct(
        #[Nullable, StringType, Uuid]
        public ?string $id = null,
        #[Nullable, StringType]
        public ?string $externalId = null,
        #[Nullable, Enum(OrderStatusEnum::class)]
        public ?OrderStatusEnum $status = null,
        #[Nullable, StringType]
        public ?string $comment = null,
        #[Nullable]
        public mixed $delivery = null,
        #[Nullable, ArrayType, Min(1), DataCollectionOf(OrderItemUpdateData::class)]
        public ?array $items = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $customAttributes = null,
    ) {}
}
