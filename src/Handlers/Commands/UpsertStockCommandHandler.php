<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertStockCommand;
use GeekCo\CommerceJson\Repositories\StockRepository;
use Illuminate\Support\Facades\DB;

class UpsertStockCommandHandler implements CommandHandlerInterface
{
    private StockRepository $stockRepository;

    public function __construct(StockRepository $repository)
    {
        $this->stockRepository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertStockCommand);

        return DB::transaction(function () use ($command) {
            return $this->stockRepository->updateOrCreate(
                [
                    'offer_id' => $command->offerId,
                    'warehouse_id' => $command->stockData->warehouse_id,
                ],
                [
                    'quantity' => $command->stockData->quantity,
                    'quantity_reserved' => $command->stockData->quantity_reserved,
                ]
            );
        });
    }
}
