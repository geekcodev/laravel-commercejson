<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Counterparty;

class CounterpartyRepository extends BaseRepository
{
    public function __construct(Counterparty $model)
    {
        parent::__construct($model);
    }

    public function findByType(string $type): array
    {
        return $this->model->where('type', $type)->get()->toArray();
    }
}
