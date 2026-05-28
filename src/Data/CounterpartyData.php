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
        #[Nullable, StringType]
        public ?string $external_id,
        #[Required, Enum(CounterpartyTypeEnum::class)]
        public CounterpartyTypeEnum $type,
        #[Required, StringType]
        public string $name,
        #[Nullable, StringType]
        public ?string $short_name,
        #[Nullable, StringType]
        public ?string $inn,
        #[Nullable, StringType, Regex('/^\d{9}$/')]
        public ?string $kpp,
        #[Nullable, StringType]
        public ?string $ogrn,
        #[Nullable, StringType]
        public ?string $okved,
        #[Nullable, StringType]
        public ?string $okpo,
        #[Nullable, StringType]
        public ?string $okopf,
        #[Nullable, StringType]
        public ?string $okfs,
        #[Nullable, StringType]
        public ?Carbon $registration_date,
        #[Nullable]
        public ?AddressData $legal_address,
        #[Nullable]
        public ?AddressData $actual_address,
        #[Nullable, ArrayType, DataCollectionOf(ContactData::class)]
        public ?array $contacts,
        #[Nullable, ArrayType]
        public ?array $representatives,
        #[Nullable, ArrayType, DataCollectionOf(BankAccountData::class)]
        public ?array $bank_accounts,
        #[Nullable, StringType, Uuid]
        public ?string $price_type_id,
        #[Nullable]
        public ?MoneyData $credit_limit,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $custom_attributes,
        #[Nullable, BooleanType]
        public ?bool $is_active,
        #[Nullable, StringType]
        public ?Carbon $created_at,
        #[Nullable, StringType]
        public ?Carbon $updated_at,
        #[Nullable, StringType]
        public ?Carbon $deleted_at,
    ) {}
}
