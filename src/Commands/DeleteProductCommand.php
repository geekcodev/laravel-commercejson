<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Models\Product;

class DeleteProductCommand extends Command
{
    public function __construct(
        public Product $product
    ) {}
}
