<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: заказ импортирован
 */
class OrderImported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $orderId,
        public string $status
    ) {}
}
