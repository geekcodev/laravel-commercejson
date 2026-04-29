<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Commands;

use GeekCo\CommerceJson\Console\Concerns\InteractsWithExchange;
use GeekCo\CommerceJson\Events\OffersImported;
use GeekCo\CommerceJson\Models\OfferPrice;
use GeekCo\CommerceJson\Models\Stock;
use GeekCo\CommerceJson\Services\OfferService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Команда: Импорт предложений (цены и остатки)
 */
class ImportOffersCommand extends Command
{
    use InteractsWithExchange;

    protected $signature = 'commercejson:import-offers
                            {--page=1 : Номер страницы}
                            {--limit=100 : Количество на странице}
                            {--price-type= : ID типа цены}
                            {--warehouse= : ID склада}
                            {--updated-after= : Дата для инкрементального импорта}
                            {--queue : Использовать очередь}
                            {--no-sync : Не синхронизировать с БД}';

    protected $description = 'Импортировать предложения (цены и остатки)';

    public function handle(OfferService $offerService): int
    {
        $this->info('Starting offers import...');

        return $this->withErrorHandling(function () use ($offerService) {
            // Проверка соединения
            if (! $this->checkConnection()) {
                return 1;
            }

            $page = (int) $this->option('page');
            $limit = min((int) $this->option('limit'), 1000);
            $priceTypeId = $this->option('price-type');
            $warehouseId = $this->option('warehouse');
            $updatedAfter = $this->option('updated-after')
                ? new \DateTime($this->option('updated-after'))
                : null;

            $this->newLine();
            $this->info("Fetching offers (page: {$page}, limit: {$limit})...");

            if ($priceTypeId) {
                $this->line("Price type filter: {$priceTypeId}");
            }

            if ($warehouseId) {
                $this->line("Warehouse filter: {$warehouseId}");
            }

            if ($updatedAfter) {
                $this->line('Updated after: '.$updatedAfter->format('Y-m-d H:i:s'));
            }

            // Получение предложений
            $offerList = $offerService->getOffers(
                page: $page,
                limit: $limit,
                priceTypeId: $priceTypeId,
                warehouseId: $warehouseId,
                updatedAfter: $updatedAfter
            );

            $offersCount = count($offerList->offers);
            $this->line("Received: {$offersCount} offers");

            if ($this->option('no-sync')) {
                $this->warn('Skipping database sync (--no-sync flag)');

                return 0;
            }

            // Синхронизация с БД
            $this->newLine();
            $this->info('Synchronizing offers with database...');

            $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

            $this->withProgressBar($offerList->offers, function ($offerData) use ($stats) {
                try {
                    DB::transaction(function () use ($offerData, $stats) {
                        // Синхронизация предложения
                        $offer = $offerService->syncOffer($offerData);

                        if ($offer->wasRecentlyCreated) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }

                        // Синхронизация цен
                        if (! empty($offerData->prices)) {
                            foreach ($offerData->prices as $priceData) {
                                OfferPrice::updateOrCreate(
                                    [
                                        'offer_id' => $offer->id,
                                        'price_type_id' => $priceData->priceTypeId,
                                        'min_quantity' => $priceData->minQuantity ?? 0,
                                    ],
                                    [
                                        'price_amount' => $priceData->price->amount,
                                        'price_currency' => $priceData->price->currency,
                                        'price_with_discount_amount' => $priceData->priceWithDiscount?->amount,
                                        'price_with_discount_currency' => $priceData->priceWithDiscount?->currency,
                                        'discount_percent' => $priceData->discountPercent,
                                        'unit_code' => $priceData->unit?->code,
                                        'unit_short_name' => $priceData->unit?->shortName,
                                        'unit_full_name' => $priceData->unit?->fullName,
                                        'unit_international' => $priceData->unit?->international,
                                        'valid_from' => $priceData->validFrom,
                                        'valid_to' => $priceData->validTo,
                                    ]
                                );
                            }
                        }

                        // Синхронизация остатков
                        if (! empty($offerData->stocks)) {
                            foreach ($offerData->stocks as $stockData) {
                                Stock::updateOrCreate(
                                    [
                                        'offer_id' => $offer->id,
                                        'warehouse_id' => $stockData->warehouseId,
                                    ],
                                    [
                                        'quantity' => $stockData->quantity,
                                        'quantity_reserved' => $stockData->quantityReserved,
                                    ]
                                );
                            }
                        }
                    });
                } catch (\Exception $e) {
                    $stats['failed']++;
                    if ($this->option('verbose')) {
                        $this->error('Failed to sync offer: '.$e->getMessage());
                    }
                }
            });

            // Итоговая таблица
            $this->newLine();
            $this->table(
                ['Action', 'Count'],
                [
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                    ['Failed', $stats['failed']],
                    ['Total', $offersCount],
                ]
            );

            // Dispatch event
            event(new OffersImported(
                $stats['created'],
                $stats['updated']
            ));

            $this->newLine();
            $this->info('<fg=green>✓ Offers import completed successfully!</>');

            return 0;
        });
    }
}
