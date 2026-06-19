<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\DeleteProductCommand;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class DeleteProductCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof DeleteProductCommand);

        return DB::transaction(function () use ($command) {
            $product = $this->productRepository->findOrFail($command->id);

            $this->productRepository->update($product, ['is_active' => false]);

            $this->productRepository->delete($product);

            return $product->fresh();
        });
    }
}
