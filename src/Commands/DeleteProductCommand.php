<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

class DeleteProductCommand extends Command
{
    public function __construct(
        public string $id
    ) {}
}
