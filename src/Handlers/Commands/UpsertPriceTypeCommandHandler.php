<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertPriceTypeCommand;
use GeekCo\CommerceJson\Repositories\PriceTypeRepository;
use Illuminate\Support\Facades\DB;

class UpsertPriceTypeCommandHandler implements CommandHandlerInterface
{
    private PriceTypeRepository $repository;

    public function __construct(PriceTypeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertPriceTypeCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->updateOrCreate(
                ['id' => $command->priceTypeData->id],
                $command->priceTypeData->toArray()
            );
        });
    }
}
