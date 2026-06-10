<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Queries\GetProductQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\ProductRepository;

class GetProductQueryHandler implements QueryHandlerInterface
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetProductQuery);

        /** @var Product $product */
        $product = $this->repository->findOrFail($query->id)
            ->load(['images', 'propertyValues', 'variants.propertyValues', 'customAttributes', 'analogues', 'components']);

        // Transform analogues from Collection<Product> to string[] (UUIDs per spec)
        $product->setRelation('analogues', $product->analogues->pluck('id')->values()->toArray());

        // Transform components from Collection<Product> to array of {product_id, quantity} per spec
        $components = [];
        foreach ($product->components as $component) {
            $components[] = [
                'product_id' => $component->id,
                'quantity' => (float) $component->pivot->getAttribute('quantity'),
            ];
        }
        $product->setRelation('components', $components);

        return $product;
    }
}
