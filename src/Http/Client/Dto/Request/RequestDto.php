<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client\Dto\Request;

/**
 * DTO для HTTP запроса
 */
final readonly class RequestDto
{
    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public string $method,
        public string $uri,
        public array $query = [],
        public array $body = [],
        public array $headers = [],
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * Создать GET запрос
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     */
    public static function get(string $uri, array $query = [], array $headers = []): self
    {
        return new self(
            method: 'GET',
            uri: $uri,
            query: $query,
            headers: $headers,
        );
    }

    /**
     * Создать POST запрос
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    public static function post(string $uri, array $data = [], array $headers = [], ?string $idempotencyKey = null): self
    {
        return new self(
            method: 'POST',
            uri: $uri,
            body: $data,
            headers: $headers,
            idempotencyKey: $idempotencyKey,
        );
    }

    /**
     * Создать PATCH запрос
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     */
    public static function patch(string $uri, array $data = [], array $headers = [], ?string $idempotencyKey = null): self
    {
        return new self(
            method: 'PATCH',
            uri: $uri,
            body: $data,
            headers: $headers,
            idempotencyKey: $idempotencyKey,
        );
    }

    /**
     * Создать DELETE запрос
     *
     * @param  array<string, string>  $headers
     */
    public static function delete(string $uri, array $headers = []): self
    {
        return new self(
            method: 'DELETE',
            uri: $uri,
            headers: $headers,
        );
    }
}
