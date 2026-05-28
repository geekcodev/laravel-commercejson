<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertStockCommand;
use GeekCo\CommerceJson\Models\Stock;
use Illuminate\Support\Facades\DB;

class UpsertStockCommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertStockCommand);

        return DB::transaction(function () use ($command) {
            return Stock::updateOrCreate(
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
