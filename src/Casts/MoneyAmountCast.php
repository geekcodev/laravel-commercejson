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
        if ($value === null) {
            return Uncastable::create();
        }

        if ($value === '') {
            return '0';
        }

        return str_replace(',', '.', (string) $value);
    }
}
