<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\CreateCounterpartyCommand;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Queries\GetCounterpartiesQuery;
use GeekCo\CommerceJson\Queries\GetCounterpartyQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class CounterpartyController extends Controller
{
    public function __construct(
        private readonly Dispatcher $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetCounterpartiesQuery(
            perPage: (int) ($request->input('per_page', 15))
        );
        $counterparties = $this->queryBus->ask($query);

        return response()->json([
            'data' => CounterpartyData::collect($counterparties->items(), DataCollection::class),
            'meta' => [
                'current_page' => $counterparties->currentPage(),
                'last_page' => $counterparties->lastPage(),
                'per_page' => $counterparties->perPage(),
                'total' => $counterparties->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $query = new GetCounterpartyQuery($id);
        $counterparty = $this->queryBus->ask($query);

        return response()->json(CounterpartyData::from($counterparty));
    }

    public function store(Request $request): JsonResponse
    {
        $command = new CreateCounterpartyCommand(CounterpartyData::from($request->all()));
        $counterparty = $this->commandBus->dispatch($command);

        return response()->json(CounterpartyData::from($counterparty), 201);
    }
}
