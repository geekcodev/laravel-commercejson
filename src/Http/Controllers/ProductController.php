<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\DeleteProductCommand;
use GeekCo\CommerceJson\Commands\UpsertProductCommand;
use GeekCo\CommerceJson\Data\ErrorResponseData;
use GeekCo\CommerceJson\Data\ImportResultData;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Data\ProductImportData;
use GeekCo\CommerceJson\Exceptions\ForeignKeyViolationException;
use GeekCo\CommerceJson\Queries\GetProductQuery;
use GeekCo\CommerceJson\Queries\GetProductsQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            perPage: (int) ($request->input('limit', 15)),
            category_id: $request->input('category_id'),
            is_active: $request->has('is_active') ? $request->boolean('is_active') : null,
            updated_after: $request->input('updated_after'),
            include_deleted: $request->boolean('include_deleted', false),
        );
        $products = $this->queryBus->ask($query);

        $items = ProductData::collect($products->items(), DataCollection::class);

        return response()->json([
            'products' => $items,
            'pagination' => [
                'page' => $products->currentPage(),
                'limit' => $products->perPage(),
                'total' => $products->total(),
                'has_next' => $products->currentPage() < $products->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $query = new GetProductQuery($id);
            $product = $this->queryBus->ask($query);

            return response()->json(ProductData::from($product));
        } catch (ModelNotFoundException) {
            return response()->json(
                ErrorResponseData::from(['error' => ['code' => 'NOT_FOUND', 'message' => 'Product not found']]),
                404
            );
        }
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
        $product = $this->commandBus->dispatch(new DeleteProductCommand($id));

        return response()->json(ProductData::from($product));
    }
}
