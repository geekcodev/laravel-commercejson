<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Traits;

use Illuminate\Support\Collection;

/**
 * Trait для управления пагинацией в CommerceJSON API
 */
trait ManagesPagination
{
    protected int $currentPage = 1;

    protected int $currentLimit = 100;

    protected int $maxPageSize = 1000;

    /**
     * Установить текущую страницу
     */
    public function setPage(int $page): self
    {
        $this->currentPage = max(1, $page);

        return $this;
    }

    /**
     * Установить размер страницы
     */
    public function setLimit(int $limit): self
    {
        $this->currentLimit = min(max(1, $limit), $this->maxPageSize);

        return $this;
    }

    /**
     * Получить текущую страницу
     */
    public function getPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Получить текущий лимит
     */
    public function getLimit(): int
    {
        return $this->currentLimit;
    }

    /**
     * Установить максимальный размер страницы
     */
    public function setMaxPageSize(int $size): self
    {
        $this->maxPageSize = $size;

        return $this;
    }

    /**
     * Получить максимальный размер страницы
     */
    public function getMaxPageSize(): int
    {
        return $this->maxPageSize;
    }

    /**
     * Сбросить пагинацию к значениям по умолчанию
     */
    public function resetPagination(): self
    {
        $this->currentPage = 1;
        $this->currentLimit = 100;

        return $this;
    }

    /**
     * Построить параметры пагинации для запроса
     *
     * @return array{page: int, limit: int}
     */
    protected function buildPaginationParams(): array
    {
        return [
            'page' => $this->currentPage,
            'limit' => $this->currentLimit,
        ];
    }

    /**
     * Обработать ответ с пагинацией
     *
     * @param  array{pagination: array{page: int, limit: int, total: int, has_next: bool}, items: array}  $response
     * @return array{items: Collection, pagination: array, hasMore: bool}
     */
    protected function parsePaginatedResponse(array $response): array
    {
        $pagination = $response['pagination'] ?? [];
        $items = $response['items'] ?? [];

        return [
            'items' => collect($items),
            'pagination' => $pagination,
            'hasMore' => $pagination['has_next'] ?? false,
        ];
    }

    /**
     * Пройти по всем страницам и собрать все элементы
     *
     * @param  callable  $requestCallback  Функция для выполнения запроса
     */
    public function fetchAllPages(callable $requestCallback): Collection
    {
        $allItems = collect();
        $this->resetPagination();

        do {
            $response = $requestCallback($this->currentPage, $this->currentLimit);
            $parsed = $this->parsePaginatedResponse($response);

            $allItems = $allItems->merge($parsed['items']);

            if ($parsed['hasMore']) {
                $this->currentPage++;
            }
        } while ($parsed['hasMore']);

        return $allItems;
    }

    /**
     * Получить все элементы с использованием lazy collection
     */
    public function lazyFetchAll(callable $requestCallback): \Generator
    {
        $this->resetPagination();

        do {
            $response = $requestCallback($this->currentPage, $this->currentLimit);
            $parsed = $this->parsePaginatedResponse($response);

            foreach ($parsed['items'] as $item) {
                yield $item;
            }

            if ($parsed['hasMore']) {
                $this->currentPage++;
            }
        } while ($parsed['hasMore']);
    }
}
