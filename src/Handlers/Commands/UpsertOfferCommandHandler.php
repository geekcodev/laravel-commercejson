<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertOfferCommand;
use GeekCo\CommerceJson\Repositories\OfferRepository;
use Illuminate\Support\Facades\DB;

class UpsertOfferCommandHandler implements CommandHandlerInterface
{
    private OfferRepository $repository;

    public function __construct(OfferRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertOfferCommand);

        return DB::transaction(function () use ($command) {
            return $this->repository->updateOrCreate(
                [
                    'product_id' => $command->offerData->productId,
                    'variant_id' => $command->offerData->variantId,
                ],
                $command->offerData->toArray()
            );
        });
    }
}
