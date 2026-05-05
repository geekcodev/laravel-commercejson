<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\CreateOfferCommand;
use GeekCo\CommerceJson\Repositories\OfferRepository;
use Illuminate\Support\Facades\DB;

class CreateOfferCommandHandler implements CommandHandlerInterface
{
    private OfferRepository $repository;

    public function __construct(OfferRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof CreateOfferCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->create($command->offerData->toArray());
        });
    }
}
