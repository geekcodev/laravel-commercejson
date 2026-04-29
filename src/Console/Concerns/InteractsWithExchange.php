<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Concerns;

use GeekCo\CommerceJson\Exceptions\AuthenticationException;
use GeekCo\CommerceJson\Exceptions\BusinessException;
use GeekCo\CommerceJson\Exceptions\RateLimitException;
use GeekCo\CommerceJson\Exceptions\SyncException;
use GeekCo\CommerceJson\Exceptions\ValidationException;
use GeekCo\CommerceJson\Http\Client\CommerceJsonConnector;

/**
 * Trait для Console Commands работы с CommerceJSON
 */
trait InteractsWithExchange
{
    /**
     * Получить HTTP коннектор
     */
    public function connector(): CommerceJsonConnector
    {
        return app(CommerceJsonConnector::class);
    }

    /**
     * Проверить соединение с API
     */
    public function checkConnection(): bool
    {
        try {
            $this->connector()->handshake();

            return true;
        } catch (\Exception $e) {
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
            $this->warn('Details: '.$e->errorsAsString());

            return 1;
        } catch (BusinessException $e) {
            $this->error('Business error ['.$e->getBusinessCode().']: '.$e->getMessage());

            return 1;
        } catch (RateLimitException $e) {
            $this->warn('Rate limited. Retry after '.$e->retryAfter().' seconds');

            return 1;
        } catch (SyncException $e) {
            $this->error('Sync error ['.$e->getSyncType().']: '.$e->getMessage());

            return 1;
        } catch (\Exception $e) {
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
     */
    public function processWithProgress(iterable $items, \Closure $callback): void
    {
        if ($items instanceof \Traversable) {
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
     */
    public function displayResults(array $headers, array $rows): void
    {
        $this->table($headers, $rows);
    }

    /**
     * Логировать действие
     */
    public function logAction(string $action, array $context = []): void
    {
        if ($this->option('verbose')) {
            $this->info("[{$action}] ".json_encode($context));
        }
    }
}
