<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Models\PropertyDefinition;

class PropertyDefinitionRepository extends BaseRepository
{
    public function __construct(PropertyDefinition $model)
    {
        parent::__construct($model);
    }
}
