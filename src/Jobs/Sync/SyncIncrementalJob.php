<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Jobs\Sync;

use GeekCo\CommerceJson\Events\SyncFailed;
use GeekCo\CommerceJson\Jobs\Concerns\InteractsWithCommerceJson;
use GeekCo\CommerceJson\Jobs\Import\ImportOffersJob;
use GeekCo\CommerceJson\Jobs\Import\ImportOrdersJob;
use GeekCo\CommerceJson\Jobs\Import\ImportProductsJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job: Инкрементальная синхронизация с CommerceJSON API
 */
class SyncIncrementalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithCommerceJson;

    public int $timeout = 600; // 10 минут

    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ?string $since = null
    ) {
        $this->onQueue(config('commercejson.exchange.queue.import_queue', 'commercejson-import'));
        $this->onConnection(config('commercejson.exchange.queue.connection', 'sync'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $since = $this->since ?? now()->subHour()->toIso8601String();

        $this->logJobAction("Starting INCREMENTAL sync (since: {$since})");

        try {
            $chain = [
                new ImportProductsJob(updatedAfter: $since),
                new ImportOffersJob(updatedAfter: $since),
                new ImportOrdersJob(updatedAfter: $since),
            ];

            ImportProductsJob::withChain($chain)->dispatch();

            $this->logJobAction('INCREMENTAL sync chain dispatched');
        } catch (\Exception $e) {
            $this->logJobError('INCREMENTAL sync failed: '.$e->getMessage());
            event(new SyncFailed('incremental', $e));
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logJobError('INCREMENTAL sync job failed: '.$exception->getMessage());
    }
}
