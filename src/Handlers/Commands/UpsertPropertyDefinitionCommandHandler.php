<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertPropertyDefinitionCommand;
use GeekCo\CommerceJson\Repositories\PropertyDefinitionRepository;
use Illuminate\Support\Facades\DB;

class UpsertPropertyDefinitionCommandHandler implements CommandHandlerInterface
{
    private PropertyDefinitionRepository $repository;

    public function __construct(PropertyDefinitionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertPropertyDefinitionCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->updateOrCreate(
                ['id' => $command->propertyDefinitionData->id],
                $command->propertyDefinitionData->toArray()
            );
        });
    }
}
