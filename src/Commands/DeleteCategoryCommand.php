<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Models\Category;

class DeleteCategoryCommand extends Command
{
    public function __construct(
        public Category $category
    ) {}
}
