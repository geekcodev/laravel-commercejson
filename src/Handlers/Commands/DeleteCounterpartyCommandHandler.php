<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\DeleteCounterpartyCommand;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;
use Illuminate\Support\Facades\DB;

class DeleteCounterpartyCommandHandler implements CommandHandlerInterface
{
    private CounterpartyRepository $repository;

    public function __construct(CounterpartyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof DeleteCounterpartyCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->delete($command->counterparty);
        });
    }
}
