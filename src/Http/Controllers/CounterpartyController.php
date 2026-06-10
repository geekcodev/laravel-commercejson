<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\CreateCounterpartyCommand;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Data\ErrorResponseData;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Queries\GetCounterpartiesQuery;
use GeekCo\CommerceJson\Queries\GetCounterpartyQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
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
            perPage: (int) ($request->input('limit', 15))
        );
        $counterparties = $this->queryBus->ask($query);

        $items = new DataCollection(CounterpartyData::class, array_map(
            static fn (Counterparty $model) => CounterpartyData::fromModel($model),
            $counterparties->items()->all()
        ));

        return response()->json([
            'counterparties' => $items,
            'pagination' => [
                'page' => $counterparties->currentPage(),
                'limit' => $counterparties->perPage(),
                'total' => $counterparties->total(),
                'has_next' => $counterparties->currentPage() < $counterparties->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $query = new GetCounterpartyQuery($id);
            $counterparty = $this->queryBus->ask($query);

            return response()->json(CounterpartyData::fromModel($counterparty));
        } catch (ModelNotFoundException) {
            return response()->json(
                ErrorResponseData::from(['error' => ['code' => 'NOT_FOUND', 'message' => 'Counterparty not found']]),
                404
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $command = new CreateCounterpartyCommand(CounterpartyData::from($request->all()));
            $counterparty = $this->commandBus->dispatch($command);

            return response()->json(CounterpartyData::fromModel($counterparty), 201);
        } catch (QueryException $e) {
            $fe = new ForeignKeyViolationException($e);

            return response()->json(
                ErrorResponseData::from(['error' => ['code' => $fe->errorCode, 'message' => $fe->getMessage()]]),
                422
            );
        }
    }
}
