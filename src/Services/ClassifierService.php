<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Services;

use DateTimeInterface;
use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Commands\UpsertCategoryCommand;
use GeekCo\CommerceJson\Commands\UpsertPriceTypeCommand;
use GeekCo\CommerceJson\Commands\UpsertPropertyDefinitionCommand;
use GeekCo\CommerceJson\Data\CategoryData;
use GeekCo\CommerceJson\Data\ClassifierData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\PriceTypeData;
use GeekCo\CommerceJson\Data\PropertyDefinitionData;
use GeekCo\CommerceJson\Events\ClassifierImported;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;

/**
 * Сервис для работы с классификатором (категории, свойства, типы цен)
 */
class ClassifierService implements ServiceInterface
{
    public function __construct(
        protected HttpClientInterface $http,
        protected CommandBusInterface $commandBus
    ) {}

    public function setHttpClient(HttpClientInterface $http): static
    {
        $this->http = $http;

        return $this;
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->http;
    }

    public function getCommandBus(): CommandBusInterface
    {
        return $this->commandBus;
    }

    /**
     * Получить классификатор
     */
    public function getClassifier(?\DateTime $updatedAfter = null): ClassifierData
    {
        $query = $updatedAfter
            ? ['updated_after' => $updatedAfter->format(DateTimeInterface::ATOM)]
            : [];

        $response = $this->http->get('/catalog/classifier', $query);

        return ClassifierData::from($response->data);
    }

    /**
     * Импортировать классификатор (полная замена)
     */
    public function importClassifier(ClassifierData $classifier, ?string $idempotencyKey = null): ImportResultData
    {
        $response = $this->http->post(
            '/catalog/classifier',
            $classifier->toArray(),
            $idempotencyKey
        );

        // Dispatch event
        event(new ClassifierImported);

        return ImportResultData::from($response->data);
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
            $command = new UpsertCategoryCommand(CategoryData::from($categoryData));
            $this->commandBus->dispatch($command);

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
            $command = new UpsertPropertyDefinitionCommand(PropertyDefinitionData::from($propertyData));
            $this->commandBus->dispatch($command);

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
            $command = new UpsertPriceTypeCommand(PriceTypeData::from($priceTypeData));
            $this->commandBus->dispatch($command);

            $count++;
        }

        return $count;
    }
}
