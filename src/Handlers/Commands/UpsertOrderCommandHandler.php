<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

class UpsertOrderCommandHandler implements CommandHandlerInterface
{
    private OrderRepository $repository;

    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertOrderCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->updateOrCreate(
                ['id' => $command->orderData->id],
                $command->orderData->toArray()
            );
        });
    }
}
