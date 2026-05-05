<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OfferData;

class CreateOfferCommand extends Command
{
    public function __construct(
        public OfferData $offerData
    ) {}
}
