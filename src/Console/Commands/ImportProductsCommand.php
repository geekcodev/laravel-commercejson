<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Commands;

use GeekCo\CommerceJson\Console\Concerns\InteractsWithExchange;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Events\ProductsImported;
use GeekCo\CommerceJson\Services\ProductService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Команда: Импорт товаров
 */
class ImportProductsCommand extends Command
{
    use InteractsWithExchange;

    protected $signature = 'commercejson:import-products
                            {--page=1 : Номер страницы для импорта}
                            {--limit=100 : Количество товаров на странице}
                            {--category= : ID категории для фильтрации}
                            {--updated-after= : Дата для инкрементального импорта}
                            {--queue : Использовать очередь}
                            {--no-sync : Не синхронизировать с БД}';

    protected $description = 'Импортировать товары из CommerceJSON API';

    public function handle(ProductService $productService): int
    {
        $this->info('Starting products import...');

        return $this->withErrorHandling(function () use ($productService) {
            // Проверка соединения
            if (! $this->checkConnection()) {
                return 1;
            }

            $page = (int) $this->option('page');
            $limit = min((int) $this->option('limit'), 1000);
            $categoryId = $this->option('category');
            $updatedAfter = $this->option('updated-after')
                ? new \DateTime($this->option('updated-after'))
                : null;

            $this->newLine();
            $this->info("Fetching products (page: {$page}, limit: {$limit})...");

            if ($categoryId) {
                $this->line("Category filter: {$categoryId}");
            }

            if ($updatedAfter) {
                $this->line('Updated after: '.$updatedAfter->format('Y-m-d H:i:s'));
            }

            // Получение товаров
            $productList = $productService->getProducts(
                page: $page,
                limit: $limit,
                categoryId: $categoryId,
                updatedAfter: $updatedAfter
            );

            $productsCount = count($productList->products);
            $this->line("Received: {$productsCount} products");

            if ($this->option('no-sync')) {
                $this->warn('Skipping database sync (--no-sync flag)');

                return 0;
            }

            // Синхронизация с БД
            $this->newLine();
            $this->info('Synchronizing products with database...');

            $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

            $this->withProgressBar($productList->products, function ($productData) use ($productService, &$stats) {
                /** @var ProductData $productData */
                try {
                    DB::transaction(function () use ($productData, $productService, &$stats) {
                        $product = $productService->syncProduct($productData);

                        if ($product->wasRecentlyCreated) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }
                    });
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $this->error("Failed to sync product {$productData->id}: ".$e->getMessage());
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
                    ['Total', $productsCount],
                ]
            );

            // Проверка следующих страниц
            if ($productList->pagination->has_next && ! $this->option('page')) {
                $this->newLine();
                $this->info('More pages available. Use --page='.($page + 1).' to continue.');
            }

            // Dispatch event
            event(new ProductsImported(
                $stats['created'],
                $stats['updated'],
                0 // deleted count требует отдельной логики
            ));

            $this->newLine();
            $this->info('<fg=green>✓ Products import completed successfully!</>');

            return 0;
        });
    }
}
