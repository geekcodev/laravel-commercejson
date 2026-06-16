<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\BulkUpsertOrderCommand;
use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Commands\PatchOrderCommand;
use GeekCo\CommerceJson\Data\ErrorResponseData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OrderCreateData;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Data\OrderPatchData;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
use GeekCo\CommerceJson\Queries\GetOrderQuery;
use GeekCo\CommerceJson\Queries\GetOrdersQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Exceptions\CannotCastEnum;

class OrderController extends Controller
{
    public function __construct(
        private readonly Dispatcher $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetOrdersQuery(
            perPage: (int) ($request->input('limit', 15)),
            status: $request->input('status'),
            document_type: $request->input('document_type'),
            updated_after: $request->input('updated_after'),
            include_deleted: $request->boolean('include_deleted', false),
        );
        $orders = $this->queryBus->ask($query);

        $items = OrderData::collect($orders->items(), DataCollection::class);

        return response()->json([
            'orders' => $items,
            'pagination' => [
                'page' => $orders->currentPage(),
                'limit' => $orders->perPage(),
                'total' => $orders->total(),
                'has_next' => $orders->currentPage() < $orders->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $query = new GetOrderQuery($id);
            $order = $this->queryBus->ask($query);

            return response()->json(OrderData::from($order));
        } catch (ModelNotFoundException) {
            return response()->json(
                ErrorResponseData::from(['error' => ['code' => 'NOT_FOUND', 'message' => 'Order not found']]),
                404
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $createData = OrderCreateData::from($request->all());
            $order = $this->commandBus->dispatch(new CreateOrderCommand($createData));

            return response()->json(OrderData::from($order), 201);
        } catch (CannotCastEnum $e) {
            return response()->json(
                ErrorResponseData::from([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => $e->getMessage(),
                    ],
                ]),
                422
            );
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
            $patch = OrderPatchData::from($request->all());
            $order = $this->commandBus->dispatch(new PatchOrderCommand($id, $patch));

            return response()->json(OrderData::from($order));
        } catch (ModelNotFoundException) {
            return response()->json(
                ErrorResponseData::from(['error' => ['code' => 'NOT_FOUND', 'message' => 'Order not found']]),
                404
            );
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
                $items = null;
                if ($bulkItem->items) {
                    $items = [];
                    foreach ($bulkItem->items as $item) {
                        $currency = $item->price ? $item->price->currency->value : CurrencyEnum::RUB->value;
                        $amount = $item->price ? $item->price->amount : '0';
                        $items[] = [
                            'id' => $item->id ?? (string) Str::uuid(),
                            'product_id' => $item->product_id ?? '',
                            'variant_id' => $item->variant_id,
                            'quantity' => $item->quantity ?? 1,
                            'price' => ['amount' => $amount, 'currency' => $currency],
                            'total' => ['amount' => $amount, 'currency' => $currency],
                        ];
                    }
                }

                $this->commandBus->dispatch(new BulkUpsertOrderCommand(
                    id: $bulkItem->id,
                    external_id: $bulkItem->external_id,
                    status: $bulkItem->status,
                    comment: $bulkItem->comment,
                    custom_attributes: $bulkItem->custom_attributes,
                    items: $items,
                    deliveryTrack: $bulkItem->delivery,
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
