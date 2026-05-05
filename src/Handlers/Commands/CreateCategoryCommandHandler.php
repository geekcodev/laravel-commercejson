<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\CreateCategoryCommand;
use GeekCo\CommerceJson\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;

class CreateCategoryCommandHandler implements CommandHandlerInterface
{
    private CategoryRepository $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof CreateCategoryCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->create($command->categoryData->toArray());
        });
    }
}
