<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OrderData;

class UpdateOrderCommand extends Command
{
    public function __construct(
        public string $id,
        public OrderData $orderData
    ) {}
}
