<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\PaymentStatusEnum;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class OrderPatchPaymentData extends Data
{
    public function __construct(
        #[Nullable, Enum(PaymentStatusEnum::class)]
        public ?PaymentStatusEnum $status = null,
        #[Nullable]
        public ?MoneyData $amount = null,
        #[Nullable]
        public ?Carbon $paid_at = null,
        #[Nullable, StringType]
        public ?string $transaction_id = null,
    ) {}
}
