<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpdateOrderCommand;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

class UpdateOrderCommandHandler implements CommandHandlerInterface
{
    private OrderRepository $repository;

    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpdateOrderCommand);

        return DB::transaction(function () use ($command) {
            $order = $this->repository->findOrFail($command->id);

            return $this->repository->update($order, $command->orderData->toArray());
        });
    }
}
