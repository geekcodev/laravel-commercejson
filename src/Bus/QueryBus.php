<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Bus;

use GeekCo\CommerceJson\Queries\Query;

class QueryBus implements QueryBusInterface
{
    private array $handlers = [];

    public function register(string $queryClass, callable $handler): void
    {
        $this->handlers[$queryClass] = $handler;
    }

    public function ask(Query $query): mixed
    {
        $queryClass = get_class($query);

        if (! isset($this->handlers[$queryClass])) {
            throw new \InvalidArgumentException("No handler registered for query: {$queryClass}");
        }

        return $this->handlers[$queryClass]($query);
    }
}
