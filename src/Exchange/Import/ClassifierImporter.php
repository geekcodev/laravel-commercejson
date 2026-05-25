<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Exchange\Import;

use GeekCo\CommerceJson\Services\ClassifierService;

/**
 * Импортер классификатора
 */
class ClassifierImporter implements ImporterInterface
{
    public function __construct(
        protected ClassifierService $classifierService
    ) {}

    /**
     * {@inheritDoc}
     */
    public function import(): array
    {
        $classifier = $this->classifierService->getClassifier();

        $stats = [
            'categories' => 0,
            'properties' => 0,
            'priceTypes' => 0,
        ];

        // Синхронизация категорий
        if (! empty($classifier->categories)) {
            $stats['categories'] = $this->classifierService->syncCategories($classifier->categories);
        }

        // Синхронизация свойств
        if (! empty($classifier->properties)) {
            $stats['properties'] = $this->classifierService->syncProperties($classifier->properties);
        }

        // Синхронизация типов цен
        if (! empty($classifier->priceTypes)) {
            $stats['priceTypes'] = $this->classifierService->syncPriceTypes($classifier->priceTypes);
        }

        return $stats;
    }
}
