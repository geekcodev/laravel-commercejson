<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Warehouse;

class WarehouseRepository extends BaseRepository
{
    public function __construct(Warehouse $model)
    {
        parent::__construct($model);
    }
}
