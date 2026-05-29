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
            $data = $command->orderData->toArray();

            // Don't overwrite readOnly or creation-only fields on existing orders
            if ($this->repository->find($command->orderData->id)) {
                unset($data['number'], $data['document_type']);
            }

            $order = $this->repository->updateOrCreate(
                ['id' => $command->orderData->id],
                $data
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
