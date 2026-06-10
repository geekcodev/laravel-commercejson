<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Data\MoneyData;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderTotalsData;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrderCommandHandler implements CommandHandlerInterface
{
    private OrderRepository $orderRepository;

    public function __construct(OrderRepository $repository)
    {
        $this->orderRepository = $repository;
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof CreateOrderCommand);

        return DB::transaction(function () use ($command) {
            $createData = $command->createData;

            $currency = $createData->base_currency
                ?? CurrencyEnum::tryFrom(config('commercejson.default_currency'))
                ?? CurrencyEnum::RUB;

            $items = array_map(fn ($item) => [
                'id' => (string) Str::uuid(),
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'price' => MoneyData::from(['amount' => '0', 'currency' => $currency->value])->toArray(),
                'total' => MoneyData::from(['amount' => '0', 'currency' => $currency->value])->toArray(),
            ], $createData->items);

            $zeroMoney = ['amount' => '0', 'currency' => $currency->value];

            $data = array_merge($createData->toArray(), [
                'id' => (string) Str::uuid(),
                'number' => 'ORD-'.date('Ymd').'-'.Str::upper(Str::random(6)),
                'status' => OrderStatusEnum::New,
                'items' => $items,
                'totals' => OrderTotalsData::from([
                    'subtotal' => MoneyData::from($zeroMoney),
                    'total' => MoneyData::from($zeroMoney),
                    'discount' => null,
                    'delivery' => null,
                    'tax' => null,
                ])->toArray(),
            ]);

            return $this->orderRepository->create(OrderData::from($data)->toArray());
        });
    }
}
