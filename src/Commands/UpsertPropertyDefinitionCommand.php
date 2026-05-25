<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Commands;

use GeekCo\CommerceJson\Data\PropertyDefinitionData;

class UpsertPropertyDefinitionCommand extends Command
{
    public function __construct(
        public PropertyDefinitionData $propertyDefinitionData
    ) {}
}
