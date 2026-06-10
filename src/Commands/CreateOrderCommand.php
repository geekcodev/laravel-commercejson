<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OrderCreateData;

class CreateOrderCommand extends Command
{
    public function __construct(
        public OrderCreateData $createData
    ) {}
}
