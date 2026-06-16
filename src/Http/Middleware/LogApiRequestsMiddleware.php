<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequestsMiddleware
{
    private ?LoggerInterface $logger = null;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isLoggingEnabled($request)) {
            return $next($request);
        }

        $start = microtime(true);
        $logger = $this->getLogger();

        $this->logRequest($logger, $request);

        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000;
        $this->logResponse($logger, $request, $response, $duration);

        return $response;
    }

    private function isLoggingEnabled(Request $request): bool
    {
        if (! config('commercejson.api_logging.enabled', true)) {
            return false;
        }

        $excludePaths = config('commercejson.api_logging.exclude_paths', ['handshake']);
        $path = '/'.trim($request->path(), '/').'/';

        foreach ($excludePaths as $excluded) {
            $search = '/'.trim($excluded, '/').'/';

            if (str_contains($path, $search)) {
                return false;
            }
        }

        return true;
    }

    private function getLogger(): LoggerInterface
    {
        if ($this->logger !== null) {
            return $this->logger;
        }

        $channel = config('commercejson.api_logging.channel', 'commercejson-api');

        try {
            return $this->logger = Log::channel($channel);
        } catch (\InvalidArgumentException) {
        }

        $fallback = config('commercejson.api_logging.fallback_channel', 'commercejson');

        try {
            return $this->logger = Log::channel($fallback);
        } catch (\InvalidArgumentException) {
        }

        return $this->logger = Log::channel('stack');
    }

    private function logRequest(LoggerInterface $logger, Request $request): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if (config('commercejson.api_logging.log_request_body', true)) {
            $body = $request->getContent();

            if (! empty($body)) {
                $decoded = json_decode($body, true);

                $data['body'] = $decoded !== null
                    ? $this->maskSensitiveData($decoded)
                    : $body;
            }
        }

        $logger->info('Incoming API request', $data);
    }

    private function logResponse(LoggerInterface $logger, Request $request, Response $response, float $durationMs): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($durationMs, 2),
        ];

        if (config('commercejson.api_logging.log_response_body', false)) {
            $content = $response->getContent();

            if (! empty($content)) {
                $decoded = json_decode($content, true);

                $data['body'] = $decoded !== null
                    ? $this->maskSensitiveData($decoded)
                    : mb_substr($content, 0, (int) config('commercejson.api_logging.log_response_body_max_length', 1000));
            }
        }

        $level = $response->isSuccessful() ? 'info' : ($response->isClientError() ? 'warning' : 'error');

        $logger->log($level, 'API response', $data);
    }

    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'auth_token', 'access_token', 'api_key'];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }

        return $data;
    }
}
