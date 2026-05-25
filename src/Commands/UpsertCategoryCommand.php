<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\CategoryData;

class UpsertCategoryCommand extends Command
{
    public function __construct(
        public CategoryData $categoryData
    ) {}
}
