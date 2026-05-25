<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exchange\Export;

/**
 * Интерфейс для экспортеров
 */
interface ExporterInterface
{
    /**
     * Экспортировать данные
     *
     * @return array<string, int> Статистика экспорта
     */
    public function export(int $limit = 50): array;
}
