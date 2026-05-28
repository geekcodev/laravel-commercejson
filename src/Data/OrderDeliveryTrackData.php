<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class OrderDeliveryTrackData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public ?string $tracking_number = null,
        #[Nullable]
        public ?Carbon $shipped_at = null,
        #[Nullable]
        public ?Carbon $estimated_date = null,
    ) {}
}
