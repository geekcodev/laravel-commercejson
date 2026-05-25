<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Models\Order;

class DeleteOrderCommand extends Command
{
    public function __construct(
        public Order $order
    ) {}
}
