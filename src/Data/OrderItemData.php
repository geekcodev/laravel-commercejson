<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OrderItemData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, StringType, Uuid]
        public string $productId,
        #[Nullable, StringType, Uuid]
        public ?string $variantId,
        #[Nullable, StringType]
        public ?string $productName,
        #[Nullable, StringType]
        public ?string $productCode,
        #[Required, Numeric, GreaterThan(0)]
        public float $quantity,
        #[Nullable]
        public ?UnitData $unit,
        #[Required]
        public MoneyData $price,
        #[Nullable]
        public ?MoneyData $discount,
        #[Required]
        public MoneyData $total,
        #[Nullable, StringType, Regex('/^[A-Z]{2}$/')]
        public ?string $countryOfOrigin = null,
        #[Nullable, StringType, Max(50)]
        public ?string $customsDeclarationNumber = null,
        #[Nullable, Numeric, Min(0)]
        public ?float $taxRate = null,
        #[Nullable, ArrayType]
        public ?array $taxes = null,
        #[Nullable]
        public ?MoneyData $excisePerUnit = null,
        #[Nullable]
        public ?MoneyData $taxAmount = null,
    ) {}
}
