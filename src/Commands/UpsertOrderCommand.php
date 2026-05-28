<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderDeliveryTrackData;

class UpsertOrderCommand extends Command
{
    public function __construct(
        public OrderData $orderData,
        public ?OrderDeliveryTrackData $deliveryTrack = null,
    ) {}
}
