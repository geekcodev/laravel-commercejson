<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Order;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function findByStatus(string $status): array
    {
        return $this->model->where('status', $status)->get()->toArray();
    }

    public function findByExternalId(string $externalId): ?Order
    {
        /** @var Order|null $order */
        $order = $this->model->where('external_id', $externalId)->first();

        return $order;
    }
}
