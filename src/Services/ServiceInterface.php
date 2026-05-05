<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use GeekCo\CommerceJson\Http\Client\HttpClientInterface;

interface ServiceInterface
{
    public function setHttpClient(HttpClientInterface $http): static;
}
