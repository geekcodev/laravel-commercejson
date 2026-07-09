<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertWarehouseCommand;
use GeekCo\CommerceJson\Repositories\WarehouseRepository;
use Illuminate\Support\Facades\DB;

class UpsertWarehouseCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly WarehouseRepository $warehouseRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertWarehouseCommand);

        return DB::transaction(function () use ($command) {
            return $this->warehouseRepository->updateOrCreate(
                ['id' => $command->warehouseData->id],
                $command->warehouseData->toArray()
            );
        });
    }
}
