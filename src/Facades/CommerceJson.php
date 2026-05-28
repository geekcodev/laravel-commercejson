<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Facades;

use GeekCo\CommerceJson\CommerceJsonServiceProvider;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Services\ClassifierService;
use GeekCo\CommerceJson\Services\CounterpartyService;
use GeekCo\CommerceJson\Services\OfferService;
use GeekCo\CommerceJson\Services\OrderService;
use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Services\WarehouseService;
use Illuminate\Support\Facades\Facade;

/**
 * Facade для CommerceJSON
 *
 * @see CommerceJsonServiceProvider
 */
class CommerceJson extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'commercejson';
    }

    /**
     * Получить HTTP клиент
     */
    public static function http(): HttpClientInterface
    {
        return app(HttpClientInterface::class);
    }

    /**
     * Получить сервис товаров
     */
    public static function products(): ProductService
    {
        return app(ProductService::class);
    }

    /**
     * Получить сервис заказов
     */
    public static function orders(): OrderService
    {
        return app(OrderService::class);
    }

    /**
     * Получить сервис предложений
     */
    public static function offers(): OfferService
    {
        return app(OfferService::class);
    }

    /**
     * Получить сервис классификатора
     */
    public static function classifier(): ClassifierService
    {
        return app(ClassifierService::class);
    }

    /**
     * Получить сервис складов
     */
    public static function warehouses(): WarehouseService
    {
        return app(WarehouseService::class);
    }

    /**
     * Получить сервис контрагентов
     */
    public static function counterparties(): CounterpartyService
    {
        return app(CounterpartyService::class);
    }
}
