<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Client;

use GeekCo\CommerceJson\Http\Client\Dto\Request\RequestDto;
use GeekCo\CommerceJson\Http\Client\Dto\Response\ResponseDto;
use GeekCo\CommerceJson\Http\Client\Exceptions\HttpClientException;

/**
 * Интерфейс HTTP клиента для CommerceJSON API
 */
interface HttpClientInterface
{
    /**
     * Выполнить GET запрос
     *
     * @param  array<string, mixed>  $query
     *
     * @throws HttpClientException
     */
    public function get(string $uri, array $query = []): ResponseDto;

    /**
     * Выполнить POST запрос
     *
     * @param  array<string, mixed>  $data
     *
     * @throws HttpClientException
     */
    public function post(string $uri, array $data = [], ?string $idempotencyKey = null): ResponseDto;

    /**
     * Выполнить PATCH запрос
     *
     * @param  array<string, mixed>  $data
     *
     * @throws HttpClientException
     */
    public function patch(string $uri, array $data = [], ?string $idempotencyKey = null): ResponseDto;

    /**
     * Выполнить DELETE запрос
     *
     * @throws HttpClientException
     */
    public function delete(string $uri): ResponseDto;

    /**
     * Выполнить произвольный запрос
     *
     * @throws HttpClientException
     */
    public function request(RequestDto $request): ResponseDto;
}
