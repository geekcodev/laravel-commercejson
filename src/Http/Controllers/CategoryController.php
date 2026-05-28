<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\CreateCategoryCommand;
use GeekCo\CommerceJson\Commands\DeleteCategoryCommand;
use GeekCo\CommerceJson\Commands\UpdateCategoryCommand;
use GeekCo\CommerceJson\Data\CategoryData;
use GeekCo\CommerceJson\Queries\GetCategoriesQuery;
use GeekCo\CommerceJson\Queries\GetCategoryQuery;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly Dispatcher $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetCategoriesQuery(
            perPage: (int) ($request->input('per_page', 15))
        );
        $categories = $this->queryBus->ask($query);

        return response()->json([
            'data' => CategoryData::collect($categories->items(), DataCollection::class),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $query = new GetCategoryQuery($id);
        $category = $this->queryBus->ask($query);

        return response()->json(CategoryData::from($category));
    }

    public function store(Request $request): JsonResponse
    {
        $command = new CreateCategoryCommand(CategoryData::from($request->all()));
        $category = $this->commandBus->dispatch($command);

        return response()->json(CategoryData::from($category), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = $this->queryBus->ask(new GetCategoryQuery($id));
        $command = new UpdateCategoryCommand($category, CategoryData::from($request->all()));
        $category = $this->commandBus->dispatch($command);

        return response()->json(CategoryData::from($category));
    }

    public function destroy(string $id): JsonResponse
    {
        $category = $this->queryBus->ask(new GetCategoryQuery($id));
        $command = new DeleteCategoryCommand($category);
        $this->commandBus->dispatch($command);

        return response()->json(['message' => 'Category deleted']);
    }
}
