<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class HandshakeResponseData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $version,
        #[Required, ArrayType, Min(1)]
        public array $supported_versions,
        #[Required]
        public Carbon $server_time,
        #[Required]
        public CapabilitiesData $capabilities,
        #[Nullable, StringType]
        public ?string $session_token = null,
    ) {}
}
