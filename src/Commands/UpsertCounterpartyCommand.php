<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\CounterpartyData;

class UpsertCounterpartyCommand extends Command
{
    public function __construct(
        public CounterpartyData $counterpartyData
    ) {}
}
