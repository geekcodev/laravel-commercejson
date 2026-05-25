<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Models\Offer;

class DeleteOfferCommand extends Command
{
    public function __construct(
        public Offer $offer
    ) {}
}
