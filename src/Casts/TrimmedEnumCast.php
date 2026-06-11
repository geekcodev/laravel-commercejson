<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Casts;

use BackedEnum;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\DataProperty;

class TrimmedEnumCast extends EnumCast
{
    protected function castValue(?string $type, mixed $value, DataProperty $property): BackedEnum|Uncastable
    {
        return parent::castValue($type, is_string($value) ? trim($value) : $value, $property);
    }
}
