<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

use RuntimeException;

/**
 * Ошибка синхронизации данных
 *
 * Это бизнес-исключение, не связанное с HTTP клиентом.
 * Используется для ошибок синхронизации данных.
 */
class SyncException extends RuntimeException
{
    public function __construct(
        string $message = 'Sync failed',
        public readonly string $syncType = 'unknown',
        public readonly ?\DateTime $lastSyncTime = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getSyncType(): string
    {
        return $this->syncType;
    }

    public function getLastSyncTime(): ?\DateTime
    {
        return $this->lastSyncTime;
    }
}
