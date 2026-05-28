<?php

use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Tests\TestCase;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

function mockCommandBus(): Dispatcher&MockInterface
{
    $mock = Mockery::mock(Dispatcher::class);
    app()->instance(Dispatcher::class, $mock);

    return $mock;
}

function mockQueryBus(): QueryBusInterface&MockInterface
{
    $mock = Mockery::mock(QueryBusInterface::class);
    app()->instance(QueryBusInterface::class, $mock);

    return $mock;
}
