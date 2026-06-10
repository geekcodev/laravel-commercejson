<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Offer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OfferRepository extends BaseRepository
{
    public function __construct(Offer $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with('prices', 'stocks')->paginate($perPage);
    }

    public function findByProduct(string $productId): array
    {
        return $this->model->where('product_id', $productId)->get()->toArray();
    }
}
