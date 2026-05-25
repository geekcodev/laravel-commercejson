<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Commands\DeleteOrderCommand;
use GeekCo\CommerceJson\Commands\UpdateOrderCommand;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Queries\GetOrderQuery;
use GeekCo\CommerceJson\Queries\GetOrdersQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetOrdersQuery(
            perPage: (int) ($request->get('per_page', 15))
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
        $command = new CreateOrderCommand(OrderData::from($request->all()));
        $order = $this->commandBus->dispatch($command);

        return response()->json(OrderData::from($order), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $order = $this->queryBus->ask(new GetOrderQuery($id));
        $command = new UpdateOrderCommand($order, OrderData::from($request->all()));
        $order = $this->commandBus->dispatch($command);

        return response()->json(OrderData::from($order));
    }

    public function destroy(string $id): JsonResponse
    {
        $order = $this->queryBus->ask(new GetOrderQuery($id));
        $command = new DeleteOrderCommand($order);
        $this->commandBus->dispatch($command);

        return response()->json(['message' => 'Order deleted']);
    }
}
