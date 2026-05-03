<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client;

use Exception;
use GeekCo\CommerceJson\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Exceptions\BusinessException;
use GeekCo\CommerceJson\Exceptions\RateLimitException;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Traits\AuthenticatesRequests;
use GeekCo\CommerceJson\Http\Traits\HandlesIdempotency;
use GeekCo\CommerceJson\Http\Traits\LogsRequests;
use GeekCo\CommerceJson\Http\Traits\ManagesPagination;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Основной HTTP-клиент для взаимодействия с CommerceJSON API
 */
class CommerceJsonConnector
{
    use AuthenticatesRequests;
    use HandlesIdempotency;
    use LogsRequests;
    use ManagesPagination;

    protected Client $client;

    protected ?string $sessionToken = null;

    protected int $retryAttempts = 3;

    public function __construct(
        protected string $baseUrl,
        protected string $authToken,
        protected int $timeout = 30,
        protected string $authType = 'bearer'
    ) {
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => $timeout,
            // Headers are now built in buildHeaders() to ensure dynamic headers are included
            // 'headers' => [
            //     'Accept' => 'application/json',
            //     'Content-Type' => 'application/json',
            // ],
        ]);
    }

    /**
     * Handshake запрос
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws BusinessException
     * @throws RateLimitException
     */
    public function handshake(): ResponseInterface
    {
        return $this->get('/handshake');
    }

    /**
     * Получить session token из handshake
     */
    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    /**
     * Установить session token
     */
    public function setSessionToken(string $sessionToken): self
    {
        $this->sessionToken = $sessionToken;

        return $this;
    }

    /**
     * GET запрос
     *
     * @param  array<string, mixed>  $query
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws BusinessException
     * @throws RateLimitException
     */
    public function get(string $uri, array $query = []): ResponseInterface
    {
        return $this->request('GET', $uri, [
            'query' => $query,
        ]);
    }

    /**
     * POST запрос
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws BusinessException
     * @throws RateLimitException
     */
    public function post(string $uri, array $data = [], ?string $idempotencyKey = null): ResponseInterface
    {
        $options = ['json' => $data];

        if ($idempotencyKey) {
            $options['headers']['X-Idempotency-Key'] = $idempotencyKey;
        }

        return $this->request('POST', $uri, $options);
    }

    /**
     * PATCH запрос
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws BusinessException
     * @throws RateLimitException
     */
    public function patch(string $uri, array $data = [], ?string $idempotencyKey = null): ResponseInterface
    {
        $options = ['json' => $data];

        if ($idempotencyKey) {
            $options['headers']['X-Idempotency-Key'] = $idempotencyKey;
        }

        return $this->request('PATCH', $uri, $options);
    }

    /**
     * DELETE запрос
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws BusinessException
     * @throws RateLimitException|GuzzleException
     */
    public function delete(string $uri): ResponseInterface
    {
        return $this->request('DELETE', $uri);
    }

    /**
     * Выполнение HTTP запроса с retry logic
     *
     * @param  array<string, mixed>  $options
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws BusinessException
     * @throws RateLimitException|GuzzleException
     * @throws Exception
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            try {
                $requestOptions = array_merge_recursive(
                    $options,
                    ['headers' => $this->buildHeaders()]
                );

                $this->logRequest($method, $uri, $requestOptions);

                $response = $this->client->request($method, $uri, $requestOptions);

                $this->logResponse($response);

                return $response;
            } catch (ClientException $e) {
                $response = $e->getResponse();
                $statusCode = $response?->getStatusCode();

                // Не повторяем запрос при ошибках 4xx (кроме 429)
                if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
                    throw $this->mapHttpException($e);
                }

                // Retry для 429 и 5xx
                if (++$attempt >= $this->retryAttempts) {
                    throw $this->mapHttpException($e);
                }

                // Exponential backoff
                $delay = (2 ** $attempt) * 1000; // 2s, 4s, 8s
                usleep($delay * 1000);
            } catch (ServerException $e) { // Catch for 5xx errors
                if (++$attempt >= $this->retryAttempts) {
                    throw $e; // Re-throw generic ServerException if retries exhausted
                }
                // Exponential backoff
                $delay = (2 ** $attempt) * 1000;
                usleep($delay * 1000);
            } catch (ConnectException $e) {
                if (++$attempt >= $this->retryAttempts) {
                    throw $e;
                }

                // Retry при проблемах с соединением
                $delay = (2 ** $attempt) * 1000;
                usleep($delay * 1000);
            }
        }
    }

    /**
     * Асинхронный GET запрос
     *
     * @param  array<string, mixed>  $query
     */
    public function getAsync(string $uri, array $query = []): PromiseInterface
    {
        return $this->client->requestAsync('GET', $uri, [
            'query' => $query,
            'headers' => $this->buildHeaders(),
        ]);
    }

    /**
     * Асинхронный POST запрос
     *
     * @param  array<string, mixed>  $data
     */
    public function postAsync(string $uri, array $data = []): PromiseInterface
    {
        return $this->client->requestAsync('POST', $uri, [
            'json' => $data,
            'headers' => $this->buildHeaders(),
        ]);
    }

    /**
     * Построение заголовков запроса
     *
     * @return array<string, string>
     */
    protected function buildHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Request-ID' => (string) Str::uuid(),
        ];

        // Авторизация
        $authHeader = $this->buildAuthHeader();
        if ($authHeader) {
            $headers['Authorization'] = $authHeader;
        }

        // Session token (если получен из handshake)
        if ($this->sessionToken) {
            $headers['X-Session-Token'] = $this->sessionToken;
        }

        return $headers;
    }

    /**
     * Маппинг HTTP исключений
     *
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws BusinessException
     * @throws RateLimitException
     */
    protected function mapHttpException(ClientException $e): Exception
    {
        $response = $e->getResponse();
        $statusCode = $response?->getStatusCode();
        $body = json_decode($response?->getBody()->getContents() ?? '', true);

        return match ($statusCode) {
            401 => new AuthenticationException(
                $body['error']['message'] ?? 'Authentication failed',
                $statusCode,
                $e
            ),
            403 => new AuthenticationException(
                $body['error']['message'] ?? 'Forbidden',
                $statusCode,
                $e
            ),
            404 => new RuntimeException(
                $body['error']['message'] ?? 'Not found',
                $statusCode,
                $e
            ),
            400 => new ValidationException(
                $body['error']['message'] ?? 'Validation failed',
                $body['error']['details'] ?? [],
                $statusCode,
                $e
            ),
            422 => new BusinessException(
                $body['error']['message'] ?? 'Business logic error',
                $body['error']['code'] ?? 'BUSINESS_ERROR',
                $statusCode,
                $e
            ),
            429 => new RateLimitException(
                $body['error']['message'] ?? 'Rate limit exceeded',
                (int) ($response?->getHeaderLine('Retry-After') ?? 60),
                $statusCode,
                $e
            ),
            default => new RuntimeException(
                $body['error']['message'] ?? 'Unexpected error',
                $statusCode,
                $e
            )
        };
    }

    /**
     * Установить количество попыток повторного запроса
     */
    public function setRetryAttempts(int $attempts): self
    {
        $this->retryAttempts = $attempts;

        return $this;
    }
}
