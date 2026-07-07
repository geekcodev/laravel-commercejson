<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class MoneyAmountCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed
    {
        if ($value === null || $value === '') {
            return Uncastable::create();
        }

        return str_replace(',', '.', (string) $value);
    }
}
