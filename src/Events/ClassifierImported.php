<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: классификатор импортирован
 */
class ClassifierImported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $categoriesCount = 0,
        public int $propertiesCount = 0,
        public int $priceTypesCount = 0
    ) {}
}
