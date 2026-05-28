<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;

class UpsertOrderCommandHandler implements CommandHandlerInterface
{
    private OrderRepository $repository;

    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertOrderCommand);

        return DB::transaction(function () use ($command) {
            $order = $this->repository->updateOrCreate(
                ['id' => $command->orderData->id],
                $command->orderData->toArray()
            );

            if ($command->deliveryTrack) {
                $updates = array_filter([
                    'delivery_tracking_number' => $command->deliveryTrack->tracking_number,
                    'delivery_shipped_at' => $command->deliveryTrack->shipped_at,
                    'delivery_estimated_date' => $command->deliveryTrack->estimated_date,
                ], fn ($v) => $v !== null);

                if (! empty($updates)) {
                    $order->update($updates);
                    $order = $order->fresh();
                }
            }

            return $order;
        });
    }
}
