<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CounterpartyData extends Data
{
    public function __construct(
        #[Required, StringType, Uuid]
        public string $id,
        #[Nullable, StringType]
        public ?string $externalId,
        #[Required, Enum(CounterpartyTypeEnum::class)]
        public CounterpartyTypeEnum $type,
        #[Required, StringType]
        public string $name,
        #[Nullable, StringType]
        public ?string $shortName,
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
        public ?Carbon $registrationDate,
        #[Nullable]
        public ?AddressData $legalAddress,
        #[Nullable]
        public ?AddressData $actualAddress,
        #[Nullable, ArrayType, DataCollectionOf(ContactData::class)]
        public ?array $contacts,
        #[Nullable, ArrayType]
        public ?array $representatives,
        #[Nullable, ArrayType, DataCollectionOf(BankAccountData::class)]
        public ?array $bankAccounts,
        #[Nullable, StringType, Uuid]
        public ?string $priceTypeId,
        #[Nullable]
        public ?MoneyData $creditLimit,
        #[Nullable, ArrayType, DataCollectionOf(CustomAttributeData::class)]
        public ?array $customAttributes,
        #[Nullable, BooleanType]
        public ?bool $isActive,
        #[Nullable, StringType]
        public ?Carbon $createdAt,
        #[Nullable, StringType]
        public ?Carbon $updatedAt,
        #[Nullable, StringType]
        public ?Carbon $deletedAt,
    ) {}
}
