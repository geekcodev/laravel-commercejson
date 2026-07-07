<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Casts;

use Illuminate\Support\Facades\Log;
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

        $result = str_replace(',', '.', (string) $value);

        Log::debug('MoneyAmountCast', ['input' => $value, 'output' => $result]);

        return $result;
    }
}
