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
            return $this->repository->delete($command->product);
        });
    }
}
