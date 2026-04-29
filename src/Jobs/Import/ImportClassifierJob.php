<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Jobs\Import;

use GeekCo\CommerceJson\Events\ClassifierImported;
use GeekCo\CommerceJson\Jobs\Concerns\InteractsWithCommerceJson;
use GeekCo\CommerceJson\Services\ClassifierService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job: Импорт классификатора
 */
class ImportClassifierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithCommerceJson;

    public int $timeout = 300;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue(config('commercejson.exchange.queue.import_queue', 'commercejson-import'));
        $this->onConnection(config('commercejson.exchange.queue.connection', 'sync'));
    }

    /**
     * Execute the job.
     */
    public function handle(ClassifierService $classifierService): void
    {
        $this->logJobAction('Starting classifier import');

        if (! $this->checkConnection()) {
            $this->fail(new \RuntimeException('Connection to CommerceJSON API failed'));

            return;
        }

        // Получение классификатора
        $classifier = $classifierService->getClassifier();

        $stats = [
            'categories' => 0,
            'properties' => 0,
            'priceTypes' => 0,
        ];

        // Синхронизация категорий
        if (! empty($classifier->categories)) {
            $stats['categories'] = $classifierService->syncCategories($classifier->categories);
        }

        // Синхронизация свойств
        if (! empty($classifier->properties)) {
            $stats['properties'] = $classifierService->syncProperties($classifier->properties);
        }

        // Синхронизация типов цен
        if (! empty($classifier->priceTypes)) {
            $stats['priceTypes'] = $classifierService->syncPriceTypes($classifier->priceTypes);
        }

        $this->logJobAction(
            'Classifier import completed',
            $stats
        );

        // Dispatch event
        event(new ClassifierImported(
            $stats['categories'],
            $stats['properties'],
            $stats['priceTypes']
        ));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logJobError('Classifier import job failed: '.$exception->getMessage());
    }
}
