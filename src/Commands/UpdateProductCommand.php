<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Models\Product;

class UpdateProductCommand extends Command
{
    public function __construct(
        public Product $product,
        public ProductData $productData
    ) {}
}
