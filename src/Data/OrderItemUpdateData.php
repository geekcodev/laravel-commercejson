<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class OrderItemUpdateData extends Data
{
    public function __construct(
        #[Nullable, StringType, Uuid]
        public ?string $id = null,
        #[Nullable, StringType, Uuid]
        public ?string $product_id = null,
        #[Nullable, StringType, Uuid]
        public ?string $variant_id = null,
        #[Nullable, StringType, Uuid]
        public ?string $warehouse_id = null,
        #[Nullable, Numeric, GreaterThan(0)]
        public ?float $quantity = null,
        #[Nullable]
        public ?MoneyData $price = null,
    ) {}

    public static function withValidator(Validator $validator): void
    {
        $data = $validator->getData();

        $hasId = ! empty($data['id'] ?? null);
        $hasProductId = ! empty($data['product_id'] ?? null);
        $hasQuantity = ! empty($data['quantity'] ?? null);

        if (! $hasQuantity) {
            $validator->errors()->add('quantity', 'Quantity is required');
        }

        if ($hasId && ! $hasQuantity) {
            $validator->errors()->add('id', 'id requires quantity');
        }

        if ($hasProductId && ! $hasQuantity) {
            $validator->errors()->add('product_id', 'product_id requires quantity');
        }

        if (! $hasId && ! $hasProductId) {
            $validator->errors()->add('id', 'Either id or product_id is required');
            $validator->errors()->add('product_id', 'Either id or product_id is required');
        }
    }
}
