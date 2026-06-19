<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\UpsertCounterpartyCommand;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Data\CounterpartyImportData;
use GeekCo\CommerceJson\Data\ErrorResponseData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
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
            perPage: max(1, min(1000, (int) ($request->input('limit', 100)))),
            type: $request->input('type'),
            updated_after: $request->input('updated_after'),
            include_deleted: $request->boolean('include_deleted', false),
        );
        $counterparties = $this->queryBus->ask($query);

        return response()->json([
            'counterparties' => CounterpartyData::collect($counterparties->items(), DataCollection::class),
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

            return response()->json(CounterpartyData::from($counterparty));
        } catch (ModelNotFoundException) {
            return response()->json(
                ErrorResponseData::from(['error' => ['code' => 'NOT_FOUND', 'message' => 'Counterparty not found']]),
                404
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        $import = CounterpartyImportData::from($request->all());
        $processed = 0;
        $errors = [];

        foreach ($import->counterparties as $counterpartyData) {
            try {
                $this->commandBus->dispatch(
                    new UpsertCounterpartyCommand($counterpartyData)
                );
                $processed++;
            } catch (QueryException $e) {
                $fe = new ForeignKeyViolationException($e);
                $errors[] = [
                    'id' => $counterpartyData->id,
                    'code' => $fe->errorCode,
                    'message' => $fe->getMessage(),
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $counterpartyData->id,
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
