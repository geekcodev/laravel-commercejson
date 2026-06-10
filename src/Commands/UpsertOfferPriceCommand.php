<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OfferPriceData;

class UpsertOfferPriceCommand extends Command
{
    public function __construct(
        public string $offerId,
        public OfferPriceData $priceData
    ) {}
}
