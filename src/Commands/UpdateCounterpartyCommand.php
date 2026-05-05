<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Models\Counterparty;

class UpdateCounterpartyCommand extends Command
{
    public function __construct(
        public Counterparty $counterparty,
        public CounterpartyData $counterpartyData
    ) {}
}
