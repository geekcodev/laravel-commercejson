<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findByCategory(string $categoryId): array
    {
        return $this->model->where('category_id', $categoryId)->get()->toArray();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with([
            'images', 'propertyValues', 'variants.propertyValues',
            'customAttributes', 'analogues', 'components',
        ])->paginate($perPage);
    }
}
