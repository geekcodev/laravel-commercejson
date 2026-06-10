<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertOfferCommand;
use GeekCo\CommerceJson\Data\OfferPriceData;
use GeekCo\CommerceJson\Data\StockData;
use GeekCo\CommerceJson\Repositories\OfferPriceRepository;
use GeekCo\CommerceJson\Repositories\OfferRepository;
use GeekCo\CommerceJson\Repositories\StockRepository;
use Illuminate\Support\Facades\DB;

class UpsertOfferCommandHandler implements CommandHandlerInterface
{
    private OfferRepository $repository;

    private OfferPriceRepository $offerPriceRepository;

    private StockRepository $stockRepository;

    public function __construct(
        OfferRepository $repository,
        OfferPriceRepository $offerPriceRepository,
        StockRepository $stockRepository,
    ) {
        $this->repository = $repository;
        $this->offerPriceRepository = $offerPriceRepository;
        $this->stockRepository = $stockRepository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertOfferCommand);

        $data = $command->offerData;

        return DB::transaction(function () use ($data) {
            $offer = $this->repository->updateOrCreate(
                [
                    'product_id' => $data->product_id,
                    'variant_id' => $data->variant_id,
                ],
                [
                    'product_id' => $data->product_id,
                    'variant_id' => $data->variant_id,
                ]
            );

            $this->syncPrices($offer->id, $data->prices);
            $this->syncStocks($offer->id, $data->stocks);

            return $offer;
        });
    }

    private function syncPrices(string $offerId, ?array $pricesData): void
    {
        if ($pricesData === null) {
            return;
        }

        foreach ($pricesData as $priceData) {
            assert($priceData instanceof OfferPriceData);

            $this->offerPriceRepository->updateOrCreate(
                [
                    'offer_id' => $offerId,
                    'price_type_id' => $priceData->price_type_id,
                    'min_quantity' => $priceData->min_quantity ?? 0,
                ],
                [
                    'price_amount' => $priceData->price->amount,
                    'price_currency' => $priceData->price->currency,
                    'price_with_discount_amount' => $priceData->price_with_discount?->amount,
                    'price_with_discount_currency' => $priceData->price_with_discount?->currency,
                    'discount_percent' => $priceData->discount_percent,
                    'unit_code' => $priceData->unit?->code,
                    'unit_short_name' => $priceData->unit?->short_name,
                    'unit_full_name' => $priceData->unit?->full_name,
                    'unit_international' => $priceData->unit?->international,
                    'valid_from' => $priceData->valid_from,
                    'valid_to' => $priceData->valid_to,
                ]
            );
        }
    }

    private function syncStocks(string $offerId, ?array $stocksData): void
    {
        if ($stocksData === null) {
            return;
        }

        foreach ($stocksData as $stockData) {
            assert($stockData instanceof StockData);

            $this->stockRepository->updateOrCreate(
                [
                    'offer_id' => $offerId,
                    'warehouse_id' => $stockData->warehouse_id,
                ],
                [
                    'quantity' => $stockData->quantity,
                    'quantity_reserved' => $stockData->quantity_reserved,
                ]
            );
        }
    }
}
