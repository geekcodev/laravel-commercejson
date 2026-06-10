<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Jobs\Sync;

use GeekCo\CommerceJson\Events\SyncFailed;
use GeekCo\CommerceJson\Jobs\Concerns\InteractsWithCommerceJson;
use GeekCo\CommerceJson\Jobs\Import\ImportClassifierJob;
use GeekCo\CommerceJson\Jobs\Import\ImportOffersJob;
use GeekCo\CommerceJson\Jobs\Import\ImportOrdersJob;
use GeekCo\CommerceJson\Jobs\Import\ImportProductsJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job: Полная синхронизация с CommerceJSON API
 */
class SyncFullJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithCommerceJson;

    public int $timeout = 3600; // 1 час

    public int $tries = 1;

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
    public function handle(): void
    {
        $this->logJobAction('Starting FULL sync');

        try {
            $chain = [
                new ImportClassifierJob,
                new ImportProductsJob,
                new ImportOffersJob,
                new ImportOrdersJob,
            ];

            ImportClassifierJob::withChain($chain)->dispatch();

            $this->logJobAction('FULL sync chain dispatched');
        } catch (\Exception $e) {
            $this->logJobError('FULL sync failed: '.$e->getMessage());
            event(new SyncFailed('full', $e));
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logJobError('FULL sync job failed: '.$exception->getMessage());
    }
}
