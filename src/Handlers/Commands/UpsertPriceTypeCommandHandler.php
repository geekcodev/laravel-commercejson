<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertPriceTypeCommand;
use GeekCo\CommerceJson\Repositories\PriceTypeRepository;
use Illuminate\Support\Facades\DB;

class UpsertPriceTypeCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PriceTypeRepository $priceTypeRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertPriceTypeCommand);

        return DB::transaction(function () use ($command) {
            return $this->priceTypeRepository->updateOrCreate(
                ['id' => $command->priceTypeData->id],
                $command->priceTypeData->toArray()
            );
        });
    }
}
