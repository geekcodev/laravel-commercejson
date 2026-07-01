<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use GeekCo\CommerceJson\Enums\CounterpartyTypeEnum;
use GeekCo\CommerceJson\Models\Counterparty;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

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
        #[Nullable]
        public ?MoneyData $credit_limit_remaining = null,
        #[Nullable, IntegerType, Min(0)]
        public ?int $payment_deferral_days = null,
        #[Nullable]
        public ?MoneyData $outstanding_debt = null,
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

    public static function fromModel(Counterparty $model): static
    {
        $data = [
            'id' => $model->id,
            'type' => $model->type,
            'name' => $model->name,
            'external_id' => $model->external_id,
            'short_name' => $model->short_name,
            'inn' => $model->inn,
            'kpp' => $model->kpp,
            'ogrn' => $model->ogrn,
            'okved' => $model->okved,
            'okpo' => $model->okpo,
            'okopf' => $model->okopf,
            'okfs' => $model->okfs,
            'registration_date' => $model->registration_date,
            'price_type_id' => $model->price_type_id,
            'is_active' => $model->is_active,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
            'deleted_at' => $model->deleted_at,
        ];

        if ($model->legal_address_country) {
            $data['legal_address'] = AddressData::from([
                'country' => $model->legal_address_country,
                'region' => $model->legal_address_region,
                'district' => $model->legal_address_district,
                'city' => $model->legal_address_city,
                'street' => $model->legal_address_street,
                'house' => $model->legal_address_house,
                'building' => $model->legal_address_building,
                'apartment' => $model->legal_address_apartment,
                'postal_code' => $model->legal_address_postal_code,
                'full' => $model->legal_address_full,
            ]);
        }

        if ($model->actual_address_country) {
            $data['actual_address'] = AddressData::from([
                'country' => $model->actual_address_country,
                'region' => $model->actual_address_region,
                'district' => $model->actual_address_district,
                'city' => $model->actual_address_city,
                'street' => $model->actual_address_street,
                'house' => $model->actual_address_house,
                'building' => $model->actual_address_building,
                'apartment' => $model->actual_address_apartment,
                'postal_code' => $model->actual_address_postal_code,
                'full' => $model->actual_address_full,
            ]);
        }

        if ($model->credit_limit_amount !== null && $model->credit_limit_currency !== null) {
            $data['credit_limit'] = MoneyData::from([
                'amount' => (string) $model->credit_limit_amount,
                'currency' => $model->credit_limit_currency,
            ]);
        }

        if ($model->credit_limit_remaining_amount !== null && $model->credit_limit_remaining_currency !== null) {
            $data['credit_limit_remaining'] = MoneyData::from([
                'amount' => (string) $model->credit_limit_remaining_amount,
                'currency' => $model->credit_limit_remaining_currency,
            ]);
        }

        $data['payment_deferral_days'] = $model->payment_deferral_days;

        if ($model->outstanding_debt_amount !== null && $model->outstanding_debt_currency !== null) {
            $data['outstanding_debt'] = MoneyData::from([
                'amount' => (string) $model->outstanding_debt_amount,
                'currency' => $model->outstanding_debt_currency,
            ]);
        }

        if ($model->relationLoaded('contacts')) {
            $data['contacts'] = ContactData::collect($model->contacts, DataCollection::class);
        }

        if ($model->relationLoaded('representatives')) {
            $data['representatives'] = RepresentativeData::collect($model->representatives, DataCollection::class);
        }

        if ($model->relationLoaded('bank_accounts')) {
            $data['bank_accounts'] = BankAccountData::collect($model->bank_accounts, DataCollection::class);
        }

        if ($model->relationLoaded('custom_attributes')) {
            $data['custom_attributes'] = CustomAttributeData::collect($model->custom_attributes, DataCollection::class);
        }

        return static::from($data);
    }
}
