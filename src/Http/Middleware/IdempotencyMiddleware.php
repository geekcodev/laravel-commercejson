<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    private const string CACHE_PREFIX = 'commercejson:idempotency:';

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
            return $next($request);
        }

        $key = $request->header('X-Idempotency-Key');

        if ($key === null || $key === '') {
            return $next($request);
        }

        $ttl = (int) config('commercejson.idempotency.ttl', 3600);
        $cacheKey = self::CACHE_PREFIX.$key.':'.$this->requestFingerprint($request);

        if ($cached = Cache::get($cacheKey)) {
            return new JsonResponse(
                $cached['content'],
                $cached['status'],
                $cached['headers'] ?? [],
                JSON_UNESCAPED_UNICODE
            );
        }

        /** @var JsonResponse $response */
        $response = $next($request);

        if ($response->isSuccessful() || $response->isClientError()) {
            Cache::put($cacheKey, [
                'content' => $response->getData(true),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ], $ttl);
        }

        return $response;
    }

    private function requestFingerprint(Request $request): string
    {
        return md5($request->getPathInfo().':'.$request->getContent());
    }
}
