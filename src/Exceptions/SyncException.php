<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exceptions;

/**
 * Ошибка синхронизации данных
 */
class SyncException extends CommerceJsonException
{
    protected string $syncType;

    protected ?\DateTime $lastSyncTime = null;

    public function __construct(
        string $message = 'Sync failed',
        string $syncType = 'unknown',
        ?\DateTime $lastSyncTime = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->syncType = $syncType;
        $this->lastSyncTime = $lastSyncTime;
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
