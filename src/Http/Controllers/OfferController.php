<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Http\Controllers;

use GeekCo\CommerceJson\Bus\CommandBusInterface;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\CreateOfferCommand;
use GeekCo\CommerceJson\Commands\DeleteOfferCommand;
use GeekCo\CommerceJson\Commands\UpdateOfferCommand;
use GeekCo\CommerceJson\Data\OfferData;
use GeekCo\CommerceJson\Queries\GetOfferQuery;
use GeekCo\CommerceJson\Queries\GetOffersQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;

class OfferController extends Controller
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = new GetOffersQuery(
            perPage: (int) ($request->get('per_page', 15))
        );
        $offers = $this->queryBus->ask($query);

        return response()->json([
            'data' => OfferData::collect($offers->items(), DataCollection::class),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $query = new GetOfferQuery($id);
        $offer = $this->queryBus->ask($query);

        return response()->json(OfferData::from($offer));
    }

    public function store(Request $request): JsonResponse
    {
        $command = new CreateOfferCommand(OfferData::from($request->all()));
        $offer = $this->commandBus->dispatch($command);

        return response()->json(OfferData::from($offer), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $offer = $this->queryBus->ask(new GetOfferQuery($id));
        $command = new UpdateOfferCommand($offer, OfferData::from($request->all()));
        $offer = $this->commandBus->dispatch($command);

        return response()->json(OfferData::from($offer));
    }

    public function destroy(string $id): JsonResponse
    {
        $offer = $this->queryBus->ask(new GetOfferQuery($id));
        $command = new DeleteOfferCommand($offer);
        $this->commandBus->dispatch($command);

        return response()->json(['message' => 'Offer deleted']);
    }
}
