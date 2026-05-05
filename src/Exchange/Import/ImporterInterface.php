<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exchange\Import;

/**
 * Интерфейс для импортеров
 */
interface ImporterInterface
{
    /**
     * Импортировать данные
     *
     * @return array<string, int> Статистика импорта
     */
    public function import(): array;
}
