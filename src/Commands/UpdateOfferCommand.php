<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Models\Offer;

class UpdateOfferCommand extends Command
{
    public function __construct(
        public Offer $offer,
        public OfferData $offerData
    ) {}
}
