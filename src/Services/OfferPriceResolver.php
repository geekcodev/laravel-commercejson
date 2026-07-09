<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use GeekCo\CommerceJson\Models\OfferPrice;
use Illuminate\Support\Collection;

class OfferPriceResolver
{
    /**
     * Resolve the best matching price for a collection of offer prices.
     *
     * Priority:
     * 1. Price matching the given $priceTypeId (if provided)
     * 2. First available price (fallback)
     */
    public function resolve(Collection $prices, ?string $priceTypeId): ?OfferPrice
    {
        if ($prices->isEmpty()) {
            return null;
        }

        if ($priceTypeId === null) {
            return $prices->first();
        }

        return $prices->firstWhere('price_type_id', $priceTypeId) ?? $prices->first();
    }
}
