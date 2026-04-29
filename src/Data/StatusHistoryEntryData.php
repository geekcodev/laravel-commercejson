<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class StatusHistoryEntryData extends Data
{
    public function __construct(
        #[Required, Enum(OrderStatusEnum::class)]
        public OrderStatusEnum $status,
        #[Required, StringType]
        public Carbon $changedAt,
        #[Nullable, StringType]
        public ?string $changedBy = null,
        #[Nullable, StringType]
        public ?string $comment = null,
    ) {}
}
