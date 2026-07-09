<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertOfferPriceCommand;
use GeekCo\CommerceJson\Repositories\OfferPriceRepository;
use Illuminate\Support\Facades\DB;

class UpsertOfferPriceCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OfferPriceRepository $offerPriceRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertOfferPriceCommand);

        return DB::transaction(function () use ($command) {
            return $this->offerPriceRepository->updateOrCreate(
                [
                    'offer_id' => $command->offerId,
                    'price_type_id' => $command->priceData->price_type_id,
                    'min_quantity' => $command->priceData->min_quantity ?? 0,
                ],
                [
                    'price_amount' => $command->priceData->price->amount,
                    'price_currency' => $command->priceData->price->currency,
                    'price_with_discount_amount' => $command->priceData->price_with_discount?->amount,
                    'price_with_discount_currency' => $command->priceData->price_with_discount?->currency,
                    'discount_percent' => $command->priceData->discount_percent,
                    'unit_code' => $command->priceData->unit?->code,
                    'unit_short_name' => $command->priceData->unit?->short_name,
                    'unit_full_name' => $command->priceData->unit?->full_name,
                    'unit_international' => $command->priceData->unit?->international,
                    'valid_from' => $command->priceData->valid_from,
                    'valid_to' => $command->priceData->valid_to,
                ]
            );
        });
    }
}
