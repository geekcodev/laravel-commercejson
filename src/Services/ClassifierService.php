<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use DateTimeInterface;
use GeekCo\CommerceJson\Data\ClassifierData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Events\ClassifierImported;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\PriceType;
use GeekCo\CommerceJson\Models\PropertyDefinition;

/**
 * Сервис для работы с классификатором (категории, свойства, типы цен)
 */
class ClassifierService
{
    public function __construct(
        protected CommerceJsonConnector $connector
    ) {}

    /**
     * Получить классификатор
     */
    public function getClassifier(?\DateTime $updatedAfter = null): ClassifierData
    {
        $query = $updatedAfter
            ? ['updated_after' => $updatedAfter->format(DateTimeInterface::ATOM)]
            : [];

        $response = $this->connector->get('/catalog/classifier', $query);

        return ClassifierData::from($response->json());
    }

    /**
     * Импортировать классификатор (полная замена)
     */
    public function importClassifier(ClassifierData $classifier, ?string $idempotencyKey = null): ImportResultData
    {
        $response = $this->connector->post(
            '/catalog/classifier',
            $classifier->toArray(),
            $idempotencyKey
        );

        // Dispatch event
        event(new ClassifierImported);

        return ImportResultData::from($response->json());
    }

    /**
     * Синхронизировать категории из классификатора
     *
     * @param  array<int, array>  $categories
     * @return int количество синхронизированных категорий
     */
    public function syncCategories(array $categories): int
    {
        $count = 0;

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['id' => $categoryData['id']],
                [
                    'parent_id' => $categoryData['parent_id'] ?? null,
                    'name' => $categoryData['name'],
                    'code' => $categoryData['code'] ?? null,
                    'sort' => $categoryData['sort'] ?? 0,
                    'is_active' => $categoryData['is_active'] ?? true,
                    'image_url' => $categoryData['image_url'] ?? null,
                    'seo_title' => $categoryData['seo']['title'] ?? null,
                    'seo_description' => $categoryData['seo']['description'] ?? null,
                    'seo_keywords' => $categoryData['seo']['keywords'] ?? null,
                ]
            );

            $count++;

            // Рекурсивная синхронизация дочерних категорий
            if (! empty($categoryData['children'])) {
                $count += $this->syncCategories($categoryData['children']);
            }
        }

        return $count;
    }

    /**
     * Синхронизировать свойства из классификатора
     *
     * @param  array<int, array>  $properties
     * @return int количество синхронизированных свойств
     */
    public function syncProperties(array $properties): int
    {
        $count = 0;

        foreach ($properties as $propertyData) {
            PropertyDefinition::updateOrCreate(
                ['id' => $propertyData['id']],
                [
                    'name' => $propertyData['name'],
                    'code' => $propertyData['code'] ?? null,
                    'type' => $propertyData['type'],
                    'unit' => $propertyData['unit'] ?? null,
                    'is_filterable' => $propertyData['is_filterable'] ?? false,
                    'is_required' => $propertyData['is_required'] ?? false,
                    'use_for_catalog' => $propertyData['use_for_catalog'] ?? true,
                    'use_for_offers' => $propertyData['use_for_offers'] ?? false,
                    'use_for_documents' => $propertyData['use_for_documents'] ?? false,
                    'enum_values' => $propertyData['enum_values'] ?? null,
                    'applies_to_all' => $propertyData['applies_to_all'] ?? false,
                    'category_ids' => $propertyData['category_ids'] ?? null,
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Синхронизировать типы цен из классификатора
     *
     * @param  array<int, array>  $priceTypes
     * @return int количество синхронизированных типов цен
     */
    public function syncPriceTypes(array $priceTypes): int
    {
        $count = 0;

        foreach ($priceTypes as $priceTypeData) {
            PriceType::updateOrCreate(
                ['id' => $priceTypeData['id']],
                [
                    'name' => $priceTypeData['name'],
                    'currency' => $priceTypeData['currency'] ?? 'RUB',
                    'description' => $priceTypeData['description'] ?? null,
                    'is_default' => $priceTypeData['is_default'] ?? false,
                ]
            );

            $count++;
        }

        return $count;
    }
}
