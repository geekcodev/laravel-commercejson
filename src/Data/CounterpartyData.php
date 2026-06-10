<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

class CounterpartyData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Required, Enum(CounterpartyTypeEnum::class)]
        public CounterpartyTypeEnum $type,
        #[Required, StringType]
        public string $name,
        #[Nullable, StringType]
        public ?string $external_id = null,
        #[Nullable, StringType]
        public ?string $short_name = null,
        #[Nullable, StringType]
        public ?string $inn = null,
        #[Nullable, StringType, Regex('/^\d{9}$/')]
        public ?string $kpp = null,
        #[Nullable, StringType]
        public ?string $ogrn = null,
        #[Nullable, StringType]
        public ?string $okved = null,
        #[Nullable, StringType]
        public ?string $okpo = null,
        #[Nullable, StringType]
        public ?string $okopf = null,
        #[Nullable, StringType]
        public ?string $okfs = null,
        #[Nullable, StringType]
        public ?Carbon $registration_date = null,
        #[Nullable]
        public ?AddressData $legal_address = null,
        #[Nullable]
        public ?AddressData $actual_address = null,
        #[Nullable, ArrayType, DataCollectionOf(ContactData::class)]
        public ?array $contacts = null,
        #[Nullable, ArrayType, DataCollectionOf(RepresentativeData::class)]
        public ?array $representatives = null,
        #[Nullable, ArrayType, DataCollectionOf(BankAccountData::class)]
        public ?array $bank_accounts = null,
        #[Nullable, StringType, Uuid]
        public ?string $price_type_id = null,
        #[Nullable]
        public ?MoneyData $credit_limit = null,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $custom_attributes = null,
        #[Nullable, BooleanType]
        public ?bool $is_active = null,
        #[Nullable]
        public ?Carbon $created_at = null,
        #[Nullable]
        public ?Carbon $updated_at = null,
        #[Nullable]
        public ?Carbon $deleted_at = null,
    ) {}
}
