<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Commands;

use GeekCo\CommerceJson\Console\Concerns\InteractsWithExchange;
use GeekCo\CommerceJson\Events\SyncCompleted;
use GeekCo\CommerceJson\Events\SyncStarted;
use Illuminate\Console\Command;

/**
 * Команда: Синхронизация с CommerceJSON API
 */
class SyncCommand extends Command
{
    use InteractsWithExchange;

    protected $signature = 'commercejson:sync
                            {--full : Полная синхронизация}
                            {--incremental : Инкрементальная синхронизация}
                            {--since= : Дата для инкрементальной синхронизации}
                            {--classifier : Только классификатор}
                            {--products : Только товары}
                            {--offers : Только предложения}
                            {--orders : Только заказы}
                            {--queue : Использовать очередь}
                            {--no-interaction : Не спрашивать подтверждение}';

    protected $description = 'Запуск синхронизации с CommerceJSON API';

    private int $startTime;

    public function handle(): int
    {
        $this->startTime = time();
        $this->info('Starting CommerceJSON sync...');

        return $this->withErrorHandling(function () {
            // Проверка соединения
            if (! $this->checkConnection()) {
                return 1;
            }

            // Определение типа синхронизации
            $isFull = $this->option('full') || (! $this->option('incremental') && ! $this->option('since'));
            $syncType = $isFull ? 'full' : 'incremental';

            // Dispatch event
            event(new SyncStarted(
                $syncType,
                $this->option('since') ? new \DateTime($this->option('since')) : null
            ));

            if ($isFull) {
                $this->warn('Running FULL sync. This may take a long time.');

                if (! $this->option('no-interaction') && ! $this->confirm('Do you want to continue?', true)) {
                    return 1;
                }
            }

            // Синхронизация по компонентам
            $results = [];

            if ($this->option('classifier') || $this->shouldSyncAll()) {
                $results['classifier'] = $this->syncClassifier();
            }

            if ($this->option('products') || $this->shouldSyncAll()) {
                $results['products'] = $this->syncProducts($isFull);
            }

            if ($this->option('offers') || $this->shouldSyncAll()) {
                $results['offers'] = $this->syncOffers($isFull);
            }

            if ($this->option('orders') || $this->shouldSyncAll()) {
                $results['orders'] = $this->syncOrders($isFull);
            }

            // Итоговая таблица
            $this->newLine();
            $this->info('Sync Summary:');

            $rows = [];
            foreach ($results as $component => $stat) {
                $status = $stat['success']
                    ? '<fg=green>✓ Success</>'
                    : '<fg=red>✗ Failed</>';
                $rows[] = [
                    ucfirst($component),
                    $stat['count'] ?? 0,
                    $status,
                    $stat['duration'] ?? 0 .'s',
                ];
            }

            $this->table(['Component', 'Count', 'Status', 'Duration'], $rows);

            // Общее время
            $duration = time() - $this->startTime;
            $this->newLine();
            $this->line("Total sync time: {$duration}s");

            // Dispatch event
            event(new SyncCompleted($syncType, $duration));

            $this->newLine();
            $this->info('<fg=green>✓ Sync completed successfully!</>');

            return 0;
        });
    }

    /**
     * Синхронизировать классификатор
     */
    private function syncClassifier(): array
    {
        $this->newLine();
        $this->info('Synchronizing classifier...');

        $start = time();

        try {
            $this->call('commercejson:import-classifier', [
                '--queue' => $this->option('queue'),
                '--no-sync' => false,
            ]);

            return [
                'success' => true,
                'count' => 'N/A',
                'duration' => time() - $start,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => time() - $start,
            ];
        }
    }

    /**
     * Синхронизировать товары
     */
    private function syncProducts(bool $isFull): array
    {
        $this->newLine();
        $this->info('Synchronizing products...');

        $start = time();
        $options = [
            '--queue' => $this->option('queue'),
            '--no-sync' => false,
        ];

        if (! $isFull) {
            $since = $this->option('since') ?? now()->subHour()->toIso8601String();
            $options['--updated-after'] = $since;
        }

        try {
            $this->call('commercejson:import-products', $options);

            return [
                'success' => true,
                'count' => 'N/A',
                'duration' => time() - $start,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => time() - $start,
            ];
        }
    }

    /**
     * Синхронизировать предложения
     */
    private function syncOffers(bool $isFull): array
    {
        $this->newLine();
        $this->info('Synchronizing offers...');

        $start = time();
        $options = [
            '--queue' => $this->option('queue'),
            '--no-sync' => false,
        ];

        if (! $isFull) {
            $since = $this->option('since') ?? now()->subHour()->toIso8601String();
            $options['--updated-after'] = $since;
        }

        try {
            $this->call('commercejson:import-offers', $options);

            return [
                'success' => true,
                'count' => 'N/A',
                'duration' => time() - $start,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => time() - $start,
            ];
        }
    }

    /**
     * Синхронизировать заказы
     */
    private function syncOrders(bool $isFull): array
    {
        $this->newLine();
        $this->info('Synchronizing orders...');

        $start = time();
        $options = [
            '--queue' => $this->option('queue'),
            '--no-sync' => false,
        ];

        if (! $isFull) {
            $since = $this->option('since') ?? now()->subHour()->toIso8601String();
            $options['--updated-after'] = $since;
        }

        try {
            $this->call('commercejson:import-orders', $options);

            return [
                'success' => true,
                'count' => 'N/A',
                'duration' => time() - $start,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => time() - $start,
            ];
        }
    }

    /**
     * Нужно ли синхронизировать всё
     */
    private function shouldSyncAll(): bool
    {
        return ! $this->option('classifier')
            && ! $this->option('products')
            && ! $this->option('offers')
            && ! $this->option('orders');
    }
}
