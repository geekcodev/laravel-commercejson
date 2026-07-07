<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Casts;

use BackedEnum;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class TrimmedEnumCast implements Cast
{
    public function __construct(
        protected ?string $type = null
    ) {}

    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed
    {
        $enumType = $this->type ?? $property->type->type->findAcceptedTypeForBaseType(BackedEnum::class);

        $trimmed = is_string($value) ? trim($value) : $value;

        if ($enumType === null || ! is_subclass_of($enumType, BackedEnum::class)) {
            return Uncastable::create();
        }

        if ($trimmed instanceof $enumType) {
            return $trimmed;
        }

        if ($trimmed instanceof BackedEnum) {
            $trimmed = $trimmed->value;
        }

        return $enumType::tryFrom($trimmed);
    }
}
