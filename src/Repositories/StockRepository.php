<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Stock;

class StockRepository extends BaseRepository
{
    public function __construct(Stock $model)
    {
        parent::__construct($model);
    }
}
