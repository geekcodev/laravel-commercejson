<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OrderData;

class UpsertOrderCommand extends Command
{
    public function __construct(
        public OrderData $orderData
    ) {}
}
