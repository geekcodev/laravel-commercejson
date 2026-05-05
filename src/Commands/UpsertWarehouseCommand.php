<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\WarehouseData;

class UpsertWarehouseCommand extends Command
{
    public function __construct(
        public WarehouseData $warehouseData
    ) {}
}
