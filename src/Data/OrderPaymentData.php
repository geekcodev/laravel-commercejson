<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\PaymentMethodEnum;
use GeekCo\CommerceJson\Enums\PaymentStatusEnum;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OrderPaymentData extends Data
{
    public function __construct(
        #[Required, Enum(PaymentMethodEnum::class)]
        public PaymentMethodEnum $type,
        #[Nullable, Enum(PaymentStatusEnum::class)]
        public ?PaymentStatusEnum $status = null,
        #[Nullable]
        public ?MoneyData $amount = null,
        #[Nullable]
        public ?Carbon $paid_at = null,
    ) {}
}
