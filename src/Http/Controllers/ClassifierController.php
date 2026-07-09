<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\UpsertCategoryCommand;
use GeekCo\CommerceJson\Commands\UpsertPriceTypeCommand;
use GeekCo\CommerceJson\Commands\UpsertPropertyDefinitionCommand;
use GeekCo\CommerceJson\Data\CategoryData;
use GeekCo\CommerceJson\Data\ClassifierData;
use GeekCo\CommerceJson\Data\ErrorResponseData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\PriceTypeData;
use GeekCo\CommerceJson\Data\PropertyDefinitionData;
use GeekCo\CommerceJson\Events\ClassifierImported;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
use GeekCo\CommerceJson\Queries\GetCategoriesQuery;
use GeekCo\CommerceJson\Queries\GetPriceTypesQuery;
use GeekCo\CommerceJson\Queries\GetPropertyDefinitionsQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Exceptions\CannotCreateData;

class ClassifierController extends Controller
{
    public function __construct(
        private readonly Dispatcher $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $classifierId = config('commercejson.classifier_id', '00000000-0000-0000-0000-000000000001');
        $classifierName = config('commercejson.classifier_name', 'Main Classifier');

        $updatedAfter = $request->input('updated_after');

        $categories = $this->queryBus->ask(new GetCategoriesQuery(
            updated_after: $updatedAfter,
        ));
        $properties = $this->queryBus->ask(new GetPropertyDefinitionsQuery(
            updated_after: $updatedAfter,
        ));
        $priceTypes = $this->queryBus->ask(new GetPriceTypesQuery(
            updated_after: $updatedAfter,
        ));

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
        try {
            $classifierData = ClassifierData::from($request->all());
        } catch (CannotCreateData $e) {
            return response()->json(
                ErrorResponseData::from(['error' => ['code' => 'VALIDATION_ERROR', 'message' => $e->getMessage()]]),
                422
            );
        }

        $processed = 0;
        $errors = [];

        if ($classifierData->categories) {
            foreach ($classifierData->categories as $categoryData) {
                try {
                    $this->commandBus->dispatch(new UpsertCategoryCommand($categoryData));
                    $processed++;
                } catch (QueryException $e) {
                    $fe = new ForeignKeyViolationException($e);
                    $errors[] = ['id' => $categoryData->id, 'code' => $fe->errorCode, 'message' => $fe->getMessage()];
                } catch (\Exception $e) {
                    $errors[] = ['id' => $categoryData->id, 'code' => 'INTERNAL_ERROR', 'message' => $e->getMessage()];
                }
            }
        }

        if ($classifierData->properties) {
            foreach ($classifierData->properties as $propertyData) {
                try {
                    $this->commandBus->dispatch(new UpsertPropertyDefinitionCommand($propertyData));
                    $processed++;
                } catch (QueryException $e) {
                    $fe = new ForeignKeyViolationException($e);
                    $errors[] = ['id' => $propertyData->id, 'code' => $fe->errorCode, 'message' => $fe->getMessage()];
                } catch (\Exception $e) {
                    $errors[] = ['id' => $propertyData->id, 'code' => 'INTERNAL_ERROR', 'message' => $e->getMessage()];
                }
            }
        }

        if ($classifierData->price_types) {
            foreach ($classifierData->price_types as $priceTypeData) {
                try {
                    $this->commandBus->dispatch(new UpsertPriceTypeCommand($priceTypeData));
                    $processed++;
                } catch (QueryException $e) {
                    $fe = new ForeignKeyViolationException($e);
                    $errors[] = ['id' => $priceTypeData->id, 'code' => $fe->errorCode, 'message' => $fe->getMessage()];
                } catch (\Exception $e) {
                    $errors[] = ['id' => $priceTypeData->id, 'code' => 'INTERNAL_ERROR', 'message' => $e->getMessage()];
                }
            }
        }

        event(new ClassifierImported);

        return response()->json(ImportResultData::from([
            'success' => empty($errors),
            'processed' => $processed,
            'errors' => $errors,
            'warnings' => [],
        ]));
    }
}
