<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client;

use GeekCo\CommerceJson\Http\Client\Dto\Request\RequestDto;
use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Http\Client\Exceptions\BusinessException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ConnectionException;
use GeekCo\CommerceJson\Http\Client\Exceptions\HttpClientException;
use GeekCo\CommerceJson\Http\Client\Exceptions\NotFoundException;
use GeekCo\CommerceJson\Http\Client\Exceptions\RateLimitException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ServerException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Основная реализация HTTP клиента для CommerceJSON API
 */
class CommerceJsonHttpClient implements HttpClientInterface
{
    protected Client $client;

    protected LoggerInterface $logger;

    protected RetryStrategyInterface $retryStrategy;

    public function __construct(
        protected string $baseUrl,
        protected string $authToken,
        protected int $timeout = 30,
        protected string $authType = 'bearer',
        ?LoggerInterface $logger = null,
        ?RetryStrategyInterface $retryStrategy = null,
    ) {
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => $timeout,
        ]);

        $this->logger = $logger ?? new NullLogger;
        $this->retryStrategy = $retryStrategy ?? new ExponentialBackoffStrategy;
    }

    /**
     * Установить logger
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Установить стратегию retry
     */
    public function setRetryStrategy(RetryStrategyInterface $strategy): self
    {
        $this->retryStrategy = $strategy;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $uri, array $query = []): ResponseDto
    {
        return $this->request(RequestDto::get($uri, $query, $this->buildDefaultHeaders()));
    }

    /**
     * {@inheritDoc}
     */
    public function post(string $uri, array $data = [], ?string $idempotencyKey = null): ResponseDto
    {
        $headers = $this->buildDefaultHeaders();

        if ($idempotencyKey !== null) {
            $headers['X-Idempotency-Key'] = $idempotencyKey;
        }

        return $this->request(RequestDto::post($uri, $data, $headers, $idempotencyKey));
    }

    /**
     * {@inheritDoc}
     */
    public function patch(string $uri, array $data = [], ?string $idempotencyKey = null): ResponseDto
    {
        $headers = $this->buildDefaultHeaders();

        if ($idempotencyKey !== null) {
            $headers['X-Idempotency-Key'] = $idempotencyKey;
        }

        return $this->request(RequestDto::patch($uri, $data, $headers, $idempotencyKey));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $uri): ResponseDto
    {
        return $this->request(RequestDto::delete($uri, $this->buildDefaultHeaders()));
    }

    /**
     * {@inheritDoc}
     */
    public function request(RequestDto $request): ResponseDto
    {
        $attempt = 0;

        while (true) {
            try {
                $this->logRequest($request);

                $response = $this->client->request(
                    $request->method,
                    $request->uri,
                    $this->buildGuzzleOptions($request)
                );

                $responseDto = ResponseDto::fromPsr7Response($response);

                $this->logResponse($responseDto);

                return $responseDto;
            } catch (Throwable $e) {
                $shouldRetry = $this->shouldRetry($e, $attempt);

                if (! $shouldRetry) {
                    throw $this->mapException($e);
                }

                $attempt++;

                if ($attempt > $this->retryStrategy->getMaxAttempts()) {
                    throw $this->mapException($e);
                }

                $delay = $this->retryStrategy->getDelayMs($attempt, $e);
                $this->logger->warning(
                    "Request failed (attempt {$attempt}), retrying in {$delay}ms",
                    ['error' => $e->getMessage()]
                );

                if ($delay > 0) {
                    usleep($delay * 1000);
                }
            }
        }
    }

    /**
     * Построение опций для Guzzle
     *
     * @return array{query: array, json: array, headers: array}
     */
    protected function buildGuzzleOptions(RequestDto $request): array
    {
        return [
            'query' => $request->query,
            'json' => ! empty($request->body) ? $request->body : null,
            'headers' => $request->headers,
        ];
    }

    /**
     * Построение заголовков по умолчанию
     *
     * @return array<string, string>
     */
    protected function buildDefaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Request-ID' => (string) Str::uuid(),
        ];

        // Добавляем заголовок авторизации
        $authHeader = $this->buildAuthHeader();
        if ($authHeader !== null) {
            $headers['Authorization'] = $authHeader;
        }

        return $headers;
    }

    /**
     * Построение заголовка авторизации
     */
    protected function buildAuthHeader(): ?string
    {
        if (empty($this->authToken)) {
            return null;
        }

        return match ($this->authType) {
            'basic' => 'Basic '.base64_encode($this->authToken),
            'bearer' => 'Bearer '.$this->authToken,
            default => $this->authToken,
        };
    }

    /**
     * Проверка, нужно ли делать retry
     */
    protected function shouldRetry(Throwable $e, int $attempt): bool
    {
        // Не делаем retry для ClientException (кроме 429 и 5xx)
        if ($e instanceof ClientException) {
            $statusCode = $e->getResponse()?->getStatusCode() ?? 0;

            return $statusCode === 429 || $statusCode >= 500;
        }

        // Делаем retry для ServerException
        if ($e instanceof GuzzleServerException) {
            return true;
        }

        // Делаем retry для ConnectionException
        if ($e instanceof ConnectException) {
            return true;
        }

        return false;
    }

    /**
     * Маппинг исключений Guzzle в кастомные исключения
     */
    protected function mapException(Throwable $e): HttpClientException
    {
        if ($e instanceof ClientException) {
            return $this->mapClientException($e);
        }

        if ($e instanceof GuzzleServerException) {
            return $this->mapServerException($e);
        }

        if ($e instanceof ConnectException) {
            return new ConnectionException(
                message: 'Connection error: '.$e->getMessage(),
                previous: $e
            );
        }

        if ($e instanceof GuzzleException) {
            return new HttpClientException(
                message: 'HTTP error: '.$e->getMessage(),
                previous: $e
            );
        }

        return new HttpClientException(
            message: $e->getMessage(),
            previous: $e
        );
    }

    /**
     * Маппинг ClientException
     */
    protected function mapClientException(ClientException $e): HttpClientException
    {
        $response = $e->getResponse();
        $statusCode = $response?->getStatusCode() ?? 0;
        $body = $this->parseResponseBody($response);

        return match ($statusCode) {
            401, 403 => new AuthenticationException(
                message: $body['error']['message'] ?? 'Authentication failed',
                statusCode: $statusCode,
                previous: $e
            ),
            404 => new NotFoundException(
                message: $body['error']['message'] ?? 'Not found',
                statusCode: $statusCode,
                previous: $e
            ),
            400 => new ValidationException(
                message: $body['error']['message'] ?? 'Validation failed',
                statusCode: $statusCode,
                errors: $body['error']['details'] ?? [],
                previous: $e
            ),
            422 => new BusinessException(
                message: $body['error']['message'] ?? 'Business error',
                statusCode: $statusCode,
                businessCode: $body['error']['code'] ?? null,
                errors: $body['error']['details'] ?? [],
                previous: $e
            ),
            429 => new RateLimitException(
                message: $body['error']['message'] ?? 'Rate limit exceeded',
                retryAfter: (int) ($response?->getHeaderLine('Retry-After') ?: 60),
                statusCode: $statusCode,
                previous: $e
            ),
            default => new BusinessException(
                message: $body['error']['message'] ?? 'Business error',
                statusCode: $statusCode,
                previous: $e
            )
        };
    }

    /**
     * Маппинг ServerException
     */
    protected function mapServerException(GuzzleServerException $e): HttpClientException
    {
        $response = $e->getResponse();
        $statusCode = $response?->getStatusCode() ?? 500;
        $body = $this->parseResponseBody($response);

        return new ServerException(
            message: $body['error']['message'] ?? 'Server error',
            statusCode: $statusCode,
            previous: $e
        );
    }

    /**
     * Парсинг тела ответа
     *
     * @return array<string, mixed>
     */
    protected function parseResponseBody(?ResponseInterface $response): array
    {
        if ($response === null) {
            return [];
        }

        $body = $response->getBody();

        if ($body === null || $body->getSize() === 0) {
            return [];
        }

        $contents = $body->getContents();

        if ($contents === '') {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => ['message' => 'Invalid JSON response']];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Логирование запроса
     */
    protected function logRequest(RequestDto $request): void
    {
        $this->logger->debug(
            "HTTP {$request->method} {$request->uri}",
            [
                'query' => $request->query,
                'headers' => $this->sanitizeHeaders($request->headers),
                'body' => $request->body,
            ]
        );
    }

    /**
     * Логирование ответа
     */
    protected function logResponse(ResponseDto $response): void
    {
        $this->logger->debug(
            "HTTP {$response->statusCode}",
            [
                'statusCode' => $response->statusCode,
                'headers' => $this->sanitizeHeaders($response->headers),
                'body' => $response->data,
            ]
        );
    }

    /**
     * Санитизация заголовков для логирования (скрыть чувствительные данные)
     *
     * @param  array<string, string|string[]>  $headers
     * @return array<string, string|string[]>
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['Authorization', 'X-Idempotency-Key', 'Cookie', 'Set-Cookie'];

        foreach ($sensitive as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '***REDACTED***';
            }
        }

        return $headers;
    }
}
