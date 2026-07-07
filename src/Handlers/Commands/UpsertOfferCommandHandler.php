<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertOfferCommand;
use GeekCo\CommerceJson\Data\OfferPriceData;
use GeekCo\CommerceJson\Data\StockData;
use GeekCo\CommerceJson\Repositories\OfferPriceRepository;
use GeekCo\CommerceJson\Repositories\OfferRepository;
use GeekCo\CommerceJson\Repositories\PriceTypeRepository;
use GeekCo\CommerceJson\Repositories\StockRepository;
use GeekCo\CommerceJson\Repositories\WarehouseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpsertOfferCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OfferRepository $offerRepository,
        private readonly OfferPriceRepository $offerPriceRepository,
        private readonly StockRepository $stockRepository,
        private readonly PriceTypeRepository $priceTypeRepository,
        private readonly WarehouseRepository $warehouseRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertOfferCommand);

        $data = $command->offerData;

        return DB::transaction(function () use ($data) {
            $offer = $this->offerRepository->updateOrCreate(
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

    private function syncPrices(string $offerId, array $pricesData): void
    {
        $priceTypeIds = array_map(
            fn (OfferPriceData $p) => $p->price_type_id,
            $pricesData
        );

        $existingPriceTypes = $this->priceTypeRepository
            ->newQuery()
            ->whereIn('id', $priceTypeIds)
            ->pluck('id')
            ->all();

        foreach ($pricesData as $priceData) {
            assert($priceData instanceof OfferPriceData);

            if (! in_array($priceData->price_type_id, $existingPriceTypes, true)) {
                Log::warning('commercejson.price_type_not_found', [
                    'price_type_id' => $priceData->price_type_id,
                    'offer_id' => $offerId,
                ]);

                continue;
            }

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

        $warehouseIds = array_map(
            fn (StockData $s) => $s->warehouse_id,
            $stocksData
        );

        $existingWarehouses = $this->warehouseRepository
            ->newQuery()
            ->whereIn('id', $warehouseIds)
            ->pluck('id')
            ->all();

        foreach ($stocksData as $stockData) {
            assert($stockData instanceof StockData);

            if (! in_array($stockData->warehouse_id, $existingWarehouses, true)) {
                Log::warning('commercejson.stock_warehouse_not_found', [
                    'warehouse_id' => $stockData->warehouse_id,
                    'offer_id' => $offerId,
                ]);

                continue;
            }

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
