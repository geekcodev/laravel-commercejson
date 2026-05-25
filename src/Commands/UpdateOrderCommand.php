<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Models\Order;

class UpdateOrderCommand extends Command
{
    public function __construct(
        public Order $order,
        public OrderData $orderData
    ) {}
}
