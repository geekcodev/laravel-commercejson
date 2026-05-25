<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Bus;

use GeekCo\CommerceJson\Queries\Query;

interface QueryBusInterface
{
    public function ask(Query $query): mixed;
}
