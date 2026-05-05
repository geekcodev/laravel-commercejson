<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\CreateCounterpartyCommand;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;
use Illuminate\Support\Facades\DB;

class CreateCounterpartyCommandHandler implements CommandHandlerInterface
{
    private CounterpartyRepository $repository;

    public function __construct(CounterpartyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof CreateCounterpartyCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->create($command->counterpartyData->toArray());
        });
    }
}
