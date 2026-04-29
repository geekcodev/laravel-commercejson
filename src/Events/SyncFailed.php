<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: синхронизация провалилась
 */
class SyncFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $type,
        public \Throwable $exception
    ) {}
}
