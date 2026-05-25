<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\CategoryData;
use GeekCo\CommerceJson\Models\Category;

class UpdateCategoryCommand extends Command
{
    public function __construct(
        public Category $category,
        public CategoryData $categoryData
    ) {}
}
