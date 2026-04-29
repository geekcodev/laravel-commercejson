<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Jobs\Import;

use GeekCo\CommerceJson\Events\OffersImported;
use GeekCo\CommerceJson\Jobs\Concerns\InteractsWithCommerceJson;
use GeekCo\CommerceJson\Models\OfferPrice;
use GeekCo\CommerceJson\Models\Stock;
use GeekCo\CommerceJson\Services\OfferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Job: Импорт страницы предложений
 */
class ImportOffersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithCommerceJson;

    public int $timeout = 300;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $page = 1,
        protected int $limit = 200,
        protected ?string $priceTypeId = null,
        protected ?string $warehouseId = null,
        protected ?string $updatedAfter = null
    ) {
        $this->onQueue(config('commercejson.exchange.queue.import_queue', 'commercejson-import'));
        $this->onConnection(config('commercejson.exchange.queue.connection', 'sync'));
    }

    /**
     * Execute the job.
     */
    public function handle(OfferService $offerService): void
    {
        $this->logJobAction("Starting offers import (page: {$this->page}, limit: {$this->limit})");

        if (! $this->checkConnection()) {
            $this->fail(new \RuntimeException('Connection to CommerceJSON API failed'));

            return;
        }

        // Получение предложений
        $offerList = $offerService->getOffers(
            page: $this->page,
            limit: $this->limit,
            priceTypeId: $this->priceTypeId,
            warehouseId: $this->warehouseId,
            updatedAfter: $this->updatedAfter ? new \DateTime($this->updatedAfter) : null
        );

        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        // Синхронизация с БД
        foreach ($offerList->offers as $offerData) {
            try {
                DB::transaction(function () use ($offerData, $offerService, &$stats) {
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
                                    'discount_percent' => $priceData->discountPercent,
                                    'unit_code' => $priceData->unit?->code,
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
                $this->logJobError('Failed to sync offer: '.$e->getMessage());
            }
        }

        $this->logJobAction(
            'Offers import completed',
            [
                'page' => $this->page,
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'failed' => $stats['failed'],
            ]
        );

        // Dispatch следующей страницы
        if ($offerList->pagination->hasNext) {
            ImportOffersJob::dispatch(
                page: $this->page + 1,
                limit: $this->limit,
                priceTypeId: $this->priceTypeId,
                warehouseId: $this->warehouseId,
                updatedAfter: $this->updatedAfter
            );
        } else {
            // Все страницы обработаны
            event(new OffersImported(
                $stats['created'],
                $stats['updated']
            ));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logJobError(
            'Offers import job failed: '.$exception->getMessage(),
            ['page' => $this->page]
        );
    }
}
