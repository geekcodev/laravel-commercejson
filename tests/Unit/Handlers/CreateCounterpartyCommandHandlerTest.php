<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Tests\Unit\Handlers;

use GeekCo\CommerceJson\Commands\CreateCounterpartyCommand;
use GeekCo\CommerceJson\Data\CounterpartyData;
use GeekCo\CommerceJson\Handlers\Commands\CreateCounterpartyCommandHandler;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;
use GeekCo\CommerceJson\Tests\TestCase;
use Mockery;

class CreateCounterpartyCommandHandlerTest extends TestCase
{
    public function test_creates_counterparty_via_repository(): void
    {
        $model = Counterparty::factory()->make();

        $data = CounterpartyData::from($model);

        $repository = Mockery::mock(CounterpartyRepository::class);
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($model);

        $handler = new CreateCounterpartyCommandHandler($repository);
        $result = $handler->handle(new CreateCounterpartyCommand($data));

        $this->assertSame($model, $result);
    }
}
