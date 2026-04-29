<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: товары импортированы
 */
class ProductsImported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $importedCount,
        public int $updatedCount,
        public int $deletedCount
    ) {}
}
