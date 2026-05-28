<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Commands\UpdateOrderCommand;
use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Data\ErrorResponseData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\MoneyData;
use GeekCo\CommerceJson\Data\OrderCreateData;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderImportData;
use GeekCo\CommerceJson\Data\OrderTotalsData;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
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

            $currency = $createData->base_currency
                ?? CurrencyEnum::tryFrom(config('commercejson.default_currency'))
                ?? CurrencyEnum::RUB;

            $items = array_map(fn ($item) => [
                'id' => (string) Str::uuid(),
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                // TODO: server-side price lookup from catalog
                'price' => MoneyData::from(['amount' => '0', 'currency' => $currency->value]),
                'total' => MoneyData::from(['amount' => '0', 'currency' => $currency->value]),
            ], $createData->items);

            $zeroMoney = ['amount' => '0', 'currency' => $currency->value];

            $data = array_merge($createData->toArray(), [
                'id' => (string) Str::uuid(),
                'number' => 'ORD-'.date('Ymd').'-'.Str::upper(Str::random(6)),
                'status' => OrderStatusEnum::New,
                'items' => $items,
                'totals' => OrderTotalsData::from([
                    'subtotal' => MoneyData::from($zeroMoney),
                    'total' => MoneyData::from($zeroMoney),
                    'discount' => null,
                    'delivery' => null,
                    'tax' => null,
                ]),
            ]);

            $command = new CreateOrderCommand(OrderData::from($data));
            $order = $this->commandBus->dispatch($command);

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
                $orderArray = array_filter([
                    'id' => $bulkItem->id,
                    'external_id' => $bulkItem->external_id,
                    'status' => $bulkItem->status,
                    'comment' => $bulkItem->comment,
                    'custom_attributes' => $bulkItem->custom_attributes,
                ], fn ($v) => $v !== null);

                $orderArray['id'] ??= (string) Str::uuid();
                $orderArray['status'] ??= OrderStatusEnum::New;
                $orderArray['number'] = 'ORD-'.date('Ymd').'-'.Str::upper(Str::random(6));

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
                    $orderArray['items'] = $items;

                    $sum = array_reduce($items, fn ($c, $i) => $c + ((float) $i['price']['amount'] * $i['quantity']), 0);
                    $currency = $items[0]['price']['currency'] ?? CurrencyEnum::RUB->value;
                    $orderArray['totals'] = [
                        'subtotal' => ['amount' => number_format($sum, 2, '.', ''), 'currency' => $currency],
                        'total' => ['amount' => number_format($sum, 2, '.', ''), 'currency' => $currency],
                    ];
                }

                $this->commandBus->dispatch(new UpsertOrderCommand(
                    OrderData::from($orderArray),
                    $bulkItem->delivery
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
