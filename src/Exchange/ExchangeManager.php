<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exchange;

use DateTimeInterface;
use GeekCo\CommerceJson\Events\SyncCompleted;
use GeekCo\CommerceJson\Events\SyncFailed;
use GeekCo\CommerceJson\Events\SyncStarted;
use GeekCo\CommerceJson\Exchange\Export\OrderExporter;
use GeekCo\CommerceJson\Exchange\Import\ClassifierImporter;
use GeekCo\CommerceJson\Exchange\Import\OrderImporter;
use GeekCo\CommerceJson\Exchange\Import\ProductImporter;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Jobs\Export\ExportOrdersJob;
use GeekCo\CommerceJson\Jobs\Import\ImportClassifierJob;
use GeekCo\CommerceJson\Jobs\Import\ImportOffersJob;
use GeekCo\CommerceJson\Jobs\Import\ImportOrdersJob;
use GeekCo\CommerceJson\Jobs\Import\ImportProductsJob;
use GeekCo\CommerceJson\Jobs\Sync\SyncFullJob;
use GeekCo\CommerceJson\Jobs\Sync\SyncIncrementalJob;

/**
 * Менеджер обмена данными с CommerceJSON API
 *
 * Координирует процессы импорта и экспорта данных
 */
class ExchangeManager
{
    public function __construct(
        protected HttpClientInterface $http,
        protected ProductImporter $productImporter,
        protected OrderImporter $orderImporter,
        protected ClassifierImporter $classifierImporter,
        protected OrderExporter $orderExporter,
    ) {}

    /**
     * Проверить соединение с API
     */
    public function checkConnection(): array
    {
        $configPath = config('commercejson.external_api_endpoints.handshake', '/handshake');
        $response = $this->http->get($configPath);

        return [
            'connected' => true,
            'version' => $response->data['version'] ?? 'unknown',
            'server_time' => $response->data['server_time'] ?? null,
            'capabilities' => $response->data['capabilities'] ?? [],
        ];
    }

    /**
     * Полная синхронизация всех данных
     */
    public function fullSync(bool $useQueue = true): void
    {
        event(new SyncStarted('full'));

        try {
            if ($useQueue) {
                SyncFullJob::dispatch();
            } else {
                $startTime = time();
                $this->syncClassifier();
                $this->syncProducts();
                $this->syncOffers();
                $this->syncOrders();
                $duration = time() - $startTime;
                event(new SyncCompleted('full', $duration));
            }
        } catch (\Exception $e) {
            event(new SyncFailed('full', $e));
            throw $e;
        }
    }

    public function incrementalSync(?\DateTime $since = null, bool $useQueue = true): void
    {
        $since = $since ?? now()->subHour();

        event(new SyncStarted('incremental', $since));

        try {
            if ($useQueue) {
                SyncIncrementalJob::dispatch($since->format(DateTimeInterface::ATOM));
            } else {
                $startTime = time();
                $this->syncProducts($since);
                $this->syncOffers($since);
                $this->syncOrders($since);
                $duration = time() - $startTime;
                event(new SyncCompleted('incremental', $duration));
            }
        } catch (\Exception $e) {
            event(new SyncFailed('incremental', $e));
            throw $e;
        }
    }

    /**
     * Синхронизировать классификатор
     */
    public function syncClassifier(bool $useQueue = false): array
    {
        if ($useQueue) {
            ImportClassifierJob::dispatch();

            return ['dispatched' => true];
        }

        return $this->classifierImporter->import();
    }

    /**
     * Синхронизировать товары
     */
    public function syncProducts(?\DateTime $since = null, bool $useQueue = false): array
    {
        if ($useQueue) {
            ImportProductsJob::dispatch(updatedAfter: $since?->format(DateTimeInterface::ATOM));

            return ['dispatched' => true];
        }

        return $this->productImporter->import(updatedAfter: $since);
    }

    /**
     * Синхронизировать предложения
     */
    public function syncOffers(?\DateTime $since = null, bool $useQueue = false): array
    {
        if ($useQueue) {
            ImportOffersJob::dispatch(updatedAfter: $since?->format(DateTimeInterface::ATOM));

            return ['dispatched' => true];
        }

        return $this->productImporter->importOffers(updatedAfter: $since);
    }

    /**
     * Синхронизировать заказы
     */
    public function syncOrders(?\DateTime $since = null, bool $useQueue = false): array
    {
        if ($useQueue) {
            ImportOrdersJob::dispatch(updatedAfter: $since?->format(DateTimeInterface::ATOM));

            return ['dispatched' => true];
        }

        return $this->orderImporter->import(updatedAfter: $since);
    }

    /**
     * Экспортировать новые заказы в ERP
     */
    public function exportOrders(int $limit = 50, bool $useQueue = false): array
    {
        if ($useQueue) {
            ExportOrdersJob::dispatch($limit);

            return ['dispatched' => true];
        }

        return $this->orderExporter->export($limit);
    }

    /**
     * Экспортировать заказы по дате обновления
     */
    public function exportOrdersSince(\DateTime $since, int $limit = 50, bool $useQueue = false): array
    {
        if ($useQueue) {
            ExportOrdersJob::dispatch($limit, $since->format(DateTimeInterface::ATOM));

            return ['dispatched' => true];
        }

        return $this->orderExporter->exportSince($since, $limit);
    }
}
