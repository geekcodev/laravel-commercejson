<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\UpsertWarehouseCommand;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\WarehouseData;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
use GeekCo\CommerceJson\Queries\GetWarehousesQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly Dispatcher $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $includeDeleted = $request->boolean('include_deleted', false);
        $warehouses = $this->queryBus->ask(new GetWarehousesQuery($includeDeleted));

        return response()->json([
            'warehouses' => WarehouseData::collect($warehouses, DataCollection::class),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['warehouses' => 'required|array|min:1']);
        $processed = 0;
        $errors = [];

        foreach ($data['warehouses'] as $warehouseItem) {
            try {
                $this->commandBus->dispatch(
                    new UpsertWarehouseCommand(WarehouseData::from($warehouseItem))
                );
                $processed++;
            } catch (QueryException $e) {
                $fe = new ForeignKeyViolationException($e);
                $errors[] = ['code' => $fe->errorCode, 'message' => $fe->getMessage()];
            } catch (\Exception $e) {
                $errors[] = ['code' => 'INTERNAL_ERROR', 'message' => $e->getMessage()];
            }
        }

        return response()->json(ImportResultData::from([
            'success' => empty($errors),
            'processed' => $processed,
            'errors' => $errors,
            'warnings' => [],
        ]));
    }
}
