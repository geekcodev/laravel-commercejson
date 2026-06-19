<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OrderBulkUpdateItemData extends Data
{
    public function __construct(
        #[Nullable, StringType, Uuid]
        public ?string $id = null,
        #[Nullable, StringType]
        public ?string $external_id = null,
        #[Nullable, Enum(OrderStatusEnum::class)]
        public ?OrderStatusEnum $status = null,
        #[Nullable, StringType]
        public ?string $comment = null,
        #[Nullable]
        public ?OrderDeliveryTrackData $delivery = null,
        #[Nullable, ArrayType, Min(1), DataCollectionOf(OrderItemUpdateData::class)]
        public ?array $items = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $custom_attributes = null,
    ) {}

    public static function withValidator(Validator $validator): void
    {
        $data = $validator->getData();

        $hasId = ! empty($data['id'] ?? null);
        $hasExternalId = ! empty($data['external_id'] ?? null);

        if (! $hasId && ! $hasExternalId) {
            $validator->errors()->add('id', 'Either id or external_id is required');
            $validator->errors()->add('external_id', 'Either id or external_id is required');
        }
    }
}
