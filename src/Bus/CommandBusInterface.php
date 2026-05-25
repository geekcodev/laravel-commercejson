<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Bus;

use GeekCo\CommerceJson\Commands\Command;

interface CommandBusInterface
{
    public function dispatch(Command $command): mixed;
}
