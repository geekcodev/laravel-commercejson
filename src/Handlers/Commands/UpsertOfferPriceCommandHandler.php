<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertOfferPriceCommand;
use GeekCo\CommerceJson\Models\OfferPrice;
use Illuminate\Support\Facades\DB;

class UpsertOfferPriceCommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertOfferPriceCommand);

        return DB::transaction(function () use ($command) {
            return OfferPrice::updateOrCreate(
                [
                    'offer_id' => $command->offerId,
                    'price_type_id' => $command->priceData->priceTypeId,
                    'min_quantity' => $command->priceData->minQuantity ?? 0,
                ],
                [
                    'price_amount' => $command->priceData->price->amount,
                    'price_currency' => $command->priceData->price->currency,
                    'price_with_discount_amount' => $command->priceData->priceWithDiscount?->amount,
                    'discount_percent' => $command->priceData->discountPercent,
                    'unit_code' => $command->priceData->unit?->code,
                    'valid_from' => $command->priceData->validFrom,
                    'valid_to' => $command->priceData->validTo,
                ]
            );
        });
    }
}
