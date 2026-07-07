<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Enums\CurrencyEnum;
use GeekCo\CommerceJson\Enums\OrderStatusEnum;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof CreateOrderCommand);

        return DB::transaction(function () use ($command) {
            $createData = $command->createData;

            $currency = $createData->base_currency
                ?? CurrencyEnum::tryFrom(config('commercejson.default_currency'))
                ?? CurrencyEnum::RUB;

            $orderId = (string) Str::uuid();

            $data = [
                'id' => $orderId,
                'number' => 'ORD-'.date('Ymd').'-'.Str::upper(Str::random(6)),
                'status' => OrderStatusEnum::New,
                'document_type' => $createData->document_type,
                'role' => $createData->role,
                'counterparty_id' => $createData->counterparty_id,
                'warehouse_id' => $createData->warehouse_id,
                'base_currency' => $currency->value,
                'exchange_rate' => $createData->exchange_rate,
                'payment_terms' => $createData->payment_terms,
                'comment' => $createData->comment,
            ];

            if ($createData->customer) {
                $data['customer_name'] = $createData->customer->name;
                $data['customer_phone'] = $createData->customer->phone;
                $data['customer_email'] = $createData->customer->email;
                $data['customer_counterparty_id'] = $createData->customer->counterparty_id;
            }

            if ($createData->delivery) {
                $d = $createData->delivery;
                $data['delivery_type'] = $d->type;
                $data['delivery_method_id'] = $d->method_id;
                $data['delivery_method_name'] = $d->method_name;
                $data['delivery_cost_amount'] = $d->cost?->amount;
                $data['delivery_cost_currency'] = $d->cost?->currency;
                $data['delivery_tracking_number'] = $d->tracking_number;
                $data['delivery_shipped_at'] = $d->shipped_at;
                $data['delivery_estimated_date'] = $d->estimated_date;
                if ($d->address) {
                    $a = $d->address;
                    $data['delivery_address_country'] = $a->country;
                    $data['delivery_address_region'] = $a->region;
                    $data['delivery_address_district'] = $a->district;
                    $data['delivery_address_city'] = $a->city;
                    $data['delivery_address_street'] = $a->street;
                    $data['delivery_address_house'] = $a->house;
                    $data['delivery_address_building'] = $a->building;
                    $data['delivery_address_apartment'] = $a->apartment;
                    $data['delivery_address_postal_code'] = $a->postal_code;
                    $data['delivery_address_full'] = $a->full;
                }
            }

            if ($createData->payment) {
                $p = $createData->payment;
                $data['payment_type'] = $p->type;
                $data['payment_status'] = $p->status;
                $data['payment_amount'] = $p->amount?->amount;
                $data['payment_currency'] = $p->amount?->currency;
                $data['payment_paid_at'] = $p->paid_at;
            }

            $order = $this->orderRepository->create($data);

            if ($createData->custom_attributes) {
                foreach ($createData->custom_attributes as $attr) {
                    $value = $attr->value;
                    $row = ['key' => $attr->key];
                    if (is_string($value)) {
                        $row['value_string'] = $value;
                    } elseif (is_int($value) || is_float($value)) {
                        $row['value_number'] = $value;
                    } elseif (is_bool($value)) {
                        $row['value_boolean'] = $value;
                    } elseif (is_array($value)) {
                        $row['value_json'] = $value;
                    } else {
                        $row['value_string'] = (string) $value;
                    }
                    $order->customAttributes()->create($row);
                }
            }

            if ($createData->signatories) {
                foreach ($createData->signatories as $signatory) {
                    $order->signatories()->create([
                        'first_name' => $signatory->first_name,
                        'last_name' => $signatory->last_name,
                        'middle_name' => $signatory->middle_name,
                        'position' => $signatory->position,
                        'basis' => $signatory->basis,
                    ]);
                }
            }

            if ($createData->linked_documents) {
                assert($order instanceof Order);
                $this->orderRepository->syncLinkedDocuments($order, $createData->linked_documents);
            }

            $productIds = array_unique(array_map(fn ($i) => $i->product_id, $createData->items));
            $products = $this->productRepository->newQuery()
                ->whereIn('id', $productIds)
                ->with(['offers.prices'])
                ->get()
                ->keyBy('id');

            $totalSum = 0;

            foreach ($createData->items as $item) {
                /** @var Product|null $product */
                $product = $products->get($item->product_id);

                $priceAmount = '0';
                $firstOffer = $product?->offers->first();
                $firstPrice = $firstOffer?->prices->first();
                if ($firstPrice !== null) {
                    $priceAmount = $firstPrice->price_amount;
                }

                $quantity = (float) $item->quantity;
                $lineTotal = number_format((float) $priceAmount * $quantity, 2, '.', '');
                $totalSum += (float) $lineTotal;

                $order->items()->create([
                    'id' => (string) Str::uuid(),
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'warehouse_id' => $item->warehouse_id,
                    'quantity' => $item->quantity,
                    'product_name' => $product?->name,
                    'product_code' => $product?->code,
                    'price_amount' => $priceAmount,
                    'price_currency' => $currency->value,
                    'total_amount' => $lineTotal,
                    'total_currency' => $currency->value,
                ]);
            }

            $order->update([
                'totals_subtotal_amount' => number_format($totalSum, 2, '.', ''),
                'totals_subtotal_currency' => $currency->value,
                'totals_total_amount' => number_format($totalSum, 2, '.', ''),
                'totals_total_currency' => $currency->value,
            ]);

            return $order->load(['items', 'linkedDocuments']);
        });
    }
}
