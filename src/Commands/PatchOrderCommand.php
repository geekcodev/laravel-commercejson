<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\OrderPatchData;

class PatchOrderCommand extends Command
{
    public function __construct(
        public string $id,
        public OrderPatchData $patchData
    ) {}
}
