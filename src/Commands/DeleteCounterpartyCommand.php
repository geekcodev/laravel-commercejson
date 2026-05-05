<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Models\Counterparty;

class DeleteCounterpartyCommand extends Command
{
    public function __construct(
        public Counterparty $counterparty
    ) {}
}
