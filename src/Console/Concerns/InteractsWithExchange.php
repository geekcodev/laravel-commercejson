<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Concerns;

use Exception;
use GeekCo\CommerceJson\Exceptions\SyncException;
use GeekCo\CommerceJson\Http\Client\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Http\Client\Exceptions\BusinessException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ConnectionException;
use GeekCo\CommerceJson\Http\Client\Exceptions\RateLimitException;
use GeekCo\CommerceJson\Http\Client\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\HttpClientInterface;
use Traversable;

/**
 * Trait для Console Commands работы с CommerceJSON
 */
trait InteractsWithExchange
{
    /**
     * Получить HTTP клиент
     */
    public function http(): HttpClientInterface
    {
        return app(HttpClientInterface::class);
    }

    /**
     * Проверить соединение с API
     */
    public function checkConnection(): bool
    {
        try {
            $configPath = config('commercejson.external_api_endpoints.handshake', '/handshake');
            $this->http()->get($configPath);

            return true;
        } catch (ConnectionException|Exception $e) {
            $this->error('Connection failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Выполнить с обработкой ошибок
     */
    public function withErrorHandling(\Closure $callback): int
    {
        try {
            $callback();

            return 0;
        } catch (AuthenticationException $e) {
            $this->error('Authentication error: '.$e->getMessage());

            return 1;
        } catch (ValidationException $e) {
            $this->error('Validation error: '.$e->getMessage());
            $this->warn('Details: '.($e->errorsAsString() ?? 'No details'));

            return 1;
        } catch (BusinessException $e) {
            $this->error('Business error ['.$e->getCode().']: '.$e->getMessage());

            return 1;
        } catch (RateLimitException $e) {
            $this->warn('Rate limited. Retry after '.$e->getRetryAfter().' seconds');

            return 1;
        } catch (SyncException $e) {
            $this->error('Sync error ['.$e->getSyncType().']: '.$e->getMessage());

            return 1;
        } catch (Exception $e) {
            $this->error('Unexpected error: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->info($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Получить опцию queue
     */
    public function shouldUseQueue(): bool
    {
        return $this->option('queue') && config('commercejson.exchange.queue.enabled', true);
    }

    /**
     * Получить размер пакета
     */
    public function getBatchSize(string $type): int
    {
        return config("commercejson.exchange.batch_size.{$type}", 100);
    }

    /**
     * Показать прогресс бар (кастомный метод)
     *
     * @param  iterable<mixed>  $items
     */
    public function processWithProgress(iterable $items, \Closure $callback): void
    {
        if ($items instanceof Traversable) {
            $items = iterator_to_array($items);
        }

        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        foreach ($items as $key => $item) {
            $callback($item, $key);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Показать таблицу с результатами
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function displayResults(array $headers, array $rows): void
    {
        $this->table($headers, $rows);
    }

    /**
     * Логировать действие
     *
     * @param  array<string, mixed>  $context
     */
    public function logAction(string $action, array $context = []): void
    {
        if ($this->option('verbose')) {
            $this->info("[{$action}] ".json_encode($context));
        }
    }
}
