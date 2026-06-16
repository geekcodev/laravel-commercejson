<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\UpsertOfferCommand;
use GeekCo\CommerceJson\Commands\UpsertPriceTypeCommand;
use GeekCo\CommerceJson\Commands\UpsertWarehouseCommand;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Data\OfferImportData;
use GeekCo\CommerceJson\Data\PriceTypeData;
use GeekCo\CommerceJson\Data\WarehouseData;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
use GeekCo\CommerceJson\Queries\GetOffersQuery;
use GeekCo\CommerceJson\Queries\GetPriceTypesQuery;
use GeekCo\CommerceJson\Queries\GetWarehousesQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class OfferController extends Controller
{
    public function __construct(
        private readonly Dispatcher $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetOffersQuery(
            perPage: (int) ($request->input('limit', 15)),
            price_type_id: $request->input('price_type_id'),
            warehouse_id: $request->input('warehouse_id'),
            updated_after: $request->input('updated_after'),
            include_deleted: $request->boolean('include_deleted', false),
        );
        $offers = $this->queryBus->ask($query);

        $items = OfferData::collect($offers->items(), DataCollection::class);

        $response = [
            'offers' => $items,
            'pagination' => [
                'page' => $offers->currentPage(),
                'limit' => $offers->perPage(),
                'total' => $offers->total(),
                'has_next' => $offers->currentPage() < $offers->lastPage(),
            ],
        ];

        if ($offers->currentPage() === 1) {
            $response['price_types'] = PriceTypeData::collect(
                $this->queryBus->ask(new GetPriceTypesQuery),
                DataCollection::class
            );
            $response['warehouses'] = WarehouseData::collect(
                $this->queryBus->ask(new GetWarehousesQuery),
                DataCollection::class
            );
        }

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $import = OfferImportData::from($request->all());

        if ($import->price_types) {
            foreach ($import->price_types as $priceTypeData) {
                $this->commandBus->dispatch(new UpsertPriceTypeCommand($priceTypeData));
            }
        }

        if ($import->warehouses) {
            foreach ($import->warehouses as $warehouseData) {
                $this->commandBus->dispatch(new UpsertWarehouseCommand($warehouseData));
            }
        }

        $processed = 0;
        $errors = [];

        foreach ($import->offers as $offerData) {
            try {
                $this->commandBus->dispatch(new UpsertOfferCommand($offerData));
                $processed++;
            } catch (QueryException $e) {
                $fe = new ForeignKeyViolationException($e);
                $errors[] = ['id' => $offerData->product_id, 'code' => $fe->errorCode, 'message' => $fe->getMessage()];
            } catch (\Exception $e) {
                $errors[] = ['id' => $offerData->product_id, 'code' => 'INTERNAL_ERROR', 'message' => $e->getMessage()];
            }
        }

        return response()->json(ImportResultData::from([
            'success' => empty($errors),
            'processed' => $processed,
            'errors' => $errors,
            'warnings' => [],
        ]));
    }

    public function priceTypes(Request $request): JsonResponse
    {
        $priceTypes = $this->queryBus->ask(new GetPriceTypesQuery);

        return response()->json([
            'price_types' => PriceTypeData::collect($priceTypes, DataCollection::class),
        ]);
    }
}
