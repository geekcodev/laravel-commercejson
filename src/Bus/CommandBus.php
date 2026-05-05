<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Bus;

use GeekCo\CommerceJson\Commands\Command;

class CommandBus implements CommandBusInterface
{
    private array $handlers = [];

    public function register(string $commandClass, callable $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    public function dispatch(Command $command): mixed
    {
        $commandClass = get_class($command);

        if (! isset($this->handlers[$commandClass])) {
            throw new \InvalidArgumentException("No handler registered for command: {$commandClass}");
        }

        return $this->handlers[$commandClass]($command);
    }
}
