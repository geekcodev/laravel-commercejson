<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: заказ экспортирован
 */
class OrderExported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $exportedCount
    ) {}
}
