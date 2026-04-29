<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson;

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
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;
use GeekCo\CommerceJson\Services\ClassifierService;
use GeekCo\CommerceJson\Services\CounterpartyService;
use GeekCo\CommerceJson\Services\OfferService;
use GeekCo\CommerceJson\Services\OrderService;
use GeekCo\CommerceJson\Services\ProductService;
use GeekCo\CommerceJson\Services\WarehouseService;
use GeekCo\CommerceJson\Support\Mappers\ProductMapper;
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

        // Регистрация HTTP клиента
        $this->app->singleton(CommerceJsonConnector::class, function ($app) {
            $connector = new CommerceJsonConnector(
                baseUrl: config('commercejson.base_url'),
                authToken: config('commercejson.auth.token'),
                timeout: config('commercejson.timeout'),
                authType: config('commercejson.auth.type'),
            );

            // Настройка Basic auth если нужно
            if (config('commercejson.auth.type') === 'basic') {
                $connector->setBasicAuth(
                    config('commercejson.auth.login'),
                    config('commercejson.auth.password')
                );
            }

            // Включение логирования
            if (config('commercejson.logging.enabled')) {
                $connector->enableLogging(config('commercejson.logging.channel'));

                if (config('commercejson.logging.log_requests') || config('commercejson.logging.log_responses')) {
                    $connector->enableLogging();
                }
            }

            // Количество попыток
            $connector->setRetryAttempts(config('commercejson.retry_attempts'));

            return $connector;
        });

        // Регистрация сервисов
        $this->app->singleton(ProductService::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(OfferService::class);
        $this->app->singleton(ClassifierService::class);
        $this->app->singleton(WarehouseService::class);
        $this->app->singleton(CounterpartyService::class);

        // Регистрация мапперов
        $this->app->singleton(ProductMapper::class);

        // Регистрация Exchange компонентов
        $this->app->singleton(ProductImporter::class);
        $this->app->singleton(OrderImporter::class);
        $this->app->singleton(ClassifierImporter::class);
        $this->app->singleton(OrderExporter::class);

        // Регистрация Exchange Manager
        $this->app->singleton(ExchangeManager::class);

        // Регистрация фасада
        $this->app->alias('commercejson', CommerceJson::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/config/commercejson.php' => config_path('commercejson.php'),
        ], 'commercejson-config');

        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], 'commercejson-migrations');

        // Загрузка миграций по умолчанию (если приложение не переопределило)
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

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
