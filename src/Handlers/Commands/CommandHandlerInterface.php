<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
