<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson;

use GeekCo\CommerceJson\Bus\QueryBus;
use GeekCo\CommerceJson\Bus\QueryBusInterface;
use GeekCo\CommerceJson\Commands\BulkUpsertOrderCommand;
use GeekCo\CommerceJson\Commands\CreateCategoryCommand;
use GeekCo\CommerceJson\Commands\CreateCounterpartyCommand;
use GeekCo\CommerceJson\Commands\CreateOfferCommand;
use GeekCo\CommerceJson\Commands\CreateOrderCommand;
use GeekCo\CommerceJson\Commands\CreateProductCommand;
use GeekCo\CommerceJson\Commands\DeleteCategoryCommand;
use GeekCo\CommerceJson\Commands\DeleteCounterpartyCommand;
use GeekCo\CommerceJson\Commands\DeleteOfferCommand;
use GeekCo\CommerceJson\Commands\DeleteOrderCommand;
use GeekCo\CommerceJson\Commands\DeleteProductCommand;
use GeekCo\CommerceJson\Commands\UpdateCategoryCommand;
use GeekCo\CommerceJson\Commands\UpdateCounterpartyCommand;
use GeekCo\CommerceJson\Commands\UpdateOfferCommand;
use GeekCo\CommerceJson\Commands\UpdateOrderCommand;
use GeekCo\CommerceJson\Commands\UpdateProductCommand;
use GeekCo\CommerceJson\Commands\UpsertCategoryCommand;
use GeekCo\CommerceJson\Commands\UpsertCounterpartyCommand;
use GeekCo\CommerceJson\Commands\UpsertOfferCommand;
use GeekCo\CommerceJson\Commands\UpsertOfferPriceCommand;
use GeekCo\CommerceJson\Commands\UpsertOrderCommand;
use GeekCo\CommerceJson\Commands\UpsertPriceTypeCommand;
use GeekCo\CommerceJson\Commands\UpsertProductCommand;
use GeekCo\CommerceJson\Commands\UpsertPropertyDefinitionCommand;
use GeekCo\CommerceJson\Commands\UpsertStockCommand;
use GeekCo\CommerceJson\Commands\UpsertWarehouseCommand;
use GeekCo\CommerceJson\Console\Commands\ExportOrdersCommand;
use GeekCo\CommerceJson\Console\Commands\HandshakeCommand;
use GeekCo\CommerceJson\Console\Commands\ImportClassifierCommand;
use GeekCo\CommerceJson\Console\Commands\ImportOffersCommand;
use GeekCo\CommerceJson\Console\Commands\ImportOrdersCommand;
use GeekCo\CommerceJson\Console\Commands\ImportProductsCommand;
use GeekCo\CommerceJson\Console\Commands\SyncCommand;
use GeekCo\CommerceJson\Exchange\ExchangeManager;
use GeekCo\CommerceJson\Exchange\Export\OrderExporter;
use GeekCo\CommerceJson\Exchange\Import\ClassifierImporter;
use GeekCo\CommerceJson\Exchange\Import\OrderImporter;
use GeekCo\CommerceJson\Exchange\Import\ProductImporter;
use GeekCo\CommerceJson\Facades\CommerceJson;
use GeekCo\CommerceJson\Handlers\Commands\BulkUpsertOrderCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\CreateCategoryCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\CreateCounterpartyCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\CreateOfferCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\CreateOrderCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\CreateProductCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\DeleteCategoryCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\DeleteCounterpartyCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\DeleteOfferCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\DeleteOrderCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\DeleteProductCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpdateCategoryCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpdateCounterpartyCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpdateOfferCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpdateOrderCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpdateProductCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertCategoryCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertCounterpartyCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertOfferCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertOfferPriceCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertOrderCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertPriceTypeCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertProductCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertPropertyDefinitionCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertStockCommandHandler;
use GeekCo\CommerceJson\Handlers\Commands\UpsertWarehouseCommandHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetCategoriesQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetCategoryQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetCounterpartiesQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetCounterpartyQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetOfferQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetOffersQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetOrderQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetOrdersQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetPriceTypesQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetProductQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetProductsQueryHandler;
use GeekCo\CommerceJson\Handlers\Queries\GetWarehousesQueryHandler;
use GeekCo\CommerceJson\Http\Client\CommerceJsonHttpClient;
use GeekCo\CommerceJson\Http\Client\ExponentialBackoffStrategy;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use GeekCo\CommerceJson\Models\Category;
use GeekCo\CommerceJson\Models\Counterparty;
use GeekCo\CommerceJson\Models\Offer;
use GeekCo\CommerceJson\Models\Order;
use GeekCo\CommerceJson\Models\PriceType;
use GeekCo\CommerceJson\Models\Product;
use GeekCo\CommerceJson\Models\PropertyDefinition;
use GeekCo\CommerceJson\Models\Warehouse;
use GeekCo\CommerceJson\Queries\GetCategoriesQuery;
use GeekCo\CommerceJson\Queries\GetCategoryQuery;
use GeekCo\CommerceJson\Queries\GetCounterpartiesQuery;
use GeekCo\CommerceJson\Queries\GetCounterpartyQuery;
use GeekCo\CommerceJson\Queries\GetOfferQuery;
use GeekCo\CommerceJson\Queries\GetOffersQuery;
use GeekCo\CommerceJson\Queries\GetOrderQuery;
use GeekCo\CommerceJson\Queries\GetOrdersQuery;
use GeekCo\CommerceJson\Queries\GetPriceTypesQuery;
use GeekCo\CommerceJson\Queries\GetProductQuery;
use GeekCo\CommerceJson\Queries\GetProductsQuery;
use GeekCo\CommerceJson\Queries\GetWarehousesQuery;
use GeekCo\CommerceJson\Repositories\CategoryRepository;
use GeekCo\CommerceJson\Repositories\CounterpartyRepository;
use GeekCo\CommerceJson\Repositories\OfferRepository;
use GeekCo\CommerceJson\Repositories\OrderRepository;
use GeekCo\CommerceJson\Repositories\PriceTypeRepository;
use GeekCo\CommerceJson\Repositories\ProductRepository;
use GeekCo\CommerceJson\Repositories\PropertyDefinitionRepository;
use GeekCo\CommerceJson\Repositories\WarehouseRepository;
use GeekCo\CommerceJson\Services\ClassifierService;
use GeekCo\CommerceJson\Services\CounterpartyService;
use GeekCo\CommerceJson\Services\OfferService;
use GeekCo\CommerceJson\Services\OrderService;
use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Services\WarehouseService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\ServiceProvider;

class CommerceJsonServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/commercejson.php',
            'commercejson'
        );

        // Принимаем даты в различных форматах (миллисекунды + Z, ISO 8601, MySQL)
        config()->set('data.date_format', [
            'Y-m-d H:i:s',
            DATE_ATOM,
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:s.u\Z',
            'Y-m-d\TH:i:s.uP',
            'Y-m-d\TH:i:s.v\Z',
            'Y-m-d\TH:i:s.vP',
            'Y-m-d',
        ]);

        // Регистрация HTTP клиента
        $this->app->singleton(HttpClientInterface::class, function ($app) {
            $logger = config('commercejson.logging.enabled')
                ? $app->make('log')->channel(config('commercejson.logging.channel'))
                : null;

            $retryStrategy = new ExponentialBackoffStrategy(
                maxAttempts: config('commercejson.retry_attempts', 3),
                baseDelayMs: 2000,
                maxDelayMs: 30000
            );

            return new CommerceJsonHttpClient(
                baseUrl: config('commercejson.base_url'),
                authToken: config('commercejson.auth.token'),
                timeout: config('commercejson.timeout', 30),
                authType: config('commercejson.auth.type', 'bearer'),
                logger: $logger,
                retryStrategy: $retryStrategy
            );
        });

        // Регистрация сервисов
        $this->app->singleton(ProductService::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(OfferService::class);
        $this->app->singleton(ClassifierService::class);
        $this->app->singleton(WarehouseService::class);
        $this->app->singleton(CounterpartyService::class);

        // Регистрация Exchange компонентов
        $this->app->singleton(ProductImporter::class);
        $this->app->singleton(OrderImporter::class);
        $this->app->singleton(ClassifierImporter::class);
        $this->app->singleton(OrderExporter::class);

        // Регистрация Exchange Manager
        $this->app->singleton(ExchangeManager::class);

        // Регистрация фасада
        $this->app->alias('commercejson', CommerceJson::class);

        // Регистрация репозиториев
        $this->app->singleton(ProductRepository::class, function ($app) {
            return new ProductRepository($app->make(Product::class));
        });
        $this->app->singleton(OrderRepository::class, function ($app) {
            return new OrderRepository($app->make(Order::class));
        });
        $this->app->singleton(CounterpartyRepository::class, function ($app) {
            return new CounterpartyRepository($app->make(Counterparty::class));
        });
        $this->app->singleton(CategoryRepository::class, function ($app) {
            return new CategoryRepository($app->make(Category::class));
        });
        $this->app->singleton(OfferRepository::class, function ($app) {
            return new OfferRepository($app->make(Offer::class));
        });
        $this->app->singleton(PropertyDefinitionRepository::class, function ($app) {
            return new PropertyDefinitionRepository($app->make(PropertyDefinition::class));
        });
        $this->app->singleton(PriceTypeRepository::class, function ($app) {
            return new PriceTypeRepository($app->make(PriceType::class));
        });
        $this->app->singleton(WarehouseRepository::class, function ($app) {
            return new WarehouseRepository($app->make(Warehouse::class));
        });

        // Регистрация QueryBus (read-операции)
        $this->app->singleton(QueryBusInterface::class, function ($app) {
            $queryBus = new QueryBus;

            // Регистрация Query handlers
            $queryBus->register(GetProductsQuery::class, function ($query) use ($app) {
                return $app->make(GetProductsQueryHandler::class)->handle($query);
            });
            $queryBus->register(GetProductQuery::class, function ($query) use ($app) {
                return $app->make(GetProductQueryHandler::class)->handle($query);
            });

            $queryBus->register(GetOrdersQuery::class, function ($query) use ($app) {
                return $app->make(GetOrdersQueryHandler::class)->handle($query);
            });
            $queryBus->register(GetOrderQuery::class, function ($query) use ($app) {
                return $app->make(GetOrderQueryHandler::class)->handle($query);
            });

            $queryBus->register(GetCounterpartiesQuery::class, function ($query) use ($app) {
                return $app->make(GetCounterpartiesQueryHandler::class)->handle($query);
            });
            $queryBus->register(GetCounterpartyQuery::class, function ($query) use ($app) {
                return $app->make(GetCounterpartyQueryHandler::class)->handle($query);
            });

            $queryBus->register(GetCategoriesQuery::class, function ($query) use ($app) {
                return $app->make(GetCategoriesQueryHandler::class)->handle($query);
            });
            $queryBus->register(GetCategoryQuery::class, function ($query) use ($app) {
                return $app->make(GetCategoryQueryHandler::class)->handle($query);
            });

            $queryBus->register(GetOffersQuery::class, function ($query) use ($app) {
                return $app->make(GetOffersQueryHandler::class)->handle($query);
            });
            $queryBus->register(GetOfferQuery::class, function ($query) use ($app) {
                return $app->make(GetOfferQueryHandler::class)->handle($query);
            });

            $queryBus->register(GetPriceTypesQuery::class, function ($query) use ($app) {
                return $app->make(GetPriceTypesQueryHandler::class)->handle($query);
            });
            $queryBus->register(GetWarehousesQuery::class, function ($query) use ($app) {
                return $app->make(GetWarehousesQueryHandler::class)->handle($query);
            });

            return $queryBus;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Регистрация Command → Handler маппинга (Laravel Bus)
        Bus::map([
            CreateProductCommand::class => CreateProductCommandHandler::class,
            UpdateProductCommand::class => UpdateProductCommandHandler::class,
            DeleteProductCommand::class => DeleteProductCommandHandler::class,
            CreateOrderCommand::class => CreateOrderCommandHandler::class,
            UpdateOrderCommand::class => UpdateOrderCommandHandler::class,
            DeleteOrderCommand::class => DeleteOrderCommandHandler::class,
            CreateCounterpartyCommand::class => CreateCounterpartyCommandHandler::class,
            UpdateCounterpartyCommand::class => UpdateCounterpartyCommandHandler::class,
            DeleteCounterpartyCommand::class => DeleteCounterpartyCommandHandler::class,
            CreateCategoryCommand::class => CreateCategoryCommandHandler::class,
            UpdateCategoryCommand::class => UpdateCategoryCommandHandler::class,
            DeleteCategoryCommand::class => DeleteCategoryCommandHandler::class,
            CreateOfferCommand::class => CreateOfferCommandHandler::class,
            UpdateOfferCommand::class => UpdateOfferCommandHandler::class,
            DeleteOfferCommand::class => DeleteOfferCommandHandler::class,
            UpsertProductCommand::class => UpsertProductCommandHandler::class,
            UpsertOfferCommand::class => UpsertOfferCommandHandler::class,
            BulkUpsertOrderCommand::class => BulkUpsertOrderCommandHandler::class,
            UpsertOrderCommand::class => UpsertOrderCommandHandler::class,
            UpsertCategoryCommand::class => UpsertCategoryCommandHandler::class,
            UpsertPriceTypeCommand::class => UpsertPriceTypeCommandHandler::class,
            UpsertPropertyDefinitionCommand::class => UpsertPropertyDefinitionCommandHandler::class,
            UpsertCounterpartyCommand::class => UpsertCounterpartyCommandHandler::class,
            UpsertWarehouseCommand::class => UpsertWarehouseCommandHandler::class,
            UpsertOfferPriceCommand::class => UpsertOfferPriceCommandHandler::class,
            UpsertStockCommand::class => UpsertStockCommandHandler::class,
        ]);

        $this->publishes([
            __DIR__.'/config/commercejson.php' => config_path('commercejson.php'),
        ], 'commercejson-config');

        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], 'commercejson-migrations');

        // Загрузка миграций по умолчанию (если приложение не переопределило)
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Загрузка маршрутов
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                HandshakeCommand::class,
                ImportClassifierCommand::class,
                ImportProductsCommand::class,
                ImportOffersCommand::class,
                ImportOrdersCommand::class,
                ExportOrdersCommand::class,
                SyncCommand::class,
            ]);
        }

        // Регистрация событий
        if (config('commercejson.events.dispatch', true)) {
            $this->registerEvents();
        }
    }

    /**
     * Регистрация событий и слушателей
     */
    protected function registerEvents(): void
    {
        $events = $this->app->make('events');

        $listen = config('commercejson.events.listen', []);

        foreach ($listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
    }
}
