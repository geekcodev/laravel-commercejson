<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\ProductData;

class CreateProductCommand extends Command
{
    public function __construct(
        public ProductData $productData
    ) {}
}
