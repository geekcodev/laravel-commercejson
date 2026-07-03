<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OrderDeliveryTrackData;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;

class BulkUpsertOrderCommand extends Command
{
    public function __construct(
        public ?string $id = null,
        public ?string $external_id = null,
        public ?OrderStatusEnum $status = null,
        public ?string $comment = null,
        public ?array $custom_attributes = null,
        public ?array $items = null,
        public ?OrderDeliveryTrackData $deliveryTrack = null,
        public ?array $linkedDocuments = null,
    ) {}
}
