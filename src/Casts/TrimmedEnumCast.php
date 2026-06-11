<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Casts;

use Spatie\LaravelData\Casts\EnumCast;

class TrimmedEnumCast extends EnumCast
{
    protected function castValue(?string $type, mixed $value, mixed $property): mixed
    {
        return parent::castValue($type, is_string($value) ? trim($value) : $value, $property);
    }
}
