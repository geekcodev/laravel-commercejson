<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\OfferPrice;

class OfferPriceRepository extends BaseRepository
{
    public function __construct(OfferPrice $model)
    {
        parent::__construct($model);
    }
}
