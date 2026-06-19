<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Handlers\Queries;

use GeekCo\CommerceJson\Queries\GetOffersQuery;
use GeekCo\CommerceJson\Queries\QueryInterface;
use GeekCo\CommerceJson\Repositories\OfferRepository;

readonly class GetOffersQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private OfferRepository $offerRepository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetOffersQuery);

        $qb = $this->offerRepository->newQuery();

        if ($query->price_type_id !== null) {
            $qb->whereHas('prices', fn ($q) => $q->where('price_type_id', $query->price_type_id));
        }

        if ($query->warehouse_id !== null) {
            $qb->whereHas('stocks', fn ($q) => $q->where('warehouse_id', $query->warehouse_id));
        }

        if ($query->updated_after !== null) {
            $qb->where('updated_at', '>', $query->updated_after);
        }

        if ($query->include_deleted) {
            $qb->withTrashed();
        }

        return $qb->with(['prices', 'stocks'])->paginate($query->perPage);
    }
}
