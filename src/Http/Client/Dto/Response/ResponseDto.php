<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client\Dto\Response;

use Psr\Http\Message\ResponseInterface;

/**
 * DTO для HTTP ответа
 *
 * @template T of array|object|null
 */
final readonly class ResponseDto
{
    /**
     * @param  T  $data
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public mixed $data,
        private ResponseInterface $response,
    ) {}

    /**
     * Создать DTO из PSR-7 ответа
     *
     * @return self<array>
     */
    public static function fromPsr7Response(ResponseInterface $response): self
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        // Проверка на валидный JSON
        if (json_last_error() !== JSON_ERROR_NONE && $body !== '') {
            throw new \RuntimeException('Invalid JSON response: '.json_last_error_msg());
        }

        return new self(
            statusCode: $response->getStatusCode(),
            headers: $response->getHeaders(),
            data: $data ?? [],
            response: $response,
        );
    }

    /**
     * Проверка на успешный статус (2xx)
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Получить оригинальный PSR-7 ответ
     */
    public function getOriginalResponse(): ResponseInterface
    {
        return $this->response;
    }
}
