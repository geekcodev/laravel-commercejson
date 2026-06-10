<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\StockData;

class UpsertStockCommand extends Command
{
    public function __construct(
        public string $offerId,
        public StockData $stockData
    ) {}
}
