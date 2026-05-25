<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\PriceTypeData;

class UpsertPriceTypeCommand extends Command
{
    public function __construct(
        public PriceTypeData $priceTypeData
    ) {}
}
