<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Commands\UpdateOrderCommand;
use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Data\ErrorResponseData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
use GeekCo\CommerceJson\Queries\GetOrderQuery;
use GeekCo\CommerceJson\Queries\GetOrdersQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly Dispatcher $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetOrdersQuery(
            perPage: (int) ($request->input('per_page', 15))
        );
        $orders = $this->queryBus->ask($query);

        return response()->json([
            'data' => OrderData::collect($orders->items(), DataCollection::class),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $query = new GetOrderQuery($id);
        $order = $this->queryBus->ask($query);

        return response()->json(OrderData::from($order));
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $command = new CreateOrderCommand(OrderData::from($request->all()));
            $order = $this->commandBus->dispatch($command);

            return response()->json(OrderData::from($order), 201);
        } catch (QueryException $e) {
            $fe = new ForeignKeyViolationException($e);

            return response()->json(
                ErrorResponseData::from(['error' => ['code' => $fe->errorCode, 'message' => $fe->getMessage()]]),
                422
            );
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $order = $this->queryBus->ask(new GetOrderQuery($id));
            $command = new UpdateOrderCommand($order, OrderData::from($request->all()));
            $order = $this->commandBus->dispatch($command);

            return response()->json(OrderData::from($order));
        } catch (QueryException $e) {
            $fe = new ForeignKeyViolationException($e);

            return response()->json(
                ErrorResponseData::from(['error' => ['code' => $fe->errorCode, 'message' => $fe->getMessage()]]),
                422
            );
        }
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $importData = OrderImportData::from($request->all());
        $processed = 0;
        $errors = [];

        foreach ($importData->orders as $bulkItem) {
            try {
                $this->commandBus->dispatch(new UpsertOrderCommand(
                    OrderData::from($bulkItem->toArray())
                ));
                $processed++;
            } catch (QueryException $e) {
                $fe = new ForeignKeyViolationException($e);
                $errors[] = [
                    'id' => $bulkItem->id ?? $bulkItem->external_id,
                    'code' => $fe->errorCode,
                    'message' => $fe->getMessage(),
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $bulkItem->id ?? $bulkItem->external_id,
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                ];
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
