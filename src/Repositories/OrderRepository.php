<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Repositories;

use GeekCo\CommerceJson\Data\LinkedDocumentData;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\OrderItem;
use GeekCo\CommerceJson\Models\OrderItemTax;
use Illuminate\Support\Collection;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function findByExternalId(string $externalId): ?Order
    {
        /** @var Order|null $order */
        $order = $this->model->where('external_id', $externalId)->first();

        return $order;
    }

    /**
     * @param  array<int, LinkedDocumentData>  $linkedDocuments
     */
    public function syncLinkedDocuments(Order $order, array $linkedDocuments): void
    {
        $sync = [];
        $skippedSelf = false;

        foreach ($linkedDocuments as $doc) {
            if ($doc->id === $order->id) {
                $skippedSelf = true;

                continue;
            }

            $sync[$doc->id] = [
                'type' => $doc->type->value,
                'external_id' => $doc->external_id,
            ];
        }

        // All items were self-references — preserve existing links
        if ($skippedSelf && $sync === []) {
            return;
        }

        $order->linkedDocuments()->sync($sync);
    }

    public function updateOrCreateItem(Order $order, array $data): OrderItem
    {
        /** @var OrderItem $item */
        $item = $order->items()->updateOrCreate(
            ['id' => $data['id']],
            $data
        );

        return $item;
    }

    public function updateOrCreateItemTax(OrderItem $orderItem, array $data): OrderItemTax
    {
        /** @var OrderItemTax $tax */
        $tax = $orderItem->taxes()->updateOrCreate(
            ['order_item_id' => $orderItem->id, 'type' => $data['type']],
            $data
        );

        return $tax;
    }
}
