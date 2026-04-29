<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: заказ обновлен
 */
class OrderUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $orderId
    ) {}
}
