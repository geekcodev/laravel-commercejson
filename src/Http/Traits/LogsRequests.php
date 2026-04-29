<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Traits;

use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait для логирования HTTP запросов и ответов
 */
trait LogsRequests
{
    protected bool $logRequests = false;

    protected string $logChannel = 'stack';

    /**
     * Включить логирование запросов
     */
    public function enableLogging(string $channel = 'stack'): self
    {
        $this->logRequests = true;
        $this->logChannel = $channel;

        return $this;
    }

    /**
     * Выключить логирование запросов
     */
    public function disableLogging(): self
    {
        $this->logRequests = false;

        return $this;
    }

    /**
     * Логировать исходящий запрос
     *
     * @param  array<string, mixed>  $options
     */
    protected function logRequest(string $method, string $uri, array $options = []): void
    {
        if (! $this->logRequests) {
            return;
        }

        $context = [
            'method' => $method,
            'uri' => $uri,
            'headers' => $this->sanitizeHeaders($options['headers'] ?? []),
            'query' => $options['query'] ?? null,
            'body' => $options['json'] ?? $options['body'] ?? null,
        ];

        Log::channel($this->logChannel)->debug(
            "CommerceJSON Request: {$method} {$uri}",
            $context
        );
    }

    /**
     * Логировать полученный ответ
     */
    protected function logResponse(ResponseInterface $response): void
    {
        if (! $this->logRequests) {
            return;
        }

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        // Восстанавливаем поток для дальнейшего использования
        $response->getBody()->rewind();

        $context = [
            'status' => $statusCode,
            'headers' => $response->getHeaders(),
            'body' => $this->sanitizeResponseBody($body),
        ];

        Log::channel($this->logChannel)->debug(
            "CommerceJSON Response: {$statusCode}",
            $context
        );
    }

    /**
     * Санилизировать заголовки (удалить чувствительные данные)
     *
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['Authorization', 'X-Session-Token', 'Cookie'];

        foreach ($sensitive as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '***REDACTED***';
            }
        }

        return $headers;
    }

    /**
     * Санилизировать тело ответа (удалить чувствительные данные)
     */
    protected function sanitizeResponseBody(string $body): string
    {
        if (! $this->logRequests) {
            return $body;
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $body;
        }

        // Санилизация чувствительных полей
        $sensitiveFields = [
            'password',
            'token',
            'secret',
            'api_key',
            'authorization',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }

        return json_encode($data);
    }
}
