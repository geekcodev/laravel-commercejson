<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Jobs\Import;

use GeekCo\CommerceJson\Events\ProductsImported;
use GeekCo\CommerceJson\Jobs\Concerns\InteractsWithCommerceJson;
use GeekCo\CommerceJson\Services\ProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job: Импорт страницы товаров
 */
class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithCommerceJson;

    public int $timeout = 300; // 5 минут

    public int $tries = 3;

    public int $backoff = 10; // 10 секунд между попытками

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $page = 1,
        protected int $limit = 100,
        protected ?string $categoryId = null,
        protected ?string $updatedAfter = null
    ) {
        $this->onQueue(config('commercejson.exchange.queue.import_queue', 'commercejson-import'));
        $this->onConnection(config('commercejson.exchange.queue.connection', 'sync'));
    }

    /**
     * Execute the job.
     */
    public function handle(ProductService $productService): void
    {
        $this->logJobAction("Starting products import (page: {$this->page}, limit: {$this->limit})");

        if (! $this->checkConnection()) {
            $this->fail(new \RuntimeException('Connection to CommerceJSON API failed'));

            return;
        }

        // Получение товаров
        $productList = $productService->getProducts(
            page: $this->page,
            limit: $this->limit,
            categoryId: $this->categoryId,
            updatedAfter: $this->updatedAfter ? new \DateTime($this->updatedAfter) : null
        );

        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        // Синхронизация с БД через Service (использует CommandBus внутри)
        foreach ($productList->products as $productData) {
            try {
                $product = $productService->syncProduct($productData);

                if ($product->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                $this->logJobError("Failed to sync product {$productData->id}: ".$e->getMessage());

                // Не прерываем обработку остальных товаров
            }
        }

        $this->logJobAction(
            'Products import completed',
            [
                'page' => $this->page,
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'failed' => $stats['failed'],
            ]
        );

        // Dispatch следующей страницы если есть
        if ($productList->pagination->hasNext) {
            ImportProductsJob::dispatch(
                page: $this->page + 1,
                limit: $this->limit,
                categoryId: $this->categoryId,
                updatedAfter: $this->updatedAfter
            );
        } else {
            // Все страницы обработаны - dispatch события
            event(new ProductsImported(
                $stats['created'],
                $stats['updated'],
                0
            ));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logJobError(
            'Products import job failed: '.$exception->getMessage(),
            ['page' => $this->page]
        );
    }
}
