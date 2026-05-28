<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\CreateProductCommand;
use GeekCo\CommerceJson\Commands\DeleteProductCommand;
use GeekCo\CommerceJson\Data\ProductData;
use GeekCo\CommerceJson\Queries\GetProductQuery;
use GeekCo\CommerceJson\Queries\GetProductsQuery;
use Illuminate\Contracts\Bus\Dispatcher;
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
        $command = new CreateProductCommand(ProductData::from($request->all()));
        $product = $this->commandBus->dispatch($command);

        return response()->json(ProductData::from($product), 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = $this->queryBus->ask(new GetProductQuery($id));
        $command = new DeleteProductCommand($product);
        $this->commandBus->dispatch($command);

        return response()->json(['message' => 'Product deleted']);
    }
}
