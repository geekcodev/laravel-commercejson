<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Commands\UpsertCategoryCommand;
use GeekCo\CommerceJson\Commands\UpsertPriceTypeCommand;
use GeekCo\CommerceJson\Commands\UpsertPropertyDefinitionCommand;
use GeekCo\CommerceJson\Data\CategoryData;
use GeekCo\CommerceJson\Data\ClassifierData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\PriceTypeData;
use GeekCo\CommerceJson\Data\PropertyDefinitionData;
use GeekCo\CommerceJson\Events\ClassifierImported;
use GeekCo\CommerceJson\Repositories\CategoryRepository;
use GeekCo\CommerceJson\Repositories\PriceTypeRepository;
use GeekCo\CommerceJson\Repositories\PropertyDefinitionRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

class ClassifierController extends Controller
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly PropertyDefinitionRepository $propertyDefinitionRepository,
        private readonly PriceTypeRepository $priceTypeRepository,
        private readonly Dispatcher $commandBus,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $classifierId = config('commercejson.classifier_id', '00000000-0000-0000-0000-000000000001');
        $classifierName = config('commercejson.classifier_name', 'Main Classifier');

        $categories = $this->categoryRepository->all();
        $properties = $this->propertyDefinitionRepository->all();
        $priceTypes = $this->priceTypeRepository->all();

        return response()->json(ClassifierData::from([
            'id' => $classifierId,
            'name' => $classifierName,
            'version' => (string) now()->timestamp,
            'categories' => CategoryData::collect($categories, DataCollection::class),
            'properties' => PropertyDefinitionData::collect($properties, DataCollection::class),
            'price_types' => PriceTypeData::collect($priceTypes, DataCollection::class),
            'updated_at' => now()->toIso8601String(),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $classifierData = ClassifierData::from($request->all());

        $processed = 0;
        $errors = [];

        DB::transaction(function () use ($classifierData, &$processed, &$errors) {
            if ($classifierData->categories) {
                $sortedCategories = $classifierData->categories;
                usort($sortedCategories, function (CategoryData $a, CategoryData $b) {
                    if ($a->parent_id === null) {
                        return -1;
                    }
                    if ($b->parent_id === null) {
                        return 1;
                    }

                    return $a->parent_id === $b->id ? 1 : -1;
                });

                foreach ($sortedCategories as $categoryData) {
                    if ($categoryData->parent_id === $categoryData->id) {
                        $categoryData->parent_id = null;
                    }

                    try {
                        $this->commandBus->dispatch(new UpsertCategoryCommand($categoryData));
                        $processed++;
                    } catch (\Exception $e) {
                        $errors[] = ['id' => $categoryData->id, 'message' => $e->getMessage()];
                    }
                }
            }

            if ($classifierData->properties) {
                foreach ($classifierData->properties as $propertyData) {
                    try {
                        $this->commandBus->dispatch(new UpsertPropertyDefinitionCommand($propertyData));
                        $processed++;
                    } catch (\Exception $e) {
                        $errors[] = ['id' => $propertyData->id, 'message' => $e->getMessage()];
                    }
                }
            }

            if ($classifierData->price_types) {
                foreach ($classifierData->price_types as $priceTypeData) {
                    try {
                        $this->commandBus->dispatch(new UpsertPriceTypeCommand($priceTypeData));
                        $processed++;
                    } catch (\Exception $e) {
                        $errors[] = ['id' => $priceTypeData->id, 'message' => $e->getMessage()];
                    }
                }
            }
        });

        event(new ClassifierImported);

        return response()->json(ImportResultData::from([
            'success' => empty($errors),
            'processed' => $processed,
            'errors' => $errors,
            'warnings' => [],
        ]));
    }
}
