<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Queries\GetProductsQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GetProductsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetProductsQuery);

        $qb = $this->productRepository->newQuery();

        if ($query->category_id !== null) {
            $qb->where('category_id', $query->category_id);
        }

        if ($query->is_active !== null) {
            $qb->where('is_active', $query->is_active);
        }

        if ($query->updated_after !== null) {
            $qb->where('updated_at', '>', $query->updated_after);
        }

        if ($query->include_deleted) {
            $qb->withTrashed();
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $qb->with([
            'images', 'propertyValues', 'variants.propertyValues',
            'customAttributes', 'analogues', 'components',
        ])->paginate($query->perPage);

        /** @var Collection<int, Product> $collection */
        $collection = $paginator->getCollection();
        $collection->transform(function (Product $product) {
            $product->setRelationForApi();

            return $product;
        });

        return $paginator;
    }
}
