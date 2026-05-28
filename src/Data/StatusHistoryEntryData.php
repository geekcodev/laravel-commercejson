<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StatusHistoryEntryData extends Data
{
    public function __construct(
        #[Required, Enum(OrderStatusEnum::class)]
        public OrderStatusEnum $status,
        #[Required, StringType]
        public Carbon $changed_at,
        #[Nullable, StringType]
        public ?string $changed_by = null,
        #[Nullable, StringType]
        public ?string $comment = null,
    ) {}
}
