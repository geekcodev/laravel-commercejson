<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\DeleteOrderCommand;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

class DeleteOrderCommandHandler implements CommandHandlerInterface
{
    private OrderRepository $repository;

    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof DeleteOrderCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->delete($command->order);
        });
    }
}
