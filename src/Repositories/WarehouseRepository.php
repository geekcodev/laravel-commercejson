<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

class WarehouseRepository extends BaseRepository
{
    public function __construct(Warehouse $model)
    {
        parent::__construct($model);
    }

    public function allWithTrashed(array $columns = ['*']): Collection
    {
        return $this->model->withTrashed()->get($columns);
    }
}
