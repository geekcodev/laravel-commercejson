<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Commands;

use GeekCo\CommerceJson\Commands\CommandInterface;
use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Data\OrderData;
use GeekCo\CommerceJson\Data\OrderItemData;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class UpsertOrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof UpsertOrderCommand);

        return DB::transaction(function () use ($command) {
            $data = $command->orderData;

            $flat = $this->buildFlatArray($data);

            $existing = $this->orderRepository->find($data->id);

            if ($existing) {
                unset($flat['number'], $flat['document_type']);
                $order = $this->orderRepository->update($existing, $flat);
            } else {
                $order = $this->orderRepository->create($flat);
            }

            assert($order instanceof Order);

            if ($data->items) {
                $this->syncItems($order, $data->items);
            }

            if ($data->linked_documents !== null) {
                $this->orderRepository->syncLinkedDocuments($order, $data->linked_documents);
            }

            if ($data->custom_attributes !== null) {
                $order->customAttributes()->delete();
                foreach ($data->custom_attributes as $attr) {
                    $value = $attr->value;
                    $row = ['key' => $attr->key];
                    if (is_string($value) || $value === null) {
                        $row['value_string'] = $value;
                    } elseif (is_int($value) || is_float($value)) {
                        $row['value_number'] = $value;
                    } elseif (is_bool($value)) {
                        $row['value_boolean'] = $value;
                    } else {
                        $row['value_json'] = $value;
                    }
                    $order->customAttributes()->create($row);
                }
            }

            if ($data->signatories !== null) {
                $order->signatories()->delete();
                foreach ($data->signatories as $signatory) {
                    $order->signatories()->create([
                        'first_name' => $signatory->first_name,
                        'last_name' => $signatory->last_name,
                        'middle_name' => $signatory->middle_name,
                        'position' => $signatory->position,
                        'basis' => $signatory->basis,
                    ]);
                }
            }

            if ($command->deliveryTrack) {
                $updates = array_filter([
                    'delivery_tracking_number' => $command->deliveryTrack->tracking_number,
                    'delivery_shipped_at' => $command->deliveryTrack->shipped_at,
                    'delivery_estimated_date' => $command->deliveryTrack->estimated_date,
                ], fn ($v) => $v !== null);

                if (! empty($updates)) {
                    $order->update($updates);
                }
            }

            return $order->fresh(['items', 'linkedDocuments']);
        });
    }

    private function buildFlatArray(OrderData $data): array
    {
        $flat = [
            'id' => $data->id,
            'status' => $data->status,
            'number' => $data->number,
            'external_id' => $data->external_id,
            'document_type' => $data->document_type,
            'role' => $data->role,
            'base_currency' => $data->base_currency,
            'exchange_rate' => $data->exchange_rate,
            'payment_terms' => $data->payment_terms,
            'counterparty_id' => $data->counterparty_id,
            'warehouse_id' => $data->warehouse_id,
            'comment' => $data->comment,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
            'deleted_at' => $data->deleted_at,
        ];

        if ($data->customer) {
            $c = $data->customer;
            $flat['customer_name'] = $c->name;
            $flat['customer_phone'] = $c->phone;
            $flat['customer_email'] = $c->email;
            $flat['customer_counterparty_id'] = $c->counterparty_id;
        }

        if ($data->delivery) {
            $d = $data->delivery;
            $flat['delivery_type'] = $d->type->value;
            $flat['delivery_method_id'] = $d->method_id;
            $flat['delivery_method_name'] = $d->method_name;
            $flat['delivery_cost_amount'] = $d->cost?->amount;
            $flat['delivery_cost_currency'] = $d->cost?->currency;
            $flat['delivery_tracking_number'] = $d->tracking_number;
            $flat['delivery_shipped_at'] = $d->shipped_at;
            $flat['delivery_estimated_date'] = $d->estimated_date;
            if ($d->address) {
                $a = $d->address;
                $flat['delivery_address_country'] = $a->country;
                $flat['delivery_address_region'] = $a->region;
                $flat['delivery_address_district'] = $a->district;
                $flat['delivery_address_city'] = $a->city;
                $flat['delivery_address_street'] = $a->street;
                $flat['delivery_address_house'] = $a->house;
                $flat['delivery_address_building'] = $a->building;
                $flat['delivery_address_apartment'] = $a->apartment;
                $flat['delivery_address_postal_code'] = $a->postal_code;
                $flat['delivery_address_full'] = $a->full;
            }
        }

        if ($data->payment) {
            $p = $data->payment;
            $flat['payment_type'] = $p->type->value;
            $flat['payment_status'] = $p->status?->value;
            $flat['payment_amount'] = $p->amount?->amount;
            $flat['payment_currency'] = $p->amount?->currency;
            $flat['payment_paid_at'] = $p->paid_at;
        }

        $t = $data->totals;
        $flat['totals_subtotal_amount'] = $t->subtotal->amount;
        $flat['totals_subtotal_currency'] = $t->subtotal->currency;
        $flat['totals_total_amount'] = $t->total->amount;
        $flat['totals_total_currency'] = $t->total->currency;
        if ($t->discount) {
            $flat['totals_discount_amount'] = $t->discount->amount;
            $flat['totals_discount_currency'] = $t->discount->currency;
        }
        if ($t->delivery) {
            $flat['totals_delivery_amount'] = $t->delivery->amount;
            $flat['totals_delivery_currency'] = $t->delivery->currency;
        }
        if ($t->tax) {
            $flat['totals_tax_amount'] = $t->tax->amount;
            $flat['totals_tax_currency'] = $t->tax->currency;
        }

        return $flat;
    }

    private function syncItems(Order $order, array $items): void
    {
        $order->items()->delete();

        $productIds = array_map(fn (OrderItemData $i) => $i->product_id, $items);
        $products = $this->productRepository->findMany($productIds)->keyBy('id');

        foreach ($items as $item) {
            assert($item instanceof OrderItemData);

            $product = $products->get($item->product_id);
            $defaultProductName = $product?->name;
            $defaultProductCode = $product?->code;

            $row = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'product_name' => $item->product_name ?? $defaultProductName ?? 'Unknown Product',
                'product_code' => $item->product_code ?? $defaultProductCode,
                'price_amount' => $item->price->amount,
                'price_currency' => $item->price->currency,
                'total_amount' => $item->total->amount,
                'total_currency' => $item->total->currency,
                'country_of_origin' => $item->country_of_origin,
                'customs_declaration_number' => $item->customs_declaration_number,
                'tax_rate' => $item->tax_rate,
            ];

            if ($item->unit) {
                $row['unit_code'] = $item->unit->code;
                $row['unit_short_name'] = $item->unit->short_name;
                $row['unit_full_name'] = $item->unit->full_name;
                $row['unit_international'] = $item->unit->international;
            }

            if ($item->discount) {
                $row['discount_amount'] = $item->discount->amount;
                $row['discount_currency'] = $item->discount->currency;
            }

            $order->items()->create($row);
        }
    }
}
