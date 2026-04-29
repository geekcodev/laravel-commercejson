<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Jobs\Concerns;

use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;

/**
 * Trait для Queue Jobs работы с CommerceJSON
 */
trait InteractsWithCommerceJson
{
    /**
     * Получить HTTP коннектор
     */
    protected function connector(): CommerceJsonConnector
    {
        return app(CommerceJsonConnector::class);
    }

    /**
     * Проверить соединение с API
     */
    protected function checkConnection(): bool
    {
        try {
            $this->connector()->handshake();

            return true;
        } catch (\Exception $e) {
            logger()->error('CommerceJSON connection check failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Получить размер пакета из конфига
     */
    protected function getBatchSize(string $type): int
    {
        return config("commercejson.exchange.batch_size.{$type}", 100);
    }

    /**
     * Логировать действие
     */
    protected function logJobAction(string $action, array $context = []): void
    {
        logger()->info("[CommerceJSON Job] {$action}", $context);
    }

    /**
     * Логировать ошибку
     */
    protected function logJobError(string $error, array $context = []): void
    {
        logger()->error("[CommerceJSON Job Error] {$error}", $context);
    }
}
