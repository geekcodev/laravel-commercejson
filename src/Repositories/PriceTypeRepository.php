<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\PriceType;

class PriceTypeRepository extends BaseRepository
{
    public function __construct(PriceType $model)
    {
        parent::__construct($model);
    }
}
