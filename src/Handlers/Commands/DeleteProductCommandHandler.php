<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\DeleteProductCommand;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class DeleteProductCommandHandler implements CommandHandlerInterface
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof DeleteProductCommand);

        return DB::transaction(function () use ($command) {
            $product = $this->repository->findOrFail($command->id);

            $this->repository->update($product, ['is_active' => false]);

            $this->repository->delete($product);

            return $product->fresh();
        });
    }
}
