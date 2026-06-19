<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Order;
use Illuminate\Support\Collection;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function findByExternalId(string $externalId): ?Order
    {
        /** @var Order|null $order */
        $order = $this->model->where('external_id', $externalId)->first();

        return $order;
    }
}
