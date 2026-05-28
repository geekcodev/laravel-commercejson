<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\DeleteProductCommand;
use GeekCo\CommerceJson\Commands\UpsertProductCommand;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Data\ProductImportData;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
use GeekCo\CommerceJson\Queries\GetProductQuery;
use GeekCo\CommerceJson\Queries\GetProductsQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly Dispatcher $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetProductsQuery(
            perPage: (int) ($request->input('per_page', 15))
        );
        $products = $this->queryBus->ask($query);

        return response()->json([
            'data' => ProductData::collect($products->items(), DataCollection::class),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $query = new GetProductQuery($id);
        $product = $this->queryBus->ask($query);

        return response()->json(ProductData::from($product));
    }

    public function store(Request $request): JsonResponse
    {
        $import = ProductImportData::from($request->all());

        $processed = 0;
        $errors = [];

        foreach ($import->products as $productData) {
            try {
                $this->commandBus->dispatch(new UpsertProductCommand($productData));
                $processed++;
            } catch (QueryException $e) {
                $fe = new ForeignKeyViolationException($e);
                $errors[] = ['id' => $productData->id, 'code' => $fe->errorCode, 'message' => $fe->getMessage()];
            } catch (\Exception $e) {
                $errors[] = ['id' => $productData->id, 'code' => 'INTERNAL_ERROR', 'message' => $e->getMessage()];
            }
        }

        return response()->json(ImportResultData::from([
            'success' => empty($errors),
            'processed' => $processed,
            'errors' => $errors,
            'warnings' => [],
        ]));
    }

    public function destroy(string $id): JsonResponse
    {
        $product = $this->queryBus->ask(new GetProductQuery($id));
        $command = new DeleteProductCommand($product);
        $this->commandBus->dispatch($command);

        return response()->json(ProductData::from($product->fresh()));
    }
}
