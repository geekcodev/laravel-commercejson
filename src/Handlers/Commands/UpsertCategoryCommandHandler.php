<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertCategoryCommand;
use GeekCo\CommerceJson\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;

class UpsertCategoryCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertCategoryCommand);

        return DB::transaction(function () use ($command) {
            $data = $command->categoryData->toArray();

            if ($data['parent_id'] === $data['id']) {
                $data['parent_id'] = null;
            }

            return $this->categoryRepository->updateOrCreate(
                ['id' => $command->categoryData->id],
                $data
            );
        });
    }
}
